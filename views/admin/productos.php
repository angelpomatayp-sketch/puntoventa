<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Gestión de Productos';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// MULTI-TENANT: Obtener productos filtrados por tienda
$sql = "
    SELECT p.*, c.nombre as categoria_nombre, um.abreviatura as unidad_abreviatura
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN unidades_medida um ON p.unidad_medida_id = um.id
    WHERE 1=1
";
// MULTI-TENANT: Filtrar por tienda (excepto SUPER_ADMIN que ve todo)
if (!isSuperAdmin()) {
    $sql .= " AND p.tienda_id = " . getTiendaId();
}
$sql .= " ORDER BY p.nombre ASC";

$stmt = $db->query($sql);
$productos = $stmt->fetchAll();

// MULTI-TENANT: Obtener categorías asignadas a la tienda
$categorias = getCategoriasDisponibles();

// Obtener unidades de medida activas (si existe la tabla)
$unidades_medida = [];
$tableExists = $db->query("SHOW TABLES LIKE 'unidades_medida'")->rowCount() > 0;
if ($tableExists) {
    $stmt = $db->query("
        SELECT id, nombre, abreviatura
        FROM unidades_medida
        WHERE activo = 1
        ORDER BY nombre ASC
    ");
    $unidades_medida = $stmt->fetchAll();
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-box-seam"></i> Gestión de Productos</h2>
            <button type="button" class="btn btn-primary" id="btnNuevoProducto" data-bs-toggle="modal" data-bs-target="#modalProducto">
                <i class="bi bi-plus-circle"></i> Nuevo Producto
            </button>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <input type="text" class="form-control" id="buscarProductoTabla" placeholder="Buscar producto...">
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaProductos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Unidad</th>
                                <th class="text-center">Precio Venta</th>
                                <th class="text-center">Costo</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center">Stock Mín</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo e($producto['id']); ?></td>
                                <td><strong><?php echo e($producto['nombre']); ?></strong></td>
                                <td><?php echo e($producto['categoria_nombre']); ?></td>
                                <td>
                                    <?php if ($producto['unidad_abreviatura']): ?>
                                        <span class="badge bg-secondary"><?php echo e($producto['unidad_abreviatura']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo e(formatMoney($producto['precio_venta'])); ?></td>
                                <td class="text-center"><?php echo e(formatMoney($producto['precio_compra'] ?? 0)); ?></td>
                                <td class="text-center">
                                    <?php if ($producto['stock_actual'] <= $producto['stock_minimo']): ?>
                                        <span class="badge bg-danger"><?php echo e($producto['stock_actual']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?php echo e($producto['stock_actual']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo e($producto['stock_minimo']); ?></td>
                                <td>
                                    <?php if ($producto['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning btn-edit"
                                            data-id="<?php echo e($producto['id']); ?>"
                                            data-nombre="<?php echo e($producto['nombre']); ?>"
                                            data-categoria="<?php echo e($producto['categoria_id']); ?>"
                                            data-unidad="<?php echo e($producto['unidad_medida_id']); ?>"
                                            data-precio="<?php echo e($producto['precio_venta']); ?>"
                                            data-costo="<?php echo e($producto['precio_compra'] ?? 0); ?>"
                                            data-stock="<?php echo e($producto['stock_minimo']); ?>"
                                            data-activo="<?php echo e($producto['activo']); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-delete"
                                            data-id="<?php echo e($producto['id']); ?>"
                                            data-nombre="<?php echo e($producto['nombre']); ?>">
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

<!-- Modal Crear/Editar Producto -->
<div class="modal fade" id="modalProducto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProductoTitle">Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProducto" method="POST" action="<?php echo e(BASE_URL); ?>/controllers/ProductoController.php">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="crear">
                    <input type="hidden" name="id" id="producto_id">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del Producto *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required autocomplete="off" spellcheck="false">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoria_id" class="form-label">Categoría *</label>
                                <select class="form-select" id="categoria_id" name="categoria_id" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo e($cat['id']); ?>"><?php echo e($cat['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unidad_medida_id" class="form-label">Unidad de Medida</label>
                                <select class="form-select" id="unidad_medida_id" name="unidad_medida_id">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($unidades_medida as $unidad): ?>
                                    <option value="<?php echo e($unidad['id']); ?>">
                                        <?php echo e($unidad['nombre']); ?><?php echo e($unidad['abreviatura'] ? ' (' . $unidad['abreviatura'] . ')' : ''); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="activo" class="form-label">Estado *</label>
                                <select class="form-select" id="activo" name="activo" required>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="precio_venta" class="form-label">Precio de Venta *</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/.</span>
                                    <input type="number" class="form-control" id="precio_venta" name="precio_venta"
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="precio_compra" class="form-label">Precio Compra (Opcional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/.</span>
                                    <input type="number" class="form-control" id="precio_compra" name="precio_compra"
                                           step="0.01" min="0" value="0">
                                </div>
                                <small class="text-muted">Para calcular margen de ganancia</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stock_minimo" class="form-label">Stock Mínimo *</label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo"
                                       min="0" value="5" required>
                                <small class="text-muted">Alerta cuando el stock llegue a este nivel</small>
                            </div>
                        </div>
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
    filterTable('buscarProductoTabla', 'tablaProductos');

    // Resetear modal al abrir para crear
    $('#modalProducto').on('show.bs.modal', function(e) {
        // Solo resetea si el modal fue gatillado por el botón de nuevo producto
        if (e.relatedTarget && e.relatedTarget.id === 'btnNuevoProducto') {
            $('#formProducto')[0].reset();
            $('#action').val('crear');
            $('#producto_id').val('');
            $('#modalProductoTitle').text('Nuevo Producto');
        }
    });

    // Event delegation for edit and delete buttons
    $('#tablaProductos').on('click', '.btn-edit', function() {
        $('#action').val('editar');
        $('#producto_id').val($(this).data('id'));
        $('#nombre').val($(this).data('nombre'));
        $('#categoria_id').val($(this).data('categoria'));
        $('#unidad_medida_id').val($(this).data('unidad'));
        $('#precio_venta').val($(this).data('precio'));
        $('#precio_compra').val($(this).data('costo'));
        $('#stock_minimo').val($(this).data('stock'));
        $('#activo').val($(this).data('activo') ? '1' : '0');
        $('#modalProductoTitle').text('Editar Producto');
        $('#modalProducto').modal('show');
    });

    $('#tablaProductos').on('click', '.btn-delete', function() {
        if (confirm('¿Está seguro de eliminar el producto: ' + $(this).data('nombre') + '?')) {
            window.location.href = '<?php echo e(BASE_URL); ?>/controllers/ProductoController.php?action=eliminar&id=' + $(this).data('id') + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
        }
    });
});
</script>
";
include '../layouts/footer.php';
?>
