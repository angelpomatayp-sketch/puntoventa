<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Editar Categoría';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener categoría
if (!isset($_GET['id'])) {
    redirect('/views/super_admin/categorias_globales.php');
}

$id = intval($_GET['id']);

$stmt = $db->prepare("
    SELECT c.*,
           COUNT(DISTINCT tc.tienda_id) as tiendas_asignadas,
           COUNT(DISTINCT p.id) as productos_usando
    FROM categorias c
    LEFT JOIN tienda_categorias tc ON c.id = tc.categoria_id
    LEFT JOIN productos p ON c.id = p.categoria_id
    WHERE c.id = :id AND c.es_global = 1
    GROUP BY c.id
");
$stmt->execute(['id' => $id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    setFlashMessage('Categoría no encontrada', 'danger');
    redirect('/views/super_admin/categorias_globales.php');
}

// Obtener tiendas que usan esta categoría
$stmt = $db->prepare("
    SELECT t.id, t.nombre_negocio
    FROM tiendas t
    INNER JOIN tienda_categorias tc ON t.id = tc.tienda_id
    WHERE tc.categoria_id = :categoria_id AND tc.activo = 1
    ORDER BY t.nombre_negocio
");
$stmt->execute(['categoria_id' => $id]);
$tiendas_usando = $stmt->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="bi bi-pencil"></i> Editar Categoría Global</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Datos de la Categoría</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/CategoriaGlobalController.php?action=actualizar">
                    <input type="hidden" name="id" value="<?php echo e($categoria['id']); ?>">

                    <div class="mb-3">
                        <label for="tipo_negocio" class="form-label">Tipo de Negocio *</label>
                        <select class="form-select" id="tipo_negocio" name="tipo_negocio" required>
                            <option value="ALIMENTOS" <?php echo e($categoria['tipo_negocio'] === 'ALIMENTOS' ? 'selected' : ''); ?>>Alimentos</option>
                            <option value="FERRETERIA" <?php echo e($categoria['tipo_negocio'] === 'FERRETERIA' ? 'selected' : ''); ?>>Ferretería</option>
                            <option value="FARMACIA" <?php echo e($categoria['tipo_negocio'] === 'FARMACIA' ? 'selected' : ''); ?>>Farmacia</option>
                            <option value="ROPA" <?php echo e($categoria['tipo_negocio'] === 'ROPA' ? 'selected' : ''); ?>>Ropa</option>
                            <option value="TECNOLOGIA" <?php echo e($categoria['tipo_negocio'] === 'TECNOLOGIA' ? 'selected' : ''); ?>>Tecnología</option>
                            <option value="LIMPIEZA" <?php echo e($categoria['tipo_negocio'] === 'LIMPIEZA' ? 'selected' : ''); ?>>Limpieza</option>
                            <option value="LIBRERIA" <?php echo e($categoria['tipo_negocio'] === 'LIBRERIA' ? 'selected' : ''); ?>>Librería</option>
                            <option value="MASCOTAS" <?php echo e($categoria['tipo_negocio'] === 'MASCOTAS' ? 'selected' : ''); ?>>Mascotas</option>
                            <option value="VARIADO" <?php echo e($categoria['tipo_negocio'] === 'VARIADO' ? 'selected' : ''); ?>>Variado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                               value="<?php echo e($categoria['nombre']); ?>"
                               required minlength="3" maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion"
                                  rows="3" maxlength="255"><?php echo e($categoria['descripcion']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo"
                                   <?php echo e($categoria['activo'] ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="activo">
                                Categoría activa
                            </label>
                        </div>
                        <small class="text-muted">Si desactivas la categoría, no estará disponible para nuevas asignaciones</small>
                    </div>

                    <?php if ($categoria['productos_usando'] > 0): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Atención:</strong> Esta categoría está siendo usada por <strong><?php echo e($categoria['productos_usando']); ?> productos</strong>.
                        Los cambios afectarán a todos los productos que la usan.
                    </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/categorias_globales.php"
                           class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Estadísticas de uso -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Estadísticas de Uso</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Tiendas asignadas:</span>
                    <strong class="text-primary"><?php echo e($categoria['tiendas_asignadas']); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Productos usando:</span>
                    <strong class="text-success"><?php echo e($categoria['productos_usando']); ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Estado:</span>
                    <?php if ($categoria['activo']): ?>
                        <span class="badge bg-success">Activa</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Inactiva</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tiendas que usan esta categoría -->
        <?php if (count($tiendas_usando) > 0): ?>
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-shop"></i> Tiendas que usan esta categoría</h6>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($tiendas_usando as $tienda): ?>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i>
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/asignar_categorias.php?tienda_id=<?php echo e($tienda['id']); ?>">
                            <?php echo e($tienda['nombre_negocio']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
