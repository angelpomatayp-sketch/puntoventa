<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Gestión de Categorías';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// MULTI-TENANT: Obtener categorías filtradas por tienda
$sql = "SELECT * FROM categorias WHERE 1=1";
TenantHelper::addTenantScope($sql);
$sql .= " ORDER BY nombre ASC";

$stmt = $db->query($sql);
$categorias = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-tags"></i> Gestión de Categorías</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                <i class="bi bi-plus-circle"></i> Nueva Categoría
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?php echo e($categoria['id']); ?></td>
                                <td><strong><?php echo e($categoria['nombre']); ?></strong></td>
                                <td><?php echo e($categoria['descripcion'] ?: '-'); ?></td>
                                <td>
                                    <?php if ($categoria['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e(formatDate($categoria['created_at'])); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning btn-edit"
                                            data-id="<?php echo e($categoria['id']); ?>"
                                            data-nombre="<?php echo e($categoria['nombre']); ?>"
                                            data-descripcion="<?php echo e($categoria['descripcion']); ?>"
                                            data-activo="<?php echo e($categoria['activo']); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-delete"
                                            data-id="<?php echo e($categoria['id']); ?>"
                                            data-nombre="<?php echo e($categoria['nombre']); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
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

<!-- Modal Crear/Editar Categoría -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCategoriaTitle">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCategoria" method="POST" action="<?php echo e(BASE_URL); ?>/controllers/CategoriaController.php">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="crear">
                    <input type="hidden" name="id" id="categoria_id">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="activo" class="form-label">Estado *</label>
                        <select class="form-select" id="activo" name="activo" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$custom_js = "
<script>
$(document).ready(function() {
    $('#modalCategoria').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('btn-edit')) {
            $('#formCategoria')[0].reset();
            $('#action').val('crear');
            $('#categoria_id').val('');
            $('#modalCategoriaTitle').text('Nueva Categoría');
        }
    });

    $('.btn-edit').click(function() {
        $('#action').val('editar');
        $('#categoria_id').val($(this).data('id'));
        $('#nombre').val($(this).data('nombre'));
        $('#descripcion').val($(this).data('descripcion'));
        $('#activo').val($(this).data('activo') ? '1' : '0');
        $('#modalCategoriaTitle').text('Editar Categoría');
        $('#modalCategoria').modal('show');
    });

    $('.btn-delete').click(function() {
        if (confirm('¿Está seguro de eliminar la categoría: ' + $(this).data('nombre') + '?')) {
            window.location.href = '" . BASE_URL . "/controllers/CategoriaController.php?action=eliminar&id=' + $(this).data('id') + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
        }
    });
});
</script>
";
include '../layouts/footer.php';
?>
