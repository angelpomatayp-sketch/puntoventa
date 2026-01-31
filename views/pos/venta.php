<?php
require_once '../../config/config.php';
requireLogin();

// Verificar que haya una caja abierta
if (isAdmin()) {
    // Admin puede vender con cualquier caja abierta
    $caja = getCajaAbierta();
} else {
    // Cajero solo puede vender con SU caja abierta
    $caja = getCajaAbierta($_SESSION['user_id']);
}

if (!$caja) {
    setFlashMessage('Debe abrir su caja antes de realizar ventas', 'warning');
    if (isAdmin()) {
        redirect('/views/admin/cajas.php');
    } else {
        redirect('/views/cajero/caja.php');
    }
    exit();
}

$page_title = 'Punto de Venta';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener productos activos con stock (MULTI-TENANT)
$sql = "
    SELECT p.*, c.nombre as categoria
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.activo = TRUE AND p.stock_actual > 0
";
// Filtrar por tienda especificando la tabla
if (!isSuperAdmin()) {
    $sql .= " AND p.tienda_id = " . getTiendaId();
}
$sql .= " ORDER BY p.nombre ASC";
$stmt = $db->query($sql);
$productos = $stmt->fetchAll();

// Obtener categorías asignadas a la tienda (MULTI-TENANT)
$categorias = getCategoriasDisponibles();

// MULTI-TENANT: Obtener cliente ANÓNIMO de esta tienda
$stmt = $db->prepare("SELECT id FROM clientes WHERE nombre = 'ANÓNIMO' AND tienda_id = :tienda_id");
$stmt->execute(['tienda_id' => getTiendaId()]);
$clienteAnonimo = $stmt->fetch();
$clienteAnonimoId = $clienteAnonimo ? $clienteAnonimo['id'] : 1;
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-cash-stack"></i> <strong>Caja Abierta:</strong> #<?php echo e($caja['id']); ?>
                | <strong>Cajero:</strong> <?php echo e($_SESSION['nombre']); ?>
            </div>
            <div>
                <i class="bi bi-calendar3"></i> <?php echo e(date('d/m/Y H:i:s')); ?>
            </div>
        </div>
    </div>
</div>

<!-- Selector de Cliente -->
<div class="row mb-3" style="position: relative; z-index: 100;">
    <div class="col-12">
        <div class="card" style="overflow: visible;">
            <div class="card-body" style="overflow: visible;">
                <div class="row align-items-end">
                    <div class="col-md-8" style="position: relative;">
                        <label class="form-label"><i class="bi bi-person"></i> <strong>Cliente:</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="buscarClienteInput"
                                   placeholder="Buscar cliente por nombre o DNI..." autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" id="btnSeleccionarAnonimo">
                                <i class="bi bi-person-x"></i> Anónimo
                            </button>
                        </div>
                        <div id="resultadosCliente" class="list-group" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 9999; max-height: 250px; overflow-y: auto; background: white; box-shadow: 0 6px 12px rgba(0,0,0,0.15); border: 1px solid #ccc; border-radius: 4px;"></div>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
                            <i class="bi bi-plus-circle"></i> Nuevo Cliente
                        </button>
                    </div>
                </div>
                <div id="clienteSeleccionado" class="mt-2" style="display: none;">
                    <input type="hidden" id="clienteId" value="<?php echo e($clienteAnonimoId); ?>">
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-check-circle"></i> <strong>Cliente seleccionado:</strong>
                        <span id="clienteNombre">ANÓNIMO</span>
                        <span id="clienteDni" class="ms-2"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row pos-container">
    <!-- Columna de Productos -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Productos Disponibles</h5>
            </div>
            <div class="card-body">
                <!-- Buscador -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" id="buscarProducto" placeholder="Buscar producto por nombre...">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="filtroCategoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo e($cat['id']); ?>"><?php echo e($cat['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Lista de Productos -->
                <div class="productos-list">
                    <div id="listaProductos">
                        <?php foreach ($productos as $producto): ?>
                        <div class="producto-item"
                             data-id="<?php echo e($producto['id']); ?>"
                             data-nombre="<?php echo e($producto['nombre']); ?>"
                             data-precio="<?php echo e($producto['precio_venta']); ?>"
                             data-stock="<?php echo e($producto['stock_actual']); ?>"
                             data-categoria="<?php echo e($producto['categoria_id']); ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo e($producto['nombre']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo e($producto['categoria']); ?></small>
                                </div>
                                <div class="text-end">
                                    <div class="text-primary fw-bold"><?php echo e(formatMoney($producto['precio_venta'])); ?></div>
                                    <small class="badge bg-secondary">Stock: <?php echo e($producto['stock_actual']); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna del Carrito -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-cart3"></i> Carrito de Venta</h5>
                    <button type="button" class="btn btn-sm btn-danger" id="btnLimpiarCarrito">
                        <i class="bi bi-trash"></i> Limpiar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="carritoVacio" class="text-center text-muted py-5">
                    <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                    <p class="mt-3">El carrito está vacío</p>
                </div>

                <div id="carritoItems" style="display: none;">
                    <div id="listaCarrito" class="mb-3"></div>

                    <!-- Aplicar IGV -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="aplicarIGV">
                            <label class="form-check-label" for="aplicarIGV">
                                <strong>Aplicar IGV (18%)</strong>
                            </label>
                        </div>
                    </div>

                    <!-- Totales -->
                    <div class="total-section">
                        <div class="d-flex justify-content-between mb-2" id="subtotalRow" style="display: none !important;">
                            <span>Subtotal:</span>
                            <strong id="subtotalAmount">S/. 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2" id="igvRow" style="display: none !important;">
                            <span>IGV (18%):</span>
                            <strong id="igvAmount">S/. 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <h4>TOTAL:</h4>
                            <h4 class="total-amount" id="totalAmount">S/. 0.00</h4>
                        </div>

                        <!-- Medio de Pago -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Medio de Pago:</strong></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="medioPago" id="pagoEfectivo" value="EFECTIVO" checked>
                                <label class="btn btn-outline-success" for="pagoEfectivo">
                                    <i class="bi bi-cash"></i> Efectivo
                                </label>

                                <input type="radio" class="btn-check" name="medioPago" id="pagoYape" value="YAPE">
                                <label class="btn btn-outline-primary" for="pagoYape">
                                    <i class="bi bi-phone"></i> Yape
                                </label>
                            </div>
                        </div>

                        <!-- Campos según medio de pago -->
                        <div id="camposEfectivo" class="mb-3">
                            <label for="montoRecibido" class="form-label">Monto Recibido:</label>
                            <input type="number" class="form-control form-control-lg" id="montoRecibido" step="0.01" min="0">
                            <div id="vueltoInfo" class="mt-2" style="display: none;">
                                <div class="alert alert-success">
                                    <strong>Vuelto: <span id="vueltoAmount">S/. 0.00</span></strong>
                                </div>
                            </div>
                        </div>

                        <div id="camposYape" class="mb-3" style="display: none;">
                            <label for="refOperacion" class="form-label">Número de Operación:</label>
                            <input type="text" class="form-control" id="refOperacion" placeholder="Ej: 123456789">
                        </div>

                        <!-- Botón Finalizar Venta -->
                        <button type="button" class="btn btn-success btn-lg w-100" id="btnFinalizarVenta">
                            <i class="bi bi-check-circle"></i> Finalizar Venta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmación de Venta -->
<div class="modal fade" id="modalConfirmarVenta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-question-circle"></i> Confirmar Venta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-cash-coin text-success" style="font-size: 3rem;"></i>
                <h4 class="mt-3">¿Confirmar venta por <span id="montoConfirmar" class="text-primary"></span>?</h4>
                <p class="text-muted mt-2" id="detalleVenta"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmarVentaFinal">
                    <i class="bi bi-check-circle"></i> Sí, Confirmar Venta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Alerta -->
<div class="modal fade" id="modalAlerta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="alertaHeader">
                <h5 class="modal-title" id="alertaTitulo"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4" id="alertaMensaje">
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="bi bi-check-circle"></i> Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmación Genérica -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="confirmacionTitulo">
                    <i class="bi bi-question-circle"></i> Confirmar Acción
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-0" id="confirmacionMensaje"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmarAccion">
                    <i class="bi bi-check-circle"></i> Sí, Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ticket -->
<div class="modal fade" id="modalTicket" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt"></i> Ticket de Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ticketContent">
                <!-- El contenido del ticket se cargará aquí -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3">Generando ticket...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnImprimirTicket">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Cliente -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoCliente">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nuevoClienteNombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="nuevoClienteNombre" required minlength="3">
                    </div>

                    <div class="mb-3">
                        <label for="nuevoClienteDni" class="form-label">DNI/RUC *</label>
                        <input type="text" class="form-control" id="nuevoClienteDni" required
                               pattern="[0-9]{8}|[0-9]{11}" maxlength="11"
                               title="Debe ser un DNI (8 dígitos) o RUC (11 dígitos)">
                        <small class="text-muted">DNI: 8 dígitos | RUC: 11 dígitos</small>
                    </div>

                    <div class="mb-3">
                        <label for="nuevoClienteTelefono" class="form-label">Teléfono *</label>
                        <input type="text" class="form-control" id="nuevoClienteTelefono" required
                               pattern="[0-9]{9}" maxlength="9"
                               title="Debe tener 9 dígitos">
                        <small class="text-muted">9 dígitos</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar y Seleccionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$custom_js = "
<script>
    // MULTI-TENANT: ID del cliente ANÓNIMO de esta tienda
    const CLIENTE_ANONIMO_ID = " . $clienteAnonimoId . ";
</script>
<script src='" . BASE_URL . "/assets/js/pos.js'></script>
";
include '../layouts/footer.php';
?>
