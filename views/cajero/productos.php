<?php
require_once '../../config/config.php';
requireLogin();

$page_title = 'Productos';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// MULTI-TENANT: Obtener productos filtrados por tienda
$sql = "
    SELECT p.*, c.nombre as categoria_nombre
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.activo = TRUE
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
            <h2><i class="bi bi-box-seam"></i> Productos</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto">
                <i class="bi bi-plus-circle"></i> Registrar Nuevo Producto
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
                                <th>Precio Venta</th>
                                <th>Stock Actual</th>
                                <th>Stock Mín</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo e($producto['id']); ?></td>
                                <td><strong><?php echo e($producto['nombre']); ?></strong></td>
                                <td><?php echo e($producto['categoria_nombre']); ?></td>
                                <td class="text-end"><?php echo e(formatMoney($producto['precio_venta'])); ?></td>
                                <td class="text-center">
                                    <?php if ($producto['stock_actual'] <= $producto['stock_minimo']): ?>
                                        <span class="badge bg-danger"><?php echo e($producto['stock_actual']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?php echo e($producto['stock_actual']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo e($producto['stock_minimo']); ?></td>
                                <td>
                                    <span class="badge bg-success">Activo</span>
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

<!-- Modal Registrar Producto -->
<div class="modal fade" id="modalProducto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/ProductoController.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="crear_cajero">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Solo puede registrar nuevos productos. Para editar o eliminar, contacte al administrador.
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del Producto *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required autocomplete="off" spellcheck="false">
                            </div>
                        </div>
                        <div class="col-md-4">
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
                                <label for="precio_venta" class="form-label">Precio de Venta *</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/.</span>
                                    <input type="number" class="form-control" id="precio_venta" name="precio_venta"
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stock_min" class="form-label">Stock Mínimo *</label>
                                <input type="number" class="form-control" id="stock_min" name="stock_min"
                                       min="0" value="5" required>
                                <small class="text-muted">Alerta cuando el stock llegue a este nivel</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$custom_js = "
<script>
$(document).ready(function() {
    filterTable('buscarProductoTabla', 'tablaProductos');
});
</script>
";
include '../layouts/footer.php';
?>
