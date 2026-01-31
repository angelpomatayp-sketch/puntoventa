<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Dashboard Global - Super Administrador';
include '../layouts/header.php';

// Estadísticas globales
$db = Database::getInstance()->getConnection();

// Total de tiendas
$stmt = $db->query("SELECT COUNT(*) as total FROM tiendas");
$total_tiendas = $stmt->fetch()['total'];

// Tiendas activas
$stmt = $db->query("SELECT COUNT(*) as total FROM tiendas WHERE estado = 'ACTIVA'");
$tiendas_activas = $stmt->fetch()['total'];

// Total de usuarios (sin contar super admins)
$stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol != 'SUPER_ADMINISTRADOR'");
$total_usuarios = $stmt->fetch()['total'];

// Total de ventas (todas las tiendas)
$stmt = $db->query("SELECT COUNT(*) as total, SUM(total) as monto FROM ventas WHERE estado = 'PAGADA'");
$ventas_global = $stmt->fetch();

// Ventas del día
$stmt = $db->query("SELECT COUNT(*) as total, SUM(total) as monto FROM ventas WHERE estado = 'PAGADA' AND DATE(fecha_hora) = CURDATE()");
$ventas_hoy = $stmt->fetch();

// Tiendas recientes (últimas 5)
$stmt = $db->query("SELECT * FROM tiendas ORDER BY created_at DESC LIMIT 5");
$tiendas_recientes = $stmt->fetchAll();

// Top 5 tiendas por ventas del mes
$stmt = $db->query("
    SELECT t.nombre_negocio, t.slug, COUNT(v.id) as total_ventas, SUM(v.total) as monto_ventas
    FROM tiendas t
    LEFT JOIN ventas v ON t.id = v.tienda_id AND MONTH(v.fecha_hora) = MONTH(CURDATE()) AND YEAR(v.fecha_hora) = YEAR(CURDATE()) AND v.estado = 'PAGADA'
    GROUP BY t.id
    ORDER BY monto_ventas DESC
    LIMIT 5
");
$top_tiendas = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-speedometer2"></i> Dashboard Global</h2>
        <p class="text-muted">Vista general del sistema multi-tenant</p>
    </div>
</div>

<!-- Tarjetas de Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Tiendas</h6>
                        <h2 class="mb-0"><?php echo e($total_tiendas); ?></h2>
                    </div>
                    <i class="bi bi-shop-window" style="font-size: 2.5rem;"></i>
                </div>
                <small><?php echo e($tiendas_activas); ?> activas</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Usuarios Totales</h6>
                        <h2 class="mb-0"><?php echo e($total_usuarios); ?></h2>
                    </div>
                    <i class="bi bi-people-fill" style="font-size: 2.5rem;"></i>
                </div>
                <small>En todas las tiendas</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Ventas Hoy</h6>
                        <h2 class="mb-0"><?php echo e($ventas_hoy['total'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-cart-check-fill" style="font-size: 2.5rem;"></i>
                </div>
                <small><?php echo e(formatMoney($ventas_hoy['monto'] ?? 0)); ?></small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Ventas Totales</h6>
                        <h2 class="mb-0"><?php echo e($ventas_global['total'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-graph-up-arrow" style="font-size: 2.5rem;"></i>
                </div>
                <small><?php echo e(formatMoney($ventas_global['monto'] ?? 0)); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Contenido Principal -->
<div class="row">
    <!-- Top 5 Tiendas por Ventas -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-trophy-fill"></i> Top 5 Tiendas del Mes</h5>
            </div>
            <div class="card-body">
                <?php if (count($top_tiendas) > 0): ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tienda</th>
                            <th class="text-end">Ventas</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_tiendas as $index => $tienda): ?>
                        <tr>
                            <td>
                                <?php if ($index === 0): ?>
                                    <i class="bi bi-trophy-fill text-warning"></i>
                                <?php else: ?>
                                    <?php echo e($index + 1); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo e($tienda['nombre_negocio']); ?></strong>
                                <br><small class="text-muted"><?php echo e($tienda['slug']); ?></small>
                            </td>
                            <td class="text-end"><?php echo e($tienda['total_ventas'] ?? 0); ?></td>
                            <td class="text-end"><strong><?php echo e(formatMoney($tienda['monto_ventas'] ?? 0)); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted text-center">No hay datos de ventas este mes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tiendas Recientes -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Tiendas Recientes</h5>
            </div>
            <div class="card-body">
                <?php if (count($tiendas_recientes) > 0): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($tiendas_recientes as $tienda): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0"><?php echo e($tienda['nombre_negocio']); ?></h6>
                            <small class="text-muted">
                                <i class="bi bi-calendar-event"></i> <?php echo e(formatDate($tienda['created_at'])); ?>
                            </small>
                        </div>
                        <div>
                            <?php if ($tienda['estado'] === 'ACTIVA'): ?>
                                <span class="badge bg-success">ACTIVA</span>
                            <?php else: ?>
                                <span class="badge bg-danger">SUSPENDIDA</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No hay tiendas registradas</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Acciones Rápidas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/crear_tienda.php" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-plus-circle"></i><br>
                            <span>Nueva Tienda</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php" class="btn btn-success w-100 btn-lg">
                            <i class="bi bi-shop-window"></i><br>
                            <span>Ver Todas las Tiendas</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/reportes.php" class="btn btn-info w-100 btn-lg">
                            <i class="bi bi-file-earmark-bar-graph"></i><br>
                            <span>Reportes Globales</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo e(BASE_URL); ?>/views/admin/usuarios.php" class="btn btn-warning w-100 btn-lg">
                            <i class="bi bi-people"></i><br>
                            <span>Gestión de Usuarios</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
