<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Categorías Globales';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener filtro de tipo de negocio
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Obtener categorías globales
$sql = "
    SELECT
        c.*,
        COUNT(DISTINCT tc.tienda_id) as tiendas_asignadas,
        COUNT(DISTINCT p.id) as productos_usando
    FROM categorias c
    LEFT JOIN tienda_categorias tc ON c.id = tc.categoria_id AND tc.activo = 1
    LEFT JOIN productos p ON c.id = p.categoria_id AND p.activo = 1
    WHERE c.es_global = 1
";

if ($filtro_tipo) {
    $sql .= " AND c.tipo_negocio = :tipo";
}

$sql .= " GROUP BY c.id ORDER BY c.tipo_negocio, c.nombre ASC";

$stmt = $db->prepare($sql);
if ($filtro_tipo) {
    $stmt->execute(['tipo' => $filtro_tipo]);
} else {
    $stmt->execute();
}
$categorias = $stmt->fetchAll();

// Agrupar por tipo de negocio
$categorias_por_tipo = [];
foreach ($categorias as $cat) {
    $categorias_por_tipo[$cat['tipo_negocio']][] = $cat;
}
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="bi bi-tags"></i> Gestión de Categorías Globales</h2>
        <p class="text-muted">Administra las categorías que las tiendas pueden usar</p>
    </div>
</div>

<!-- Filtros y acciones -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Filtrar por Tipo de Negocio:</label>
                        <select class="form-select" name="tipo">
                            <option value="">Todos los tipos</option>
                            <option value="ALIMENTOS" <?php echo e($filtro_tipo === 'ALIMENTOS' ? 'selected' : ''); ?>>Alimentos</option>
                            <option value="FERRETERIA" <?php echo e($filtro_tipo === 'FERRETERIA' ? 'selected' : ''); ?>>Ferretería</option>
                            <option value="FARMACIA" <?php echo e($filtro_tipo === 'FARMACIA' ? 'selected' : ''); ?>>Farmacia</option>
                            <option value="ROPA" <?php echo e($filtro_tipo === 'ROPA' ? 'selected' : ''); ?>>Ropa</option>
                            <option value="TECNOLOGIA" <?php echo e($filtro_tipo === 'TECNOLOGIA' ? 'selected' : ''); ?>>Tecnología</option>
                            <option value="LIMPIEZA" <?php echo e($filtro_tipo === 'LIMPIEZA' ? 'selected' : ''); ?>>Limpieza</option>
                            <option value="LIBRERIA" <?php echo e($filtro_tipo === 'LIBRERIA' ? 'selected' : ''); ?>>Librería</option>
                            <option value="MASCOTAS" <?php echo e($filtro_tipo === 'MASCOTAS' ? 'selected' : ''); ?>>Mascotas</option>
                            <option value="VARIADO" <?php echo e($filtro_tipo === 'VARIADO' ? 'selected' : ''); ?>>Variado</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/categorias_globales.php" class="btn btn-secondary">
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
                <a href="<?php echo e(BASE_URL); ?>/views/super_admin/crear_categoria.php" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-plus-circle"></i> Nueva Categoría Global
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Categorías</h6>
                <h3><?php echo e(count($categorias)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Activas</h6>
                <h3><?php echo e(count(array_filter($categorias, fn($c) => $c['activo']))); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Tipos de Negocio</h6>
                <h3><?php echo e(count($categorias_por_tipo)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning">
            <div class="card-body text-center">
                <h6>En Uso</h6>
                <h3><?php echo e(count(array_filter($categorias, fn($c) => $c['productos_usando'] > 0))); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Categorías agrupadas por tipo -->
<?php if (count($categorias) > 0): ?>
    <?php foreach ($categorias_por_tipo as $tipo => $cats): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-folder"></i>
                        <?php
                        $tipo_nombres = [
                            'ALIMENTOS' => 'Alimentos',
                            'FERRETERIA' => 'Ferretería',
                            'FARMACIA' => 'Farmacia',
                            'ROPA' => 'Ropa y Accesorios',
                            'TECNOLOGIA' => 'Tecnología',
                            'LIMPIEZA' => 'Limpieza',
                            'LIBRERIA' => 'Librería',
                            'MASCOTAS' => 'Mascotas',
                            'VARIADO' => 'Variado'
                        ];
                        echo $tipo_nombres[$tipo] ?? $tipo;
                        ?>
                        <span class="badge bg-light text-dark ms-2"><?php echo e(count($cats)); ?> categorías</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Descripción</th>
                                    <th class="text-center">Tiendas Asignadas</th>
                                    <th class="text-center">Productos</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cats as $cat): ?>
                                <tr>
                                    <td><strong><?php echo e($cat['nombre']); ?></strong></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo e($cat['descripcion'] ?: '-'); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?php echo e($cat['tiendas_asignadas']); ?> tiendas
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            <?php echo e($cat['productos_usando']); ?> productos
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($cat['activo']): ?>
                                            <span class="badge bg-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo e(BASE_URL); ?>/views/super_admin/editar_categoria.php?id=<?php echo e($cat['id']); ?>"
                                               class="btn btn-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <?php if ($cat['activo']): ?>
                                            <a href="<?php echo e(BASE_URL); ?>/controllers/CategoriaGlobalController.php?action=cambiar_estado&id=<?php echo e($cat['id']); ?>&activo=0&csrf_token=<?php echo e(getCsrfToken()); ?>"
                                               class="btn btn-warning"
                                               onclick="return confirm('¿Desactivar esta categoría?')"
                                               title="Desactivar">
                                                <i class="bi bi-toggle-on"></i>
                                            </a>
                                            <?php else: ?>
                                            <a href="<?php echo e(BASE_URL); ?>/controllers/CategoriaGlobalController.php?action=cambiar_estado&id=<?php echo e($cat['id']); ?>&activo=1&csrf_token=<?php echo e(getCsrfToken()); ?>"
                                               class="btn btn-success"
                                               title="Activar">
                                                <i class="bi bi-toggle-off"></i>
                                            </a>
                                            <?php endif; ?>

                                            <?php if ($cat['productos_usando'] == 0): ?>
                                            <a href="<?php echo e(BASE_URL); ?>/controllers/CategoriaGlobalController.php?action=eliminar&id=<?php echo e($cat['id']); ?>&csrf_token=<?php echo e(getCsrfToken()); ?>"
                                               class="btn btn-danger"
                                               onclick="return confirm('¿Eliminar esta categoría? Esta acción no se puede deshacer.')"
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
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No hay categorías globales creadas.
            <a href="<?php echo e(BASE_URL); ?>/views/super_admin/crear_categoria.php">Crear la primera categoría</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>
