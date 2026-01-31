<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Detalle de Tienda';
include '../layouts/header.php';

// Obtener tienda
$tienda_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM tiendas WHERE id = :id");
$stmt->execute(['id' => $tienda_id]);
$tienda = $stmt->fetch();

if (!$tienda) {
    setFlashMessage('Tienda no encontrada', 'danger');
    redirect('/views/super_admin/tiendas.php');
    exit;
}

// Estadísticas de la tienda
$stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE tienda_id = :id");
$stmt->execute(['id' => $tienda_id]);
$total_usuarios = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM productos WHERE tienda_id = :id AND activo = TRUE");
$stmt->execute(['id' => $tienda_id]);
$total_productos = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM clientes WHERE tienda_id = :id AND activo = TRUE");
$stmt->execute(['id' => $tienda_id]);
$total_clientes = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(total) as monto FROM ventas WHERE tienda_id = :id AND estado = 'PAGADA'");
$stmt->execute(['id' => $tienda_id]);
$ventas_totales = $stmt->fetch();

$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(total) as monto FROM ventas WHERE tienda_id = :id AND estado = 'PAGADA' AND DATE(fecha_hora) = CURDATE()");
$stmt->execute(['id' => $tienda_id]);
$ventas_hoy = $stmt->fetch();

$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(total) as monto FROM ventas WHERE tienda_id = :id AND estado = 'PAGADA' AND MONTH(fecha_hora) = MONTH(CURDATE()) AND YEAR(fecha_hora) = YEAR(CURDATE())");
$stmt->execute(['id' => $tienda_id]);
$ventas_mes = $stmt->fetch();

// Últimas ventas
$stmt = $db->prepare("
    SELECT v.*, u.nombre as cajero, c.nombre as cliente
    FROM ventas v
    INNER JOIN usuarios u ON v.usuario_id = u.id
    INNER JOIN clientes c ON v.cliente_id = c.id
    WHERE v.tienda_id = :id AND v.estado = 'PAGADA'
    ORDER BY v.fecha_hora DESC
    LIMIT 10
");
$stmt->execute(['id' => $tienda_id]);
$ultimas_ventas = $stmt->fetchAll();

// Usuarios de la tienda
$stmt = $db->prepare("SELECT * FROM usuarios WHERE tienda_id = :id ORDER BY created_at DESC");
$stmt->execute(['id' => $tienda_id]);
$usuarios = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="bi bi-shop-window"></i> <?php echo e($tienda['nombre_negocio']); ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo e(BASE_URL); ?>/views/super_admin/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php">Tiendas</a></li>
                <li class="breadcrumb-item active"><?php echo e($tienda['nombre_negocio']); ?></li>
            </ol>
        </nav>
    </div>
    <div class="col-md-4 text-end">
        <?php if ($tienda['estado'] === 'ACTIVA'): ?>
            <span class="badge bg-success" style="font-size: 1rem;">ACTIVA</span>
        <?php else: ?>
            <span class="badge bg-danger" style="font-size: 1rem;">SUSPENDIDA</span>
        <?php endif; ?>
        <br>
        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/editar_tienda.php?id=<?php echo e($tienda['id']); ?>"
           class="btn btn-warning mt-2">
            <i class="bi bi-pencil"></i> Editar Tienda
        </a>
    </div>
</div>

<!-- Información de la Tienda -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-building"></i> Información del Negocio</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>RUC:</strong> <?php echo e($tienda['ruc']); ?></p>
                        <p><strong>Dirección:</strong> <?php echo e($tienda['direccion']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo e($tienda['telefono'] ?: 'No especificado'); ?></p>
                        <p><strong>Email:</strong> <?php echo e($tienda['email'] ?: 'No especificado'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Slug:</strong> <code><?php echo e($tienda['slug']); ?></code></p>
                        <p><strong>IGV:</strong> <?php echo e($tienda['igv']); ?>%</p>
                        <p><strong>Fecha Activación:</strong> <?php echo e($tienda['fecha_activacion'] ? date('d/m/Y', strtotime($tienda['fecha_activacion'])) : 'N/A'); ?></p>
                        <p><strong>Creada:</strong> <?php echo e(formatDate($tienda['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-image"></i> Logo</h5>
            </div>
            <div class="card-body text-center">
                <?php if ($tienda['logo']): ?>
                    <img src="<?php echo e(getLogoUrl($tienda['logo'])); ?>"
                         alt="Logo"
                         class="img-thumbnail"
                         style="max-width: 200px; max-height: 150px; object-fit: contain;">
                <?php else: ?>
                    <p class="text-muted">Sin logo personalizado</p>
                    <i class="bi bi-image" style="font-size: 3rem; color: #ccc;"></i>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-12">
        <h4><i class="bi bi-graph-up"></i> Estadísticas</h4>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6>Usuarios</h6>
                <h2><?php echo e($total_usuarios); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Productos Activos</h6>
                <h2><?php echo e($total_productos); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6>Clientes</h6>
                <h2><?php echo e($total_clientes); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h6>Ventas Totales</h6>
                <h2><?php echo e($ventas_totales['total'] ?? 0); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Ventas Hoy</h6>
                <h3><?php echo e(formatMoney($ventas_hoy['monto'] ?? 0)); ?></h3>
                <small class="text-muted"><?php echo e($ventas_hoy['total'] ?? 0); ?> ventas</small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Ventas del Mes</h6>
                <h3><?php echo e(formatMoney($ventas_mes['monto'] ?? 0)); ?></h3>
                <small class="text-muted"><?php echo e($ventas_mes['total'] ?? 0); ?> ventas</small>
            </div>
        </div>
    </div>
</div>

<!-- Usuarios de la Tienda -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Usuarios (<?php echo e(count($usuarios)); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($usuarios) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $user): ?>
                            <tr>
                                <td><?php echo e($user['nombre']); ?></td>
                                <td><?php echo e($user['usuario']); ?></td>
                                <td>
                                    <?php if ($user['rol'] === 'ADMINISTRADOR'): ?>
                                        <span class="badge bg-danger">ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">CAJERO</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No hay usuarios</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Últimas Ventas -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Últimas Ventas</h5>
            </div>
            <div class="card-body">
                <?php if (count($ultimas_ventas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Cajero</th>
                                <th>Total</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas_ventas as $venta): ?>
                            <tr>
                                <td><small><?php echo e($venta['nro_ticket']); ?></small></td>
                                <td><small><?php echo e($venta['cajero']); ?></small></td>
                                <td><strong><?php echo e(formatMoney($venta['total'])); ?></strong></td>
                                <td><small><?php echo e(date('d/m H:i', strtotime($venta['fecha_hora']))); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No hay ventas registradas</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Botones de Acción -->
<div class="row">
    <div class="col-12">
        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Lista de Tiendas
        </a>
        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/editar_tienda.php?id=<?php echo e($tienda['id']); ?>"
           class="btn btn-warning">
            <i class="bi bi-pencil"></i> Editar Tienda
        </a>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
