<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Gestión de Clientes';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// MULTI-TENANT: Obtener clientes filtrados por tienda
$sql = "SELECT * FROM clientes WHERE 1=1";
TenantHelper::addTenantScope($sql);
// Mostrar cliente ANÓNIMO primero
$sql .= " ORDER BY CASE WHEN nombre = 'ANÓNIMO' THEN 0 ELSE 1 END, nombre ASC";

$stmt = $db->query($sql);
$clientes = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Gestión de Clientes</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                <i class="bi bi-plus-circle"></i> Nuevo Cliente
            </button>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <input type="text" class="form-control" id="buscarCliente" placeholder="Buscar por nombre o DNI...">
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaClientes">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>DNI/RUC</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?php echo e($cliente['id']); ?></td>
                                <td>
                                    <strong><?php echo e($cliente['nombre']); ?></strong>
                                    <?php if ($cliente['id'] == 1): ?>
                                        <span class="badge bg-secondary">Por Defecto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($cliente['dni_ruc']); ?></td>
                                <td><?php echo e($cliente['telefono']); ?></td>
                                <td>
                                    <?php if ($cliente['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e(formatDate($cliente['created_at'])); ?></td>
                                <td class="text-center">
                                    <a href="<?php echo e(BASE_URL); ?>/views/admin/historial_cliente.php?id=<?php echo e($cliente['id']); ?>"
                                       class="btn btn-sm btn-info" title="Ver Historial">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <?php if ($cliente['id'] != 1): ?>
                                    <button type="button" class="btn btn-sm btn-warning btn-edit"
                                            data-id="<?php echo e($cliente['id']); ?>"
                                            data-nombre="<?php echo e(htmlspecialchars($cliente['nombre'], ENT_QUOTES)); ?>"
                                            data-dni="<?php echo e(htmlspecialchars($cliente['dni_ruc'], ENT_QUOTES)); ?>"
                                            data-telefono="<?php echo e(htmlspecialchars($cliente['telefono'], ENT_QUOTES)); ?>"
                                            data-activo="<?php echo e($cliente['activo'] ? '1' : '0'); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete"
                                            data-id="<?php echo e($cliente['id']); ?>"
                                            data-nombre="<?php echo e(htmlspecialchars($cliente['nombre'], ENT_QUOTES)); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Protegido</span>
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

<!-- Modal Crear/Editar Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalClienteTitle">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCliente" method="POST" action="<?php echo e(BASE_URL); ?>/controllers/ClienteController.php">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="crear">
                    <input type="hidden" name="id" id="cliente_id">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required minlength="3">
                    </div>

                    <div class="mb-3">
                        <label for="dni_ruc" class="form-label">DNI/RUC *</label>
                        <input type="text" class="form-control" id="dni_ruc" name="dni_ruc" required
                               pattern="[0-9]{8}|[0-9]{11}" maxlength="11"
                               title="Debe ser un DNI (8 dígitos) o RUC (11 dígitos)">
                        <small class="text-muted">DNI: 8 dígitos | RUC: 11 dígitos</small>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono *</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" required
                               pattern="[0-9]{9}" maxlength="9"
                               title="Debe tener 9 dígitos">
                        <small class="text-muted">9 dígitos</small>
                    </div>

                    <div class="mb-3" id="estadoGroup" style="display: none;">
                        <label for="activo" class="form-label">Estado *</label>
                        <select class="form-select" id="activo" name="activo">
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
    // Buscar en tabla
    filterTable('buscarCliente', 'tablaClientes');

    // Validar solo números en DNI y teléfono
    $('#dni_ruc, #telefono').on('keypress', function(e) {
        if (e.which < 48 || e.which > 57) {
            e.preventDefault();
        }
    });

    // Variable para controlar si estamos editando
    let isEditing = false;

    // Resetear modal cuando se cierra
    $('#modalCliente').on('hidden.bs.modal', function() {
        $('#formCliente')[0].reset();
        $('#action').val('crear');
        $('#cliente_id').val('');
        $('#modalClienteTitle').text('Nuevo Cliente');
        $('#estadoGroup').hide();
        $('#activo').removeAttr('required');
        isEditing = false;
    });

    // Resetear modal al abrir (solo si no estamos editando)
    $('#modalCliente').on('show.bs.modal', function(e) {
        if (!isEditing) {
            $('#formCliente')[0].reset();
            $('#action').val('crear');
            $('#cliente_id').val('');
            $('#modalClienteTitle').text('Nuevo Cliente');
            $('#estadoGroup').hide();
            $('#activo').removeAttr('required');
        }
    });

    // Editar cliente - usando delegación de eventos
    $(document).on('click', '.btn-edit', function(e) {
        e.preventDefault();
        isEditing = true;

        const id = $(this).data('id');
        const activo = $(this).data('activo');

        console.log('Abriendo modal para editar cliente. ID:', id);

        $('#action').val('editar');
        $('#cliente_id').val(id);
        $('#nombre').val($(this).data('nombre'));
        $('#dni_ruc').val($(this).data('dni'));
        $('#telefono').val($(this).data('telefono'));
        // Convertir activo a string '1' o '0' correctamente
        $('#activo').val(activo == 1 || activo === '1' || activo === true ? '1' : '0');

        console.log('Datos cargados en el formulario:', {
            id: id,
            nombre: $(this).data('nombre'),
            dni: $(this).data('dni'),
            telefono: $(this).data('telefono'),
            activo: activo,
            activoFinal: $('#activo').val()
        });

        $('#modalClienteTitle').text('Editar Cliente');
        $('#estadoGroup').show();
        $('#activo').attr('required', true);
        $('#modalCliente').modal('show');
    });

    // Eliminar cliente - usando delegación de eventos
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        console.log('Intentando eliminar cliente. ID:', id, 'Nombre:', nombre);

        if (confirm('¿Está seguro de eliminar al cliente: ' + nombre + '?\\n\\nSus ventas serán asignadas a ANÓNIMO.')) {
            // Usar la constante BASE_URL de PHP
            window.location.href = '" . BASE_URL . "/controllers/ClienteController.php?action=eliminar&id=' + id + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
        }
    });
});
</script>
";
include '../layouts/footer.php';
?>
