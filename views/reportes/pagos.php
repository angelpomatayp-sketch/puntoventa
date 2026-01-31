<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Reporte de Medios de Pago';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Procesar filtros
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-d');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

// Obtener pagos por medio
$stmt = $db->prepare("
    SELECT
        p.medio,
        COUNT(DISTINCT v.id) as cantidad_ventas,
        COALESCE(SUM(p.monto), 0) as total_monto
    FROM pagos p
    INNER JOIN ventas v ON p.venta_id = v.id
    WHERE DATE(v.fecha_hora) BETWEEN :desde AND :hasta
    AND v.estado = 'PAGADA'
    GROUP BY p.medio
");
$stmt->execute(['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
$pagos_resumen = $stmt->fetchAll();

// Obtener detalle de pagos
$stmt = $db->prepare("
    SELECT p.*, v.nro_ticket, v.fecha_hora, u.nombre as cajero
    FROM pagos p
    INNER JOIN ventas v ON p.venta_id = v.id
    INNER JOIN usuarios u ON v.usuario_id = u.id
    WHERE DATE(v.fecha_hora) BETWEEN :desde AND :hasta
    AND v.estado = 'PAGADA'
    ORDER BY v.fecha_hora DESC
");
$stmt->execute(['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
$pagos = $stmt->fetchAll();

// Calcular totales
$total_efectivo = 0;
$total_yape = 0;
$cantidad_efectivo = 0;
$cantidad_yape = 0;

foreach ($pagos_resumen as $resumen) {
    if ($resumen['medio'] === 'EFECTIVO') {
        $total_efectivo = $resumen['total_monto'];
        $cantidad_efectivo = $resumen['cantidad_ventas'];
    } else {
        $total_yape = $resumen['total_monto'];
        $cantidad_yape = $resumen['cantidad_ventas'];
    }
}

$total_general = $total_efectivo + $total_yape;
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-credit-card"></i> Reporte de Medios de Pago</h2>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="desde" class="form-label">Fecha Desde</label>
                        <input type="date" class="form-control" id="desde" name="desde" value="<?php echo e($fecha_desde); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="hasta" class="form-label">Fecha Hasta</label>
                        <input type="date" class="form-control" id="hasta" name="hasta" value="<?php echo e($fecha_hasta); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-cash"></i> Efectivo</h6>
                <h4><?php echo e(formatMoney($total_efectivo)); ?></h4>
                <small><?php echo e($cantidad_efectivo); ?> ventas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-phone"></i> Yape</h6>
                <h4><?php echo e(formatMoney($total_yape)); ?></h4>
                <small><?php echo e($cantidad_yape); ?> ventas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-calculator"></i> Total General</h6>
                <h4><?php echo e(formatMoney($total_general)); ?></h4>
                <small><?php echo e(($cantidad_efectivo + $cantidad_yape)); ?> ventas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-percent"></i> Distribución</h6>
                <h5>
                    <?php
                    $porcentaje_efectivo = $total_general > 0 ? ($total_efectivo / $total_general) * 100 : 0;
                    $porcentaje_yape = $total_general > 0 ? ($total_yape / $total_general) * 100 : 0;
                    echo round($porcentaje_efectivo, 1) . '% / ' . round($porcentaje_yape, 1) . '%';
                    ?>
                </h5>
                <small>Efectivo / Yape</small>
            </div>
        </div>
    </div>
</div>

<!-- Botón Exportar PDF -->
<div class="row mb-3">
    <div class="col-12 text-end">
        <a href="<?php echo e(BASE_URL); ?>/controllers/ReporteController.php?action=pagos_pdf&desde=<?php echo e($fecha_desde); ?>&hasta=<?php echo e($fecha_hasta); ?>"
           class="btn btn-danger" target="_blank">
            <i class="bi bi-file-pdf"></i> Exportar a PDF
        </a>
    </div>
</div>

<!-- Tabla de Pagos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Detalle de Pagos</h5>
            </div>
            <div class="card-body">
                <?php if (count($pagos) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Fecha/Hora</th>
                                <th>Cajero</th>
                                <th>Medio</th>
                                <th>Monto</th>
                                <th>Ref/Recibido</th>
                                <th>Vuelto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td><strong><?php echo e($pago['nro_ticket']); ?></strong></td>
                                <td><?php echo e(formatDate($pago['fecha_hora'])); ?></td>
                                <td><?php echo e($pago['cajero']); ?></td>
                                <td>
                                    <?php if ($pago['medio'] === 'EFECTIVO'): ?>
                                        <span class="badge bg-success"><i class="bi bi-cash"></i> Efectivo</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><i class="bi bi-phone"></i> Yape</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><strong><?php echo e(formatMoney($pago['monto'])); ?></strong></td>
                                <td class="text-end">
                                    <?php if ($pago['medio'] === 'EFECTIVO'): ?>
                                        <?php echo e(formatMoney($pago['monto_recibido'])); ?>
                                    <?php else: ?>
                                        <small><?php echo e($pago['ref_operacion']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($pago['medio'] === 'EFECTIVO'): ?>
                                        <?php echo e(formatMoney($pago['vuelto'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="4">TOTALES</th>
                                <th class="text-end"><?php echo e(formatMoney($total_general)); ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> No se encontraron pagos en el rango seleccionado.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
