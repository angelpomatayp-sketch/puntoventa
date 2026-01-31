<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Reporte de Inventario';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// MULTI-TENANT: Obtener productos con stock filtrados por tienda
$sql = "
    SELECT p.*, c.nombre as categoria
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.activo = TRUE
";
// Filtrar por tienda (excepto SUPER_ADMIN que ve todo)
if (!isSuperAdmin()) {
    $sql .= " AND p.tienda_id = " . getTiendaId();
}
$sql .= " ORDER BY p.categoria_id, p.nombre ASC";

$stmt = $db->query($sql);
$productos = $stmt->fetchAll();

// Calcular valor del inventario
$valor_costo = 0;
$valor_venta = 0;
$productos_bajo_stock = 0;

foreach ($productos as $producto) {
    $valor_costo += ($producto['precio_compra'] ?? 0) * $producto['stock_actual'];
    $valor_venta += $producto['precio_venta'] * $producto['stock_actual'];

    if ($producto['stock_actual'] <= $producto['stock_minimo']) {
        $productos_bajo_stock++;
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-box-seam"></i> Reporte de Inventario</h2>
            <a href="<?php echo e(BASE_URL); ?>/controllers/ReporteController.php?action=inventario_pdf"
               class="btn btn-danger" target="_blank">
                <i class="bi bi-file-pdf"></i> Exportar a PDF
            </a>
        </div>
    </div>
</div>

<!-- Resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Productos</h6>
                <h3><?php echo e(count($productos)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Valor Venta</h6>
                <h3><?php echo e(formatMoney($valor_venta)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Valor Costo</h6>
                <h3><?php echo e(formatMoney($valor_costo)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning">
            <div class="card-body text-center">
                <h6>Stock Bajo</h6>
                <h3><?php echo e($productos_bajo_stock); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Productos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Inventario Actual</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center">Stock Mín</th>
                                <th class="text-center">Precio Venta</th>
                                <th class="text-center">Costo</th>
                                <th class="text-center">Valor Total (Venta)</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><strong><?php echo e($producto['nombre']); ?></strong></td>
                                <td><?php echo e($producto['categoria']); ?></td>
                                <td class="text-center">
                                    <?php if ($producto['stock_actual'] <= $producto['stock_minimo']): ?>
                                        <span class="badge bg-danger"><?php echo e($producto['stock_actual']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?php echo e($producto['stock_actual']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo e($producto['stock_minimo']); ?></td>
                                <td class="text-center"><?php echo e(formatMoney($producto['precio_venta'])); ?></td>
                                <td class="text-center"><?php echo e(formatMoney($producto['precio_compra'] ?? 0)); ?></td>
                                <td class="text-center"><strong><?php echo e(formatMoney($producto['precio_venta'] * $producto['stock_actual'])); ?></strong></td>
                                <td class="text-center">
                                    <?php if ($producto['stock_actual'] <= $producto['stock_minimo']): ?>
                                        <i class="bi bi-exclamation-triangle text-danger" title="Stock bajo"></i>
                                    <?php else: ?>
                                        <i class="bi bi-check-circle text-success" title="OK"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="6">TOTALES</th>
                                <th class="text-end"><?php echo e(formatMoney($valor_venta)); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
