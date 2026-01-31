<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Reportes Globales';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener filtros (por defecto: mes actual)
$fecha_desde = $_GET['desde'] ?? date('Y-m-01'); // Primer día del mes
$fecha_hasta = $_GET['hasta'] ?? date('Y-m-t');  // Último día del mes

// ========================================
// 1. INGRESOS POR SUSCRIPCIONES
// ========================================
$stmt = $db->prepare("
    SELECT
        COUNT(*) as total_pagos,
        SUM(monto) as total_ingresos,
        AVG(monto) as promedio_pago
    FROM pagos_suscripcion
    WHERE fecha_pago BETWEEN :desde AND :hasta
");
$stmt->execute(['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
$ingresos_suscripcion = $stmt->fetch();

// ========================================
// 2. ESTADO DE TIENDAS
// ========================================
$stmt = $db->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'ACTIVA' THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN estado = 'SUSPENDIDA' THEN 1 ELSE 0 END) as suspendidas
    FROM tiendas
");
$estado_tiendas = $stmt->fetch();

// ========================================
// 3. PRÓXIMOS VENCIMIENTOS (15 DÍAS)
// ========================================
$stmt = $db->query("
    SELECT
        id, nombre_negocio, fecha_proximo_pago, monto_mensual,
        DATEDIFF(fecha_proximo_pago, CURDATE()) as dias_restantes
    FROM tiendas
    WHERE estado = 'ACTIVA'
    AND fecha_proximo_pago IS NOT NULL
    AND fecha_proximo_pago <= DATE_ADD(CURDATE(), INTERVAL 15 DAY)
    ORDER BY fecha_proximo_pago ASC
    LIMIT 10
");
$proximos_vencimientos = $stmt->fetchAll();

// ========================================
// 4. INGRESOS POR PLAN
// ========================================
$stmt = $db->query("
    SELECT
        plan,
        COUNT(*) as cantidad_tiendas,
        SUM(monto_mensual) as ingresos_mensuales
    FROM tiendas
    WHERE estado = 'ACTIVA'
    GROUP BY plan
    ORDER BY ingresos_mensuales DESC
");
$ingresos_por_plan = $stmt->fetchAll();

// ========================================
// 5. HISTORIAL DE PAGOS (PERÍODO ACTUAL)
// ========================================
$stmt = $db->prepare("
    SELECT
        ps.*,
        t.nombre_negocio,
        t.plan,
        u.nombre as registrado_por_nombre
    FROM pagos_suscripcion ps
    INNER JOIN tiendas t ON ps.tienda_id = t.id
    INNER JOIN usuarios u ON ps.registrado_por = u.id
    WHERE ps.fecha_pago BETWEEN :desde AND :hasta
    ORDER BY ps.fecha_pago DESC
    LIMIT 50
");
$stmt->execute(['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
$historial_pagos = $stmt->fetchAll();

// ========================================
// 6. TIENDAS MÁS RENTABLES (POR MONTO MENSUAL)
// ========================================
$stmt = $db->query("
    SELECT
        id,
        nombre_negocio,
        plan,
        monto_mensual,
        fecha_activacion,
        DATEDIFF(CURDATE(), fecha_activacion) as dias_activa
    FROM tiendas
    WHERE estado = 'ACTIVA'
    ORDER BY monto_mensual DESC
    LIMIT 10
");
$tiendas_rentables = $stmt->fetchAll();

// ========================================
// 7. PROYECCIÓN DE INGRESOS MENSUALES
// ========================================
$stmt = $db->query("
    SELECT
        SUM(monto_mensual) as ingreso_mensual_proyectado
    FROM tiendas
    WHERE estado = 'ACTIVA'
");
$proyeccion = $stmt->fetch();

// Calcular totales
$total_pagos = $ingresos_suscripcion['total_pagos'] ?? 0;
$total_ingresos = $ingresos_suscripcion['total_ingresos'] ?? 0;
$promedio_pago = $ingresos_suscripcion['promedio_pago'] ?? 0;
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="bi bi-graph-up-arrow"></i> Reportes Globales del Sistema</h2>
        <p class="text-muted">Panel de control del Super Administrador</p>
    </div>
</div>

<!-- Filtros de Fecha -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Desde:</label>
                        <input type="date" class="form-control" name="desde" value="<?php echo e($fecha_desde); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Hasta:</label>
                        <input type="date" class="form-control" name="hasta" value="<?php echo e($fecha_hasta); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/reportes.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Este Mes
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tarjetas de Resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-cash-coin"></i> Ingresos del Período</h6>
                <h3><?php echo e(formatMoney($total_ingresos)); ?></h3>
                <small><?php echo e($total_pagos); ?> pagos recibidos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-shop-window"></i> Tiendas Activas</h6>
                <h3><?php echo e($estado_tiendas['activas']); ?></h3>
                <small>de <?php echo e($estado_tiendas['total']); ?> totales</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-graph-up"></i> Ingreso Mensual Proyectado</h6>
                <h3><?php echo e(formatMoney($proyeccion['ingreso_mensual_proyectado'])); ?></h3>
                <small>Basado en tiendas activas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning">
            <div class="card-body text-center">
                <h6><i class="bi bi-exclamation-triangle"></i> Vencimientos Próximos</h6>
                <h3><?php echo e(count($proximos_vencimientos)); ?></h3>
                <small>En los próximos 15 días</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Ingresos por Plan -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Ingresos por Plan</h5>
            </div>
            <div class="card-body">
                <?php if (count($ingresos_por_plan) > 0): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th class="text-center">Tiendas</th>
                            <th class="text-end">Ingreso Mensual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingresos_por_plan as $plan): ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary"><?php echo e($plan['plan']); ?></span>
                            </td>
                            <td class="text-center"><?php echo e($plan['cantidad_tiendas']); ?></td>
                            <td class="text-end"><strong><?php echo e(formatMoney($plan['ingresos_mensuales'])); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted text-center mb-0">No hay datos disponibles</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Próximos Vencimientos -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Próximos Vencimientos</h5>
            </div>
            <div class="card-body">
                <?php if (count($proximos_vencimientos) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tienda</th>
                                <th>Vence</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximos_vencimientos as $tienda): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo e(BASE_URL); ?>/views/super_admin/editar_tienda.php?id=<?php echo e($tienda['id']); ?>">
                                        <?php echo e($tienda['nombre_negocio']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $dias = $tienda['dias_restantes'];
                                    $clase = $dias <= 3 ? 'danger' : ($dias <= 7 ? 'warning' : 'info');
                                    ?>
                                    <span class="badge bg-<?php echo e($clase); ?>">
                                        <?php echo e(date('d/m/Y', strtotime($tienda['fecha_proximo_pago']))); ?>
                                        (<?php echo e($dias); ?> días)
                                    </span>
                                </td>
                                <td class="text-end"><?php echo e(formatMoney($tienda['monto_mensual'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center mb-0">No hay vencimientos próximos</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tiendas Más Rentables -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Top 10 Tiendas por Monto Mensual</h5>
            </div>
            <div class="card-body">
                <?php if (count($tiendas_rentables) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tienda</th>
                                <th>Plan</th>
                                <th class="text-end">Monto Mensual</th>
                                <th>Fecha Activación</th>
                                <th class="text-center">Días Activa</th>
                                <th class="text-end">Ingreso Total Estimado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $posicion = 1;
                            foreach ($tiendas_rentables as $tienda):
                                $meses_activa = ceil($tienda['dias_activa'] / 30);
                                $ingreso_estimado = $tienda['monto_mensual'] * $meses_activa;
                            ?>
                            <tr>
                                <td><strong><?php echo e($posicion++); ?></strong></td>
                                <td>
                                    <a href="<?php echo e(BASE_URL); ?>/views/super_admin/editar_tienda.php?id=<?php echo e($tienda['id']); ?>">
                                        <?php echo e($tienda['nombre_negocio']); ?>
                                    </a>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo e($tienda['plan']); ?></span></td>
                                <td class="text-end"><strong><?php echo e(formatMoney($tienda['monto_mensual'])); ?></strong></td>
                                <td><?php echo e(date('d/m/Y', strtotime($tienda['fecha_activacion']))); ?></td>
                                <td class="text-center"><?php echo e($tienda['dias_activa']); ?></td>
                                <td class="text-end text-success"><?php echo e(formatMoney($ingreso_estimado)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center mb-0">No hay tiendas activas</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Historial de Pagos del Período -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Pagos (<?php echo e(date('d/m/Y', strtotime($fecha_desde))); ?> - <?php echo e(date('d/m/Y', strtotime($fecha_hasta))); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($historial_pagos) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Fecha Pago</th>
                                <th>Tienda</th>
                                <th>Plan</th>
                                <th class="text-end">Monto</th>
                                <th>Método</th>
                                <th>Período</th>
                                <th>Registrado Por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial_pagos as $pago): ?>
                            <tr>
                                <td><?php echo e(date('d/m/Y', strtotime($pago['fecha_pago']))); ?></td>
                                <td>
                                    <a href="<?php echo e(BASE_URL); ?>/views/super_admin/editar_tienda.php?id=<?php echo e($pago['tienda_id']); ?>">
                                        <?php echo e($pago['nombre_negocio']); ?>
                                    </a>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo e($pago['plan']); ?></span></td>
                                <td class="text-end"><strong><?php echo e(formatMoney($pago['monto'])); ?></strong></td>
                                <td>
                                    <?php
                                    $iconos = [
                                        'EFECTIVO' => 'cash',
                                        'TRANSFERENCIA' => 'bank',
                                        'YAPE' => 'phone',
                                        'PLIN' => 'phone',
                                        'TARJETA' => 'credit-card'
                                    ];
                                    $icono = $iconos[$pago['metodo_pago']] ?? 'wallet';
                                    ?>
                                    <i class="bi bi-<?php echo e($icono); ?>"></i> <?php echo e($pago['metodo_pago']); ?>
                                </td>
                                <td>
                                    <small>
                                        <?php echo e(date('d/m/Y', strtotime($pago['periodo_desde']))); ?> -
                                        <?php echo e(date('d/m/Y', strtotime($pago['periodo_hasta']))); ?>
                                    </small>
                                </td>
                                <td><?php echo e($pago['registrado_por_nombre']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="3">TOTAL DEL PERÍODO</th>
                                <th class="text-end"><?php echo e(formatMoney($total_ingresos)); ?></th>
                                <th colspan="3"><?php echo e($total_pagos); ?> pagos</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> No hay pagos registrados en este período.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
