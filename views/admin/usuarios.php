<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Gestión de Usuarios';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// MULTI-TENANT: Obtener usuarios filtrados por tienda
$sql = "SELECT * FROM usuarios WHERE 1=1";

// SUPER_ADMIN ve todos los usuarios
// ADMIN solo ve usuarios de su tienda
if (!isSuperAdmin()) {
    $sql .= " AND tienda_id = " . getTiendaId();
}

$sql .= " ORDER BY id DESC";

$stmt = $db->query($sql);
$usuarios = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Gestión de Usuarios</h2>
            <button type="button" class="btn btn-primary" id="btnNuevoUsuario" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                <i class="bi bi-plus-circle"></i> Nuevo Usuario
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaUsuarios">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo e($usuario['id']); ?></td>
                                <td><strong><?php echo e($usuario['nombre']); ?></strong></td>
                                <td><?php echo e($usuario['usuario']); ?></td>
                                <td>
                                    <?php if ($usuario['rol'] === 'SUPER_ADMINISTRADOR'): ?>
                                        <span class="badge bg-danger">Super Administrador</span>
                                    <?php elseif ($usuario['rol'] === 'ADMINISTRADOR'): ?>
                                        <span class="badge bg-primary">Administrador</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Cajero</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e(formatDate($usuario['created_at'])); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning btn-edit"
                                            data-id="<?php echo e($usuario['id']); ?>"
                                            data-nombre="<?php echo e($usuario['nombre']); ?>"
                                            data-usuario="<?php echo e($usuario['usuario']); ?>"
                                            data-rol="<?php echo e($usuario['rol']); ?>"
                                            data-activo="<?php echo e($usuario['activo']); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-danger btn-delete"
                                            data-id="<?php echo e($usuario['id']); ?>"
                                            data-nombre="<?php echo e($usuario['nombre']); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
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

<!-- Modal Crear/Editar Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioTitle">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUsuario" method="POST" action="<?php echo e(BASE_URL); ?>/controllers/UsuarioController.php">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="crear">
                    <input type="hidden" name="id" id="usuario_id">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario *</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required>
                    </div>

                    <div class="mb-3" id="passwordGroup">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="text-muted">Dejar en blanco para no cambiar. Mínimo 6 caracteres.</small>
                    </div>

                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol *</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="CAJERO">Cajero</option>
                            <option value="ADMINISTRADOR">Administrador</option>
                        </select>
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
    // Resetear modal para crear
    $('#modalUsuario').on('show.bs.modal', function(e) {
        if (e.relatedTarget && e.relatedTarget.id === 'btnNuevoUsuario') {
            $('#formUsuario')[0].reset();
            $('#action').val('crear');
            $('#usuario_id').val('');
            $('#modalUsuarioTitle').text('Nuevo Usuario');
            $('#password').prop('required', true);
            $('#password').prev('label').text('Contraseña *');
            $('#password').next('.text-muted').text('Mínimo 6 caracteres');
        }
    });

    // Delegación de eventos para botones de la tabla
    $('#tablaUsuarios').on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const usuario = $(this).data('usuario');
        const rol = $(this).data('rol');
        const activo = $(this).data('activo');

        $('#action').val('editar');
        $('#usuario_id').val(id);
        $('#nombre').val(nombre);
        $('#usuario').val(usuario);
        $('#rol').val(rol);
        $('#activo').val(activo ? '1' : '0');
        
        $('#password').val('').prop('required', false);
        $('#password').prev('label').text('Contraseña');
        $('#password').next('.text-muted').text('Dejar en blanco para no cambiar. Mínimo 6 caracteres.');

        $('#modalUsuarioTitle').text('Editar Usuario: ' + nombre);
        $('#modalUsuario').modal('show');
    });

    $('#tablaUsuarios').on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        if (confirm('¿Está seguro de eliminar al usuario: ' + nombre + '? Esta acción no se puede deshacer.')) {
            window.location.href = '<?php echo e(BASE_URL); ?>/controllers/UsuarioController.php?action=eliminar&id=' + id + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
        }
    });
});
</script>
";
include '../layouts/footer.php';
?>
