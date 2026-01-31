<?php
require_once '../../config/config.php';
requireLogin();

if (!isset($_GET['id'])) {
    redirect('/views/pos/venta.php');
}

$venta_id = $_GET['id'];
$db = Database::getInstance()->getConnection();

// Obtener datos de la venta
$stmt = $db->prepare("
    SELECT v.*, u.nombre as cajero, c.fecha_apertura, cl.nombre as cliente_nombre, cl.dni_ruc as cliente_dni, cl.telefono as cliente_telefono
    FROM ventas v
    INNER JOIN usuarios u ON v.usuario_id = u.id
    INNER JOIN cajas c ON v.caja_id = c.id
    INNER JOIN clientes cl ON v.cliente_id = cl.id
    WHERE v.id = :id
");
$stmt->execute(['id' => $venta_id]);
$venta = $stmt->fetch();

if (!$venta) {
    die('Venta no encontrada');
}

// Obtener detalle de venta
$stmt = $db->prepare("
    SELECT dv.*, p.nombre as producto
    FROM detalle_venta dv
    INNER JOIN productos p ON dv.producto_id = p.id
    WHERE dv.venta_id = :venta_id
");
$stmt->execute(['venta_id' => $venta_id]);
$detalles = $stmt->fetchAll();

// Obtener datos del pago
$stmt = $db->prepare("SELECT * FROM pagos WHERE venta_id = :venta_id");
$stmt->execute(['venta_id' => $venta_id]);
$pago = $stmt->fetch();

// MULTI-TENANT: Obtener datos de la tienda desde la venta
$stmt = $db->prepare("SELECT * FROM tiendas WHERE id = :tienda_id");
$stmt->execute(['tienda_id' => $venta['tienda_id']]);
$tienda = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta - <?php echo e($venta['nro_ticket']); ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/style.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12 text-center mb-3 no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
                <button onclick="window.close()" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cerrar
                </button>
            </div>
        </div>

        <div class="ticket">
            <!-- Encabezado -->
            <div class="ticket-header">
                <h4 style="margin: 0;"><?php echo e($tienda['nombre_negocio']); ?></h4>
                <p style="margin: 5px 0;">RUC: <?php echo e($tienda['ruc']); ?></p>
                <p style="margin: 5px 0;"><?php echo e($tienda['direccion']); ?></p>
                <?php if ($tienda['telefono']): ?>
                <p style="margin: 5px 0;">Tel: <?php echo e($tienda['telefono']); ?></p>
                <?php endif; ?>
            </div>

            <!-- Información de la venta -->
            <div class="ticket-body">
                <table style="width: 100%; font-size: 12px; margin: 10px 0;">
                    <tr>
                                <td><strong>BOLETA:</strong></td>
                                <td><?php echo e($venta['nro_ticket']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>FECHA:</strong></td>
                        <td><?php echo e(date('d/m/Y H:i:s', strtotime($venta['fecha_hora']))); ?></td>
                    </tr>
                    <tr>
                        <td><strong>CAJERO:</strong></td>
                        <td><?php echo e($venta['cajero']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>CAJA:</strong></td>
                        <td>#<?php echo e($venta['caja_id']); ?></td>
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

                <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin: 10px 0;">
                    <table style="width: 100%; font-size: 11px;">
                        <thead>
                            <tr>
                                <th style="text-align: left;">CANT</th>
                                <th style="text-align: left;">PRODUCTO</th>
                                <th style="text-align: right;">P.UNIT</th>
                                <th style="text-align: right;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?php echo e($detalle['cantidad']); ?></td>
                                <td><?php echo e($detalle['producto']); ?></td>
                                <td style="text-align: right;"><?php echo e(number_format($detalle['precio_unit'], 2)); ?></td>
                                <td style="text-align: right;"><?php echo e(number_format($detalle['subtotal'], 2)); ?></td>
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
                        <td style="text-align: right;">S/. <?php echo e(number_format($venta['subtotal'], 2)); ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><strong>IGV (18%):</strong></td>
                        <td style="text-align: right;">S/. <?php echo e(number_format($venta['igv'], 2)); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr style="font-size: 14px;">
                        <td style="text-align: right;"><strong>TOTAL:</strong></td>
                        <td style="text-align: right;"><strong>S/. <?php echo e(number_format($venta['total'], 2)); ?></strong></td>
                    </tr>
                </table>

                <!-- Información del pago -->
                <div style="border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px;">
                    <table style="width: 100%; font-size: 11px;">
                        <tr>
                            <td><strong>MEDIO DE PAGO:</strong></td>
                            <td style="text-align: right;">
                                <?php if ($pago['medio'] === 'EFECTIVO'): ?>
                                    EFECTIVO
                                <?php else: ?>
                                    YAPE
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($pago['medio'] === 'EFECTIVO'): ?>
                        <tr>
                            <td><strong>MONTO RECIBIDO:</strong></td>
                            <td style="text-align: right;">S/. <?php echo e(number_format($pago['monto_recibido'], 2)); ?></td>
                        </tr>
                        <tr>
                            <td><strong>VUELTO:</strong></td>
                            <td style="text-align: right;">S/. <?php echo e(number_format($pago['vuelto'], 2)); ?></td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td><strong>Nº OPERACIÓN:</strong></td>
                            <td style="text-align: right;"><?php echo e($pago['ref_operacion']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Pie de página -->
            <div class="ticket-footer">
                <p style="margin: 5px 0;"><strong>¡GRACIAS POR SU COMPRA!</strong></p>
                <p style="margin: 3px 0; font-size: 9px;">Impulsado por Servicios y Proyectos DYM S.A.C. - Soporte y Ventas</p>
                <?php if ($venta['estado'] === 'ANULADA'): ?>
                <p style="margin: 10px 0; color: red; font-weight: bold;">*** VENTA ANULADA ***</p>
                <p style="margin: 5px 0; font-size: 10px;">Motivo: <?php echo e($venta['motivo_anulacion']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-imprimir al cargar (opcional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
