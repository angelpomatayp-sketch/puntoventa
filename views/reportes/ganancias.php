<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Reporte de Ganancias';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Default date range: today
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-d');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

$reporte_data = [];
if (isset($_GET['action']) && $_GET['action'] == 'generar') {
    $sql = "
        SELECT
            p.nombre AS producto_nombre,
            COALESCE(p.precio_compra, 0) AS producto_costo,
            SUM(dv.cantidad) AS unidades_vendidas,
            SUM(dv.subtotal) AS ingreso_total,
            (COALESCE(p.precio_compra, 0) * SUM(dv.cantidad)) AS costo_total,
            (SUM(dv.subtotal) - (COALESCE(p.precio_compra, 0) * SUM(dv.cantidad))) AS ganancia_total
        FROM detalle_venta dv
        JOIN ventas v ON dv.venta_id = v.id
        JOIN productos p ON dv.producto_id = p.id
        WHERE v.estado = 'PAGADA' AND DATE(v.fecha_hora) BETWEEN :desde AND :hasta
    ";

    if (!isSuperAdmin()) {
        $sql .= " AND v.tienda_id = :tienda_id";
    }

    $sql .= "
        GROUP BY p.id, p.nombre, p.precio_compra
        ORDER BY ganancia_total DESC
    ";

    $stmt = $db->prepare($sql);
    $params = ['desde' => $fecha_desde, 'hasta' => $fecha_hasta];
    if (!isSuperAdmin()) {
        $params['tienda_id'] = getTiendaId();
    }
    $stmt->execute($params);
    $reporte_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-graph-up-arrow"></i> Reporte de Ganancias</h2>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="action" value="generar">
            <div class="col-md-4">
                <label for="desde" class="form-label">Desde:</label>
                <input type="date" id="desde" name="desde" class="form-control" value="<?php echo e($fecha_desde); ?>">
            </div>
            <div class="col-md-4">
                <label for="hasta" class="form-label">Hasta:</label>
                <input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo e($fecha_hasta); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Generar Reporte</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($reporte_data)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Unidades Vendidas</th>
                                <th class="text-end">Ingreso Total</th>
                                <th class="text-end">Costo Total</th>
                                <th class="text-end">Ganancia Neta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_ingresos = 0;
                            $total_costos = 0;
                            $total_ganancias = 0;
                            foreach ($reporte_data as $item): 
                                $total_ingresos += $item['ingreso_total'];
                                $total_costos += $item['costo_total'];
                                $total_ganancias += $item['ganancia_total'];
                            ?>
                            <tr>
                                <td><?php echo e(htmlspecialchars($item['producto_nombre'])); ?></td>
                                <td class="text-center"><?php echo e($item['unidades_vendidas']); ?></td>
                                <td class="text-end"><?php echo e(formatMoney($item['ingreso_total'])); ?></td>
                                <td class="text-end"><?php echo e(formatMoney($item['costo_total'])); ?></td>
                                <td class="text-end fw-bold <?php echo e(($item['ganancia_total'] >= 0) ? 'text-success' : 'text-danger'); ?>">
                                    <?php echo e(formatMoney($item['ganancia_total'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <th class="text-end" colspan="2">TOTALES GENERALES</th>
                                <th class="text-end"><?php echo e(formatMoney($total_ingresos)); ?></th>
                                <th class="text-end"><?php echo e(formatMoney($total_costos)); ?></th>
                                <th class="text-end fw-bold fs-5"><?php echo e(formatMoney($total_ganancias)); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php elseif (isset($_GET['action'])): ?>
<div class="alert alert-info">No se encontraron datos de ventas para el período seleccionado.</div>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>
