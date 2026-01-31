<?php
require_once '../config/config.php';
requireLogin();

class VentaController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function procesar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                requireCsrfToken();
                $venta = json_decode($_POST['venta'], true);

                // Validaciones
                if (empty($venta['carrito'])) {
                    echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
                    return;
                }

                // Verificar caja abierta
                $caja = getCajaAbierta();
                if (!$caja) {
                    echo json_encode(['success' => false, 'message' => 'No hay caja abierta']);
                    return;
                }

                // Iniciar transacción
                $this->db->beginTransaction();

                // Calcular totales desde BD (no confiar en el cliente)
                $subtotal = 0;
                $detalle_items = [];
                $stmtProducto = $this->db->prepare("SELECT nombre, stock_actual, precio_venta, activo FROM productos WHERE id = :id");

                foreach ($venta['carrito'] as $item) {
                    $producto_id = intval($item['id'] ?? 0);
                    $cantidad = intval($item['cantidad'] ?? 0);

                    if ($producto_id <= 0 || $cantidad <= 0) {
                        $this->db->rollBack();
                        echo json_encode(['success' => false, 'message' => 'Producto o cantidad inválida']);
                        return;
                    }

                    // MULTI-TENANT: Validar que el producto pertenece a la tienda
                    if (!TenantHelper::validateAccess('productos', $producto_id)) {
                        $this->db->rollBack();
                        echo json_encode(['success' => false, 'message' => 'Producto no válido']);
                        return;
                    }

                    $stmtProducto->execute(['id' => $producto_id]);
                    $producto = $stmtProducto->fetch();

                    if (!$producto || !$producto['activo']) {
                        $this->db->rollBack();
                        echo json_encode(['success' => false, 'message' => 'Producto no disponible']);
                        return;
                    }

                    if ($producto['stock_actual'] < $cantidad) {
                        $this->db->rollBack();
                        echo json_encode(['success' => false, 'message' => 'Stock insuficiente para: ' . $producto['nombre']]);
                        return;
                    }

                    $precio_unit = (float)$producto['precio_venta'];
                    $item_subtotal = $precio_unit * $cantidad;
                    $subtotal += $item_subtotal;

                    $detalle_items[] = [
                        'id' => $producto_id,
                        'cantidad' => $cantidad,
                        'precio_unit' => $precio_unit,
                        'subtotal' => $item_subtotal
                    ];
                }

                $igv = 0;
                $total = $subtotal;

                if ($venta['aplicar_igv']) {
                    $igv = $subtotal * (IGV_PORCENTAJE / 100);
                    $total = $subtotal + $igv;
                }

                // Generar número de ticket
                $nro_ticket = getNextTicketNumber();

                // Obtener cliente_id enviado desde el frontend
                $cliente_id_enviado = isset($venta['cliente_id']) ? intval($venta['cliente_id']) : 0;

                // MULTI-TENANT: Buscar cliente ANÓNIMO de esta tienda
                $stmt = $this->db->prepare("SELECT id FROM clientes WHERE nombre = 'ANÓNIMO' AND tienda_id = :tienda_id");
                $stmt->execute(['tienda_id' => getTiendaId()]);
                $anonimo = $stmt->fetch();
                $anonimo_id = $anonimo ? $anonimo['id'] : null;

                // Determinar el cliente_id final
                $cliente_id = $anonimo_id; // Por defecto es anónimo

                // Si se envió un cliente_id válido (mayor a 0), verificar que pertenece a la tienda
                if ($cliente_id_enviado > 0) {
                    // Verificar que el cliente existe y pertenece a esta tienda
                    if (TenantHelper::validateAccess('clientes', $cliente_id_enviado)) {
                        // Cliente válido, usarlo
                        $cliente_id = $cliente_id_enviado;
                    }
                    // Si no es válido, ya está asignado el anónimo por defecto
                }

                // Verificar que tenemos un cliente válido
                if (!$cliente_id) {
                    $this->db->rollBack();
                    echo json_encode(['success' => false, 'message' => 'No se encontró cliente ANÓNIMO para esta tienda']);
                    return;
                }

                // MULTI-TENANT: Obtener tienda_id
                $tienda_id = getTiendaId();

                // Insertar venta
                $stmt = $this->db->prepare("
                    INSERT INTO ventas (tienda_id, nro_ticket, caja_id, usuario_id, cliente_id, subtotal, igv, total, aplica_igv, estado)
                    VALUES (:tienda_id, :nro_ticket, :caja_id, :usuario_id, :cliente_id, :subtotal, :igv, :total, :aplica_igv, 'PAGADA')
                ");

                $stmt->execute([
                    'tienda_id' => $tienda_id,
                    'nro_ticket' => $nro_ticket,
                    'caja_id' => $caja['id'],
                    'usuario_id' => $_SESSION['user_id'],
                    'cliente_id' => $cliente_id,
                    'subtotal' => $subtotal,
                    'igv' => $igv,
                    'total' => $total,
                    'aplica_igv' => $venta['aplicar_igv'] ? 1 : 0
                ]);

                $venta_id = $this->db->lastInsertId();

                // MULTI-TENANT: Insertar detalle de venta
                $stmt = $this->db->prepare("
                    INSERT INTO detalle_venta (tienda_id, venta_id, producto_id, cantidad, precio_unit, subtotal)
                    VALUES (:tienda_id, :venta_id, :producto_id, :cantidad, :precio_unit, :subtotal)
                ");

                foreach ($detalle_items as $item) {
                    $stmt->execute([
                        'tienda_id' => $tienda_id,
                        'venta_id' => $venta_id,
                        'producto_id' => $item['id'],
                        'cantidad' => $item['cantidad'],
                        'precio_unit' => $item['precio_unit'],
                        'subtotal' => $item['subtotal']
                    ]);

                    // Descontar stock
                    $stmtStock = $this->db->prepare("
                        UPDATE productos
                        SET stock_actual = stock_actual - :cantidad
                        WHERE id = :id
                    ");
                    $stmtStock->execute([
                        'cantidad' => $item['cantidad'],
                        'id' => $item['id']
                    ]);
                }

                // Insertar pago
                $medio_pago = $venta['medio_pago'];
                $monto_recibido = null;
                $vuelto = 0;
                $ref_operacion = null;

                if ($medio_pago === 'EFECTIVO') {
                    $monto_recibido = floatval($venta['monto_recibido']);
                    if ($monto_recibido < $total) {
                        $this->db->rollBack();
                        echo json_encode(['success' => false, 'message' => 'Monto recibido insuficiente']);
                        return;
                    }
                    $vuelto = $monto_recibido - $total;
                } else {
                    $ref_operacion = $venta['ref_operacion'] ?? null;
                    if (!$ref_operacion) {
                        $this->db->rollBack();
                        echo json_encode(['success' => false, 'message' => 'Número de operación requerido']);
                        return;
                    }
                }

                // MULTI-TENANT: Insertar pago
                $stmt = $this->db->prepare("
                    INSERT INTO pagos (tienda_id, venta_id, medio, monto, ref_operacion, monto_recibido, vuelto)
                    VALUES (:tienda_id, :venta_id, :medio, :monto, :ref_operacion, :monto_recibido, :vuelto)
                ");

                $stmt->execute([
                    'tienda_id' => $tienda_id,
                    'venta_id' => $venta_id,
                    'medio' => $medio_pago,
                    'monto' => $total,
                    'ref_operacion' => $ref_operacion,
                    'monto_recibido' => $monto_recibido,
                    'vuelto' => $vuelto
                ]);

                // Actualizar totales de caja
                if ($medio_pago === 'EFECTIVO') {
                    $stmtCaja = $this->db->prepare("
                        UPDATE cajas
                        SET efectivo_esperado = efectivo_esperado + :monto
                        WHERE id = :id
                    ");
                    $stmtCaja->execute(['monto' => $total, 'id' => $caja['id']]);
                } else {
                    $stmtCaja = $this->db->prepare("
                        UPDATE cajas
                        SET yape_total_registrado = yape_total_registrado + :monto
                        WHERE id = :id
                    ");
                    $stmtCaja->execute(['monto' => $total, 'id' => $caja['id']]);
                }

                // Confirmar transacción
                $this->db->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Venta procesada exitosamente',
                    'venta_id' => $venta_id,
                    'ticket' => $nro_ticket
                ]);

            } catch (PDOException $e) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        }
    }

    public function anular() {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $venta_id = $_POST['venta_id'];
            $motivo = sanitizeInput($_POST['motivo']);

            try {
                $this->db->beginTransaction();

                // MULTI-TENANT: Validar que la venta pertenece a la tienda (excepto SUPER_ADMIN)
                if (!isSuperAdmin()) {
                    if (!TenantHelper::validateAccess('ventas', $venta_id)) {
                        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                        return;
                    }
                }

                // Obtener detalles de la venta
                $stmt = $this->db->prepare("SELECT * FROM ventas WHERE id = :id AND estado = 'PAGADA'");
                $stmt->execute(['id' => $venta_id]);
                $venta = $stmt->fetch();

                if (!$venta) {
                    echo json_encode(['success' => false, 'message' => 'Venta no encontrada o ya anulada']);
                    return;
                }

                // Verificar que sea del día actual
                if (date('Y-m-d', strtotime($venta['fecha_hora'])) !== date('Y-m-d')) {
                    echo json_encode(['success' => false, 'message' => 'Solo se pueden anular ventas del día actual']);
                    return;
                }

                // Obtener detalle de venta
                $stmt = $this->db->prepare("SELECT * FROM detalle_venta WHERE venta_id = :venta_id");
                $stmt->execute(['venta_id' => $venta_id]);
                $detalles = $stmt->fetchAll();

                // Reponer stock
                foreach ($detalles as $detalle) {
                    $stmtStock = $this->db->prepare("
                        UPDATE productos
                        SET stock_actual = stock_actual + :cantidad
                        WHERE id = :id
                    ");
                    $stmtStock->execute([
                        'cantidad' => $detalle['cantidad'],
                        'id' => $detalle['producto_id']
                    ]);
                }

                // Marcar venta como anulada
                $stmt = $this->db->prepare("
                    UPDATE ventas
                    SET estado = 'ANULADA',
                        motivo_anulacion = :motivo,
                        anulado_por = :usuario_id,
                        fecha_anulacion = NOW()
                    WHERE id = :id
                ");

                $stmt->execute([
                    'motivo' => $motivo,
                    'usuario_id' => $_SESSION['user_id'],
                    'id' => $venta_id
                ]);

                // Actualizar caja
                $stmt = $this->db->prepare("SELECT medio FROM pagos WHERE venta_id = :venta_id");
                $stmt->execute(['venta_id' => $venta_id]);
                $pago = $stmt->fetch();

                if ($pago['medio'] === 'EFECTIVO') {
                    $stmtCaja = $this->db->prepare("
                        UPDATE cajas
                        SET efectivo_esperado = efectivo_esperado - :monto
                        WHERE id = :id
                    ");
                    $stmtCaja->execute(['monto' => $venta['total'], 'id' => $venta['caja_id']]);
                } else {
                    $stmtCaja = $this->db->prepare("
                        UPDATE cajas
                        SET yape_total_registrado = yape_total_registrado - :monto
                        WHERE id = :id
                    ");
                    $stmtCaja->execute(['monto' => $venta['total'], 'id' => $venta['caja_id']]);
                }

                $this->db->commit();

                setFlashMessage('Venta anulada exitosamente', 'success');
                redirect('/views/admin/ventas_anuladas.php');

            } catch (PDOException $e) {
                $this->db->rollBack();
                setFlashMessage('Error al anular venta: ' . $e->getMessage(), 'danger');
                redirect('/views/admin/ventas_anuladas.php');
            }
        }
    }

    public function obtener_ticket() {
        if (isset($_GET['id'])) {
            $venta_id = $_GET['id'];

            // MULTI-TENANT + FASE 8: Obtener datos de la venta CON datos de la tienda y logo
            $stmt = $this->db->prepare("
                SELECT
                    v.*,
                    u.nombre as cajero,
                    cl.nombre as cliente_nombre,
                    cl.dni_ruc as cliente_dni,
                    cl.telefono as cliente_telefono,
                    t.nombre_negocio,
                    t.ruc,
                    t.direccion,
                    t.telefono as telefono_negocio,
                    t.logo,
                    t.igv as igv_porcentaje
                FROM ventas v
                INNER JOIN usuarios u ON v.usuario_id = u.id
                INNER JOIN clientes cl ON v.cliente_id = cl.id
                INNER JOIN tiendas t ON v.tienda_id = t.id
                WHERE v.id = :id
            ");
            $stmt->execute(['id' => $venta_id]);
            $venta = $stmt->fetch();

            if (!$venta) {
                echo '<div class="alert alert-danger">Venta no encontrada</div>';
                return;
            }

            // MULTI-TENANT: Validar acceso (solo puede ver tickets de su tienda, excepto SUPER_ADMIN)
            if (!isSuperAdmin() && $venta['tienda_id'] != getTiendaId()) {
                echo '<div class="alert alert-danger">Acceso denegado</div>';
                return;
            }

            // Obtener detalle de venta
            $stmt = $this->db->prepare("
                SELECT dv.*, p.nombre as producto
                FROM detalle_venta dv
                INNER JOIN productos p ON dv.producto_id = p.id
                WHERE dv.venta_id = :venta_id
            ");
            $stmt->execute(['venta_id' => $venta_id]);
            $detalles = $stmt->fetchAll();

            // Obtener datos del pago
            $stmt = $this->db->prepare("SELECT * FROM pagos WHERE venta_id = :venta_id");
            $stmt->execute(['venta_id' => $venta_id]);
            $pago = $stmt->fetch();

            // FASE 8: Generar HTML del ticket CON LOGO
            ob_start();
            ?>
            <div class="ticket" style="max-width: 400px; margin: 0 auto; font-family: 'Courier New', monospace;">
                <!-- FASE 8: Encabezado con Logo -->
                <div style="text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px;">
                    <?php if ($venta['logo']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?php echo getLogoUrl($venta['logo']); ?>"
                             alt="Logo"
                             style="max-width: 150px; max-height: 80px;">
                    </div>
                    <?php endif; ?>

                    <h4 style="margin: 0; font-size: 18px;"><strong><?php echo e($venta['nombre_negocio']); ?></strong></h4>
                    <p style="margin: 5px 0; font-size: 12px;">RUC: <?php echo e($venta['ruc']); ?></p>
                    <p style="margin: 5px 0; font-size: 12px;"><?php echo e($venta['direccion']); ?></p>
                    <?php if ($venta['telefono_negocio']): ?>
                    <p style="margin: 5px 0; font-size: 12px;">Tel: <?php echo e($venta['telefono_negocio']); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Información de la venta -->
                <div style="font-size: 11px; margin: 10px 0;">
                    <table style="width: 100%;">
                        <tr>
                            <td><strong>BOLETA:</strong></td>
                            <td><?php echo e($venta['nro_ticket']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>FECHA:</strong></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($venta['fecha_hora'])); ?></td>
                        </tr>
                        <tr>
                            <td><strong>CAJERO:</strong></td>
                            <td><?php echo e($venta['cajero']); ?></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 5px; border-top: 1px solid #ccc;"></td>
                        </tr>
                        <tr>
                            <td><strong>CLIENTE:</strong></td>
                            <td><?php echo e($venta['cliente_nombre']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>DNI/RUC:</strong></td>
                            <td><?php echo e($venta['cliente_dni']); ?></td>
                        </tr>
                        <?php if ($venta['cliente_nombre'] !== 'ANÓNIMO' && $venta['cliente_telefono'] && $venta['cliente_telefono'] !== '-'): ?>
                        <tr>
                            <td><strong>TELÉFONO:</strong></td>
                            <td><?php echo e($venta['cliente_telefono']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <div style="border-top: 2px dashed #000; border-bottom: 2px dashed #000; padding: 10px 0; margin: 10px 0;">
                    <table style="width: 100%; font-size: 10px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #000;">
                                <th style="text-align: left;">CANT</th>
                                <th style="text-align: left;">PRODUCTO</th>
                                <th style="text-align: right;">P.UNIT</th>
                                <th style="text-align: right;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?php echo $detalle['cantidad']; ?></td>
                                <td><?php echo e($detalle['producto']); ?></td>
                                <td style="text-align: right;"><?php echo number_format($detalle['precio_unit'], 2); ?></td>
                                <td style="text-align: right;"><?php echo number_format($detalle['subtotal'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totales -->
                <table style="width: 100%; font-size: 12px; margin: 10px 0;">
                    <?php if ($venta['aplica_igv']): ?>
                    <tr>
                        <td style="text-align: right;"><strong>SUBTOTAL:</strong></td>
                        <td style="text-align: right; width: 100px;">S/. <?php echo number_format($venta['subtotal'], 2); ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><strong>IGV (<?php echo $venta['igv_porcentaje']; ?>%):</strong></td>
                        <td style="text-align: right;">S/. <?php echo number_format($venta['igv'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr style="font-size: 14px;">
                        <td style="text-align: right;"><strong>TOTAL:</strong></td>
                        <td style="text-align: right;"><strong>S/. <?php echo number_format($venta['total'], 2); ?></strong></td>
                    </tr>
                </table>

                <!-- Información del pago -->
                <div style="border-top: 2px dashed #000; padding-top: 10px; margin-top: 10px; font-size: 11px;">
                    <table style="width: 100%;">
                        <tr>
                            <td><strong>MEDIO DE PAGO:</strong></td>
                            <td style="text-align: right;">
                                <?php echo e($pago['medio']); ?>
                            </td>
                        </tr>
                        <?php if ($pago['medio'] === 'EFECTIVO'): ?>
                        <tr>
                            <td><strong>RECIBIDO:</strong></td>
                            <td style="text-align: right;">S/. <?php echo number_format($pago['monto_recibido'], 2); ?></td>
                        </tr>
                        <tr>
                            <td><strong>VUELTO:</strong></td>
                            <td style="text-align: right;">S/. <?php echo number_format($pago['vuelto'], 2); ?></td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td><strong>Nº OPERACIÓN:</strong></td>
                            <td style="text-align: right;"><?php echo e($pago['ref_operacion']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Pie de página -->
                <div style="text-align: center; padding-top: 15px; margin-top: 10px; border-top: 2px dashed #000; font-size: 12px;">
                    <p style="margin: 5px 0;"><strong>¡GRACIAS POR SU COMPRA!</strong></p>
                    <p style="margin: 3px 0; font-size: 9px;">Impulsado por Servicios y Proyectos DYM S.A.C. - Soporte y Ventas</p>
                    <?php if ($venta['estado'] === 'ANULADA'): ?>
                    <p style="margin: 10px 0; color: red; font-weight: bold;">*** VENTA ANULADA ***</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            echo ob_get_clean();
        }
    }
}

// Manejar acciones
if (isset($_GET['action'])) {
    $controller = new VentaController();
    $action = $_GET['action'];

    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
