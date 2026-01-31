<?php
require_once '../../config/config.php';
requireLogin();

$page_title = 'Clientes';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// MULTI-TENANT: Obtener clientes activos de la tienda
$sql = "SELECT * FROM clientes WHERE activo = TRUE";
TenantHelper::addTenantScope($sql);
$sql .= " ORDER BY CASE WHEN nombre = 'ANÓNIMO' THEN 0 ELSE 1 END, nombre ASC";

$stmt = $db->query($sql);
$clientes = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Clientes</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                <i class="bi bi-plus-circle"></i> Registrar Nuevo Cliente
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
                                <th>Fecha Registro</th>
                                <th class="text-center">Historial</th>
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
                                <td><?php echo e(formatDate($cliente['created_at'])); ?></td>
                                <td class="text-center">
                                    <a href="<?php echo e(BASE_URL); ?>/views/admin/historial_cliente.php?id=<?php echo e($cliente['id']); ?>"
                                       class="btn btn-sm btn-info" title="Ver Historial">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
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

<!-- Modal Crear Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCliente" method="POST" action="<?php echo e(BASE_URL); ?>/controllers/ClienteController.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="crear">

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

    // Resetear modal
    $('#modalCliente').on('show.bs.modal', function() {
        $('#formCliente')[0].reset();
    });
});
</script>
";
include '../layouts/footer.php';
?>
