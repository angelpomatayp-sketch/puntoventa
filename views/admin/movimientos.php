<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Movimientos de Inventario';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// MULTI-TENANT: Obtener productos activos de la tienda
$sql = "SELECT * FROM productos WHERE activo = TRUE";
TenantHelper::addTenantScope($sql);
$sql .= " ORDER BY nombre ASC";

$stmt = $db->query($sql);
$productos = $stmt->fetchAll();

// MULTI-TENANT: Obtener movimientos filtrados por tienda
$sql = "
    SELECT m.*, p.nombre as producto, u.nombre as usuario
    FROM movimientos_inventario m
    INNER JOIN productos p ON m.producto_id = p.id
    INNER JOIN usuarios u ON m.usuario_id = u.id
    WHERE 1=1
";
// MULTI-TENANT: Filtrar por tienda (excepto SUPER_ADMIN que ve todo)
if (!isSuperAdmin()) {
    $sql .= " AND m.tienda_id = " . getTiendaId();
}
$sql .= " ORDER BY m.created_at DESC LIMIT 200";

$stmt = $db->query($sql);
$movimientos = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clipboard-check"></i> Movimientos de Inventario</h2>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMovimiento">
                <i class="bi bi-plus-circle"></i> Registrar Movimiento
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Historial de Movimientos</h5>
                    <input type="text" class="form-control form-control-sm" id="buscarMovimientos"
                           placeholder="Buscar..." style="max-width: 240px;">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm" id="tablaMovimientos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Stock Ant.</th>
                                <th>Stock Nuevo</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td><?php echo e($mov['id']); ?></td>
                                <td><?php echo e(formatDate($mov['created_at'])); ?></td>
                                <td><?php echo e($mov['usuario']); ?></td>
                                <td><strong><?php echo e($mov['producto']); ?></strong></td>
                                <td>
                                    <?php if ($mov['tipo'] === 'ENTRADA'): ?>
                                        <span class="badge bg-success">Entrada</span>
                                    <?php elseif ($mov['tipo'] === 'SALIDA'): ?>
                                        <span class="badge bg-warning">Salida</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Ajuste</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($mov['tipo'] === 'SALIDA'): ?>
                                        <span class="text-danger">-<?php echo e($mov['cantidad']); ?></span>
                                    <?php else: ?>
                                        <span class="text-success">+<?php echo e($mov['cantidad']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($mov['stock_anterior']); ?></td>
                                <td><?php echo e($mov['stock_nuevo']); ?></td>
                                <td><small><?php echo e($mov['motivo'] ?: '-'); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Movimiento -->
<div class="modal fade" id="modalMovimiento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Registrar Movimiento de Inventario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/MovimientoController.php?action=crear">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="producto_id" class="form-label">Producto *</label>
                        <select class="form-select" id="producto_id" name="producto_id" required>
                            <option value="">Seleccione un producto...</option>
                            <?php foreach ($productos as $prod): ?>
                            <option value="<?php echo e($prod['id']); ?>">
                                <?php echo e($prod['nombre']); ?> (Stock actual: <?php echo e($prod['stock_actual']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de Movimiento *</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="ENTRADA">Entrada (Compra/Recepción)</option>
                            <option value="AJUSTE_POSITIVO">Ajuste Positivo (Corrección+)</option>
                            <option value="AJUSTE_NEGATIVO">Ajuste Negativo (Corrección-)</option>
                        </select>
                        <small class="text-muted">Los ajustes negativos disminuyen el stock</small>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad *</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo/Observación</label>
                        <textarea class="form-control" id="motivo" name="motivo" rows="3"
                                  placeholder="Detalle del movimiento..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar Movimiento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$custom_js = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    filterTable('buscarMovimientos', 'tablaMovimientos');
});
</script>
";
include '../layouts/footer.php';
?>
