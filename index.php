<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Dashboard';
include 'views/layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener estadísticas
$stats = [];

// MULTI-TENANT: Total ventas del día filtradas por tienda
$sql = "
    SELECT
        COUNT(*) as total_ventas,
        COALESCE(SUM(total), 0) as monto_total
    FROM ventas
    WHERE DATE(fecha_hora) = CURDATE() AND estado = 'PAGADA'
";
TenantHelper::addTenantScope($sql);
$stmt = $db->query($sql);
$stats['ventas_hoy'] = $stmt->fetch();

// MULTI-TENANT: Productos con stock bajo filtrados por tienda
$sql = "
    SELECT COUNT(*) as total
    FROM productos
    WHERE stock_actual <= stock_minimo AND activo = TRUE
";
TenantHelper::addTenantScope($sql);
$stmt = $db->query($sql);
$stats['stock_bajo'] = $stmt->fetch()['total'];

// Estado de caja
$caja_abierta = getCajaAbierta();
$stats['caja_estado'] = $caja_abierta ? 'ABIERTA' : 'CERRADA';
$stats['caja_id'] = $caja_abierta ? $caja_abierta['id'] : null;

// MULTI-TENANT: Ventas por medio de pago (hoy) filtradas por tienda
$sql = "
    SELECT
        p.medio,
        COUNT(DISTINCT v.id) as cantidad,
        COALESCE(SUM(p.monto), 0) as total
    FROM pagos p
    INNER JOIN ventas v ON p.venta_id = v.id
    WHERE DATE(v.fecha_hora) = CURDATE() AND v.estado = 'PAGADA'
";
// Filtrar por tienda especificando la tabla
if (!isSuperAdmin()) {
    $sql .= " AND v.tienda_id = " . getTiendaId();
}
$sql .= " GROUP BY p.medio";
$stmt = $db->query($sql);
$stats['pagos_hoy'] = $stmt->fetchAll();

// MULTI-TENANT: Productos más vendidos (hoy) filtrados por tienda
$sql = "
    SELECT
        p.nombre,
        SUM(dv.cantidad) as total_vendido,
        SUM(dv.subtotal) as monto_total
    FROM detalle_venta dv
    INNER JOIN ventas v ON dv.venta_id = v.id
    INNER JOIN productos p ON dv.producto_id = p.id
    WHERE DATE(v.fecha_hora) = CURDATE() AND v.estado = 'PAGADA'
";
// Filtrar por tienda especificando la tabla
if (!isSuperAdmin()) {
    $sql .= " AND v.tienda_id = " . getTiendaId();
}
$sql .= " GROUP BY p.id, p.nombre ORDER BY total_vendido DESC LIMIT 5";
$stmt = $db->query($sql);
$stats['productos_top'] = $stmt->fetchAll();

// MULTI-TENANT: Productos con stock bajo (detalle) filtrados por tienda
$sql = "
    SELECT p.*, c.nombre as categoria
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.stock_actual <= p.stock_minimo AND p.activo = TRUE
";
// Filtrar por tienda especificando la tabla
if (!isSuperAdmin()) {
    $sql .= " AND p.tienda_id = " . getTiendaId();
}
$sql .= " ORDER BY p.stock_actual ASC LIMIT 10";
$stmt = $db->query($sql);
$stats['productos_stock_bajo'] = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-speedometer2"></i> Dashboard
            <?php if (isCajero()): ?>
                <span class="badge bg-info">Cajero</span>
            <?php else: ?>
                <span class="badge bg-primary">Administrador</span>
            <?php endif; ?>
        </h2>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Ventas Hoy</h6>
                        <h3><?php echo $stats['ventas_hoy']['total_ventas']; ?></h3>
                        <small><?php echo formatMoney($stats['ventas_hoy']['monto_total']); ?></small>
                    </div>
                    <i class="bi bi-cart-check" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white <?php echo $stats['caja_estado'] === 'ABIERTA' ? 'bg-success' : 'bg-danger'; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Estado de Caja</h6>
                        <h3><?php echo $stats['caja_estado']; ?></h3>
                        <?php if ($caja_abierta): ?>
                        <small>Caja #<?php echo $caja_abierta['id']; ?></small>
                        <?php endif; ?>
                    </div>
                    <i class="bi bi-cash-stack" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Stock Bajo</h6>
                        <h3><?php echo $stats['stock_bajo']; ?></h3>
                        <small>Productos</small>
                    </div>
                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Fecha</h6>
                        <h5><?php echo date('d/m/Y'); ?></h5>
                        <small><?php echo date('H:i:s'); ?></small>
                    </div>
                    <i class="bi bi-calendar3" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Ventas por medio de pago -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-credit-card"></i> Ventas por Medio de Pago (Hoy)</h5>
            </div>
            <div class="card-body">
                <?php if (count($stats['pagos_hoy']) > 0): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Medio</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['pagos_hoy'] as $pago): ?>
                        <tr>
                            <td>
                                <?php if ($pago['medio'] === 'EFECTIVO'): ?>
                                    <i class="bi bi-cash text-success"></i> Efectivo
                                <?php else: ?>
                                    <i class="bi bi-phone text-primary"></i> Yape
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $pago['cantidad']; ?></td>
                            <td class="text-end"><strong><?php echo formatMoney($pago['total']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted text-center mb-0">No hay ventas registradas hoy</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Productos más vendidos -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Productos Más Vendidos (Hoy)</h5>
            </div>
            <div class="card-body">
                <?php if (count($stats['productos_top']) > 0): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['productos_top'] as $producto): ?>
                        <tr>
                            <td><?php echo $producto['nombre']; ?></td>
                            <td class="text-center"><span class="badge bg-primary"><?php echo $producto['total_vendido']; ?></span></td>
                            <td class="text-end"><?php echo formatMoney($producto['monto_total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted text-center mb-0">No hay ventas registradas hoy</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de stock bajo -->
<?php if (count($stats['productos_stock_bajo']) > 0): ?>
<div class="row">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Productos con Stock Bajo</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-end">Precio Venta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['productos_stock_bajo'] as $producto): ?>
                            <tr>
                                <td><strong><?php echo $producto['nombre']; ?></strong></td>
                                <td><?php echo $producto['categoria']; ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger"><?php echo $producto['stock_actual']; ?></span>
                                </td>
                                <td class="text-end"><?php echo formatMoney($producto['precio_venta']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'views/layouts/footer.php'; ?>
