<?php
require_once '../../config/config.php';
requireSuperAdmin();

$db = Database::getInstance()->getConnection();

// Obtener ID de la unidad
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    setFlashMessage('ID de unidad no válido', 'danger');
    redirect('/views/super_admin/unidades_medida.php');
}

// Obtener datos de la unidad
$stmt = $db->prepare("
    SELECT um.*,
           COUNT(p.id) as productos_usando
    FROM unidades_medida um
    LEFT JOIN productos p ON um.id = p.unidad_medida_id
    WHERE um.id = :id
    GROUP BY um.id
");
$stmt->execute(['id' => $id]);
$unidad = $stmt->fetch();

if (!$unidad) {
    setFlashMessage('Unidad de medida no encontrada', 'danger');
    redirect('/views/super_admin/unidades_medida.php');
}

$page_title = 'Editar: ' . $unidad['nombre'];
include '../layouts/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo e(BASE_URL); ?>/views/super_admin/dashboard.php">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo e(BASE_URL); ?>/views/super_admin/unidades_medida.php">Unidades de Medida</a>
                </li>
                <li class="breadcrumb-item active">Editar: <?php echo e($unidad['nombre']); ?></li>
            </ol>
        </nav>
        <h2><i class="bi bi-pencil"></i> Editar Unidad de Medida</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-rulers"></i> Datos de la Unidad</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo e(BASE_URL); ?>/controllers/UnidadMedidaController.php?action=actualizar" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo e($unidad['id']); ?>">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" required
                                   value="<?php echo e(htmlspecialchars($unidad['nombre'])); ?>"
                                   maxlength="50">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Abreviatura <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="abreviatura" required
                                   value="<?php echo e(htmlspecialchars($unidad['abreviatura'])); ?>"
                                   maxlength="10"
                                   style="text-transform: lowercase;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"
                                  maxlength="255"><?php echo e(htmlspecialchars($unidad['descripcion'] ?? '')); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo"
                                   <?php echo e($unidad['activo'] ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="activo">
                                Unidad activa (disponible para asignar a productos)
                            </label>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/unidades_medida.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Información de uso -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>ID:</th>
                        <td>#<?php echo e($unidad['id']); ?></td>
                    </tr>
                    <tr>
                        <th>Productos usando:</th>
                        <td>
                            <?php if ($unidad['productos_usando'] > 0): ?>
                            <span class="badge bg-info"><?php echo e($unidad['productos_usando']); ?> productos</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Ninguno</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Creado:</th>
                        <td><small><?php echo e(formatDate($unidad['created_at'])); ?></small></td>
                    </tr>
                    <tr>
                        <th>Actualizado:</th>
                        <td><small><?php echo e(formatDate($unidad['updated_at'])); ?></small></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php if ($unidad['productos_usando'] == 0): ?>
        <div class="card mt-3 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-trash"></i> Zona de Peligro</h5>
            </div>
            <div class="card-body">
                <p class="small">Esta unidad no está siendo usada por ningún producto y puede ser eliminada.</p>
                <a href="<?php echo e(BASE_URL); ?>/controllers/UnidadMedidaController.php?action=eliminar&id=<?php echo e($unidad['id']); ?>&csrf_token=<?php echo e(getCsrfToken()); ?>"
                   class="btn btn-outline-danger btn-sm w-100"
                   onclick="return confirm('¿Estás seguro de eliminar esta unidad de medida? Esta acción no se puede deshacer.')">
                    <i class="bi bi-trash"></i> Eliminar Unidad
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="card mt-3 border-warning">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Aviso</h5>
            </div>
            <div class="card-body">
                <p class="small mb-0">Esta unidad está siendo utilizada por <?php echo e($unidad['productos_usando']); ?> producto(s) y no puede ser eliminada. Puedes desactivarla para que no aparezca en nuevos productos.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
