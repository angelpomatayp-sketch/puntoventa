<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Unidades de Medida';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Verificar si la tabla existe
try {
    $tableExists = $db->query("SHOW TABLES LIKE 'unidades_medida'")->rowCount() > 0;
} catch (PDOException $e) {
    $tableExists = false;
}

if (!$tableExists) {
    ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-warning">
                <h4><i class="bi bi-exclamation-triangle"></i> Tabla no encontrada</h4>
                <p>La tabla <code>unidades_medida</code> no existe en la base de datos.</p>
                <p>Ejecuta el script SQL ubicado en: <code>/sql/add_unidades_medida.sql</code></p>
                <hr>
                <p class="mb-0">Puedes ejecutarlo desde phpMyAdmin o desde la línea de comandos.</p>
            </div>
        </div>
    </div>
    <?php
    include '../layouts/footer.php';
    exit;
}

// Obtener filtro
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$busqueda = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

// Obtener unidades de medida
$sql = "
    SELECT
        um.*,
        COUNT(p.id) as productos_usando
    FROM unidades_medida um
    LEFT JOIN productos p ON um.id = p.unidad_medida_id
    WHERE 1=1
";

$params = [];

if ($filtro_estado !== '') {
    $sql .= " AND um.activo = :activo";
    $params['activo'] = intval($filtro_estado);
}

if ($busqueda) {
    $sql .= " AND (um.nombre LIKE :busqueda OR um.abreviatura LIKE :busqueda2)";
    $params['busqueda'] = "%{$busqueda}%";
    $params['busqueda2'] = "%{$busqueda}%";
}

$sql .= " GROUP BY um.id ORDER BY um.nombre ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$unidades = $stmt->fetchAll();

// Estadísticas
$total = count($unidades);
$activas = count(array_filter($unidades, fn($u) => $u['activo']));
$en_uso = count(array_filter($unidades, fn($u) => $u['productos_usando'] > 0));
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="bi bi-rulers"></i> Gestión de Unidades de Medida</h2>
        <p class="text-muted">Administra las unidades de medida disponibles para los productos</p>
    </div>
</div>

<!-- Filtros y acciones -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Buscar:</label>
                        <input type="text" class="form-control" name="q" value="<?php echo e($busqueda); ?>" placeholder="Nombre o abreviatura...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado:</label>
                        <select class="form-select" name="estado">
                            <option value="">Todos</option>
                            <option value="1" <?php echo e($filtro_estado === '1' ? 'selected' : ''); ?>>Activas</option>
                            <option value="0" <?php echo e($filtro_estado === '0' ? 'selected' : ''); ?>>Inactivas</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/unidades_medida.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <a href="<?php echo e(BASE_URL); ?>/views/super_admin/crear_unidad_medida.php" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-plus-circle"></i> Nueva Unidad de Medida
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Unidades</h6>
                <h3><?php echo e($total); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Activas</h6>
                <h3><?php echo e($activas); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>En Uso</h6>
                <h3><?php echo e($en_uso); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de unidades -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-list"></i> Listado de Unidades de Medida
                    <span class="badge bg-light text-dark ms-2"><?php echo e($total); ?> registros</span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($unidades) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Abreviatura</th>
                                <th>Descripción</th>
                                <th class="text-center">Productos</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unidades as $unidad): ?>
                            <tr>
                                <td><small class="text-muted">#<?php echo e($unidad['id']); ?></small></td>
                                <td><strong><?php echo e($unidad['nombre']); ?></strong></td>
                                <td>
                                    <span class="badge bg-secondary fs-6"><?php echo e($unidad['abreviatura']); ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo e($unidad['descripcion'] ?: '-'); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php if ($unidad['productos_usando'] > 0): ?>
                                    <span class="badge bg-info">
                                        <?php echo e($unidad['productos_usando']); ?> productos
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-light text-muted">Sin uso</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($unidad['activo']): ?>
                                        <span class="badge bg-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/editar_unidad_medida.php?id=<?php echo e($unidad['id']); ?>"
                                           class="btn btn-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <?php if ($unidad['activo']): ?>
                                        <a href="<?php echo e(BASE_URL); ?>/controllers/UnidadMedidaController.php?action=cambiar_estado&id=<?php echo e($unidad['id']); ?>&activo=0&csrf_token=<?php echo e(getCsrfToken()); ?>"
                                           class="btn btn-warning"
                                           onclick="return confirm('¿Desactivar esta unidad?')"
                                           title="Desactivar">
                                            <i class="bi bi-toggle-on"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="<?php echo e(BASE_URL); ?>/controllers/UnidadMedidaController.php?action=cambiar_estado&id=<?php echo e($unidad['id']); ?>&activo=1&csrf_token=<?php echo e(getCsrfToken()); ?>"
                                           class="btn btn-success"
                                           title="Activar">
                                            <i class="bi bi-toggle-off"></i>
                                        </a>
                                        <?php endif; ?>

                                        <?php if ($unidad['productos_usando'] == 0): ?>
                                        <a href="<?php echo e(BASE_URL); ?>/controllers/UnidadMedidaController.php?action=eliminar&id=<?php echo e($unidad['id']); ?>&csrf_token=<?php echo e(getCsrfToken()); ?>"
                                           class="btn btn-danger"
                                           onclick="return confirm('¿Eliminar esta unidad de medida? Esta acción no se puede deshacer.')"
                                           title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No se encontraron unidades de medida.
                    <?php if ($busqueda || $filtro_estado !== ''): ?>
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/unidades_medida.php">Ver todas</a>
                    <?php else: ?>
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/crear_unidad_medida.php">Crear la primera</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Leyenda -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Información</h6>
                <ul class="mb-0 small">
                    <li>Las unidades de medida se asignan a los productos para indicar cómo se venden.</li>
                    <li>No se pueden eliminar unidades que estén siendo utilizadas por productos.</li>
                    <li>Las unidades desactivadas no aparecerán en el selector de productos.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
