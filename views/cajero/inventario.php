<?php
require_once '../../config/config.php';
requireLogin();

$page_title = 'Movimientos de Inventario';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// MULTI-TENANT: Obtener productos activos de la tienda
$stmt = $db->prepare("SELECT * FROM productos WHERE activo = TRUE AND tienda_id = :tienda_id ORDER BY nombre ASC");
$stmt->execute(['tienda_id' => getTiendaId()]);
$productos = $stmt->fetchAll();

// Obtener movimientos recientes del usuario
$stmt = $db->prepare("
    SELECT m.*, p.nombre as producto, u.nombre as usuario
    FROM movimientos_inventario m
    INNER JOIN productos p ON m.producto_id = p.id
    INNER JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.usuario_id = :usuario_id
    ORDER BY m.created_at DESC
    LIMIT 50
");
$stmt->execute(['usuario_id' => $_SESSION['user_id']]);
$movimientos = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clipboard-check"></i> Movimientos de Inventario</h2>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMovimiento">
                <i class="bi bi-plus-circle"></i> Registrar Ingreso
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mis Movimientos Recientes</h5>
                    <input type="text" class="form-control form-control-sm" id="buscarMovimientos"
                           placeholder="Buscar..." style="max-width: 240px;">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaMovimientos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Stock Anterior</th>
                                <th>Stock Nuevo</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td><?php echo e(formatDate($mov['created_at'])); ?></td>
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
                                <td><strong><?php echo e($mov['cantidad']); ?></strong></td>
                                <td><?php echo e($mov['stock_anterior']); ?></td>
                                <td><?php echo e($mov['stock_nuevo']); ?></td>
                                <td><?php echo e($mov['motivo'] ?: '-'); ?></td>
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
                <h5 class="modal-title">Registrar Ingreso de Inventario</h5>
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
                        </select>
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
                    <button type="submit" class="btn btn-success">Registrar Ingreso</button>
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
