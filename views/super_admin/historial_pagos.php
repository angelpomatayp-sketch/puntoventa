<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Historial de Pagos';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Validar que se recibió tienda_id
if (!isset($_GET['tienda_id'])) {
    setFlashMessage('Tienda no especificada', 'danger');
    redirect('/views/super_admin/tiendas.php');
    exit;
}

$tienda_id = intval($_GET['tienda_id']);

// Obtener datos de la tienda
$stmt = $db->prepare("SELECT * FROM tiendas WHERE id = :id");
$stmt->execute(['id' => $tienda_id]);
$tienda = $stmt->fetch();

if (!$tienda) {
    setFlashMessage('Tienda no encontrada', 'danger');
    redirect('/views/super_admin/tiendas.php');
    exit;
}

// Obtener historial de pagos
$stmt = $db->prepare("
    SELECT ps.*, u.nombre as registrado_por_nombre
    FROM pagos_suscripcion ps
    INNER JOIN usuarios u ON ps.registrado_por = u.id
    WHERE ps.tienda_id = :tienda_id
    ORDER BY ps.fecha_pago DESC
");
$stmt->execute(['tienda_id' => $tienda_id]);
$pagos = $stmt->fetchAll();

// Calcular totales
$total_pagado = 0;
foreach ($pagos as $pago) {
    $total_pagado += $pago['monto'];
}

// Obtener estado de suscripción
$estadoSub = getEstadoSuscripcion($tienda['fecha_proximo_pago'], $tienda['estado']);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>
            <i class="bi bi-clock-history"></i> Historial de Pagos
        </h2>
        <p class="text-muted">
            <a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php" class="text-decoration-none">
                <i class="bi bi-arrow-left"></i> Volver a Gestión de Tiendas
            </a>
        </p>
    </div>
</div>

<!-- Información de la Tienda -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <?php if ($tienda['logo']): ?>
                    <img src="<?php echo e(getLogoUrl($tienda['logo'])); ?>"
                         alt="Logo"
                         class="img-thumbnail"
                         style="max-width: 150px; max-height: 100px; object-fit: contain;">
                <?php else: ?>
                    <i class="bi bi-shop-window" style="font-size: 4rem; color: #ccc;"></i>
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <h4 class="mb-2"><?php echo e($tienda['nombre_negocio']); ?></h4>
                <p class="mb-1"><strong>RUC:</strong> <?php echo e($tienda['ruc']); ?></p>
                <p class="mb-1"><strong>Plan:</strong> <span class="badge bg-secondary"><?php echo e($tienda['plan']); ?></span></p>
                <p class="mb-0"><strong>Monto Mensual:</strong> <?php echo e(formatMoney($tienda['monto_mensual'])); ?></p>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Estado de Suscripción</h6>
                        <h4 class="mb-2">
                            <span class="badge bg-<?php echo e($estadoSub['clase']); ?>">
                                <i class="bi bi-<?php echo e($estadoSub['icono']); ?>"></i>
                                <?php echo e($estadoSub['texto']); ?>
                            </span>
                        </h4>
                        <?php if ($tienda['fecha_ultimo_pago']): ?>
                        <p class="mb-1">
                            <small><strong>Último Pago:</strong> <?php echo e(date('d/m/Y', strtotime($tienda['fecha_ultimo_pago']))); ?></small>
                        </p>
                        <?php endif; ?>
                        <?php if ($tienda['fecha_proximo_pago']): ?>
                        <p class="mb-0">
                            <small><strong>Próximo Pago:</strong> <?php echo e(date('d/m/Y', strtotime($tienda['fecha_proximo_pago']))); ?></small>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumen de Pagos -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Total de Pagos</h6>
                <h3 class="text-primary"><?php echo e(count($pagos)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Monto Total Pagado</h6>
                <h3 class="text-success"><?php echo e(formatMoney($total_pagado)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Promedio Mensual</h6>
                <h3 class="text-info">
                    <?php echo e(count($pagos) > 0 ? formatMoney($total_pagado / count($pagos)) : 'S/. 0.00'); ?>
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Historial de Pagos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Historial Detallado de Pagos</h5>
        <button class="btn btn-primary btn-sm btn-registrar-pago"
                data-id="<?php echo e($tienda['id']); ?>"
                data-nombre="<?php echo e($tienda['nombre_negocio']); ?>"
                data-monto="<?php echo e($tienda['monto_mensual']); ?>">
            <i class="bi bi-plus-circle"></i> Registrar Nuevo Pago
        </button>
    </div>
    <div class="card-body">
        <?php if (count($pagos) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha Pago</th>
                        <th>Período Cubierto</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Referencia</th>
                        <th>Registrado Por</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td><strong>#<?php echo e($pago['id']); ?></strong></td>
                        <td><?php echo e(date('d/m/Y', strtotime($pago['fecha_pago']))); ?></td>
                        <td>
                            <small>
                                <?php echo e(date('d/m/Y', strtotime($pago['periodo_desde']))); ?>
                                <br>
                                <i class="bi bi-arrow-down"></i>
                                <br>
                                <?php echo e(date('d/m/Y', strtotime($pago['periodo_hasta']))); ?>
                            </small>
                        </td>
                        <td><strong class="text-success"><?php echo e(formatMoney($pago['monto'])); ?></strong></td>
                        <td>
                            <?php
                            $badge_class = 'bg-secondary';
                            switch($pago['metodo_pago']) {
                                case 'EFECTIVO': $badge_class = 'bg-success'; break;
                                case 'TRANSFERENCIA': $badge_class = 'bg-primary'; break;
                                case 'YAPE': $badge_class = 'bg-purple'; break;
                                case 'PLIN': $badge_class = 'bg-info'; break;
                                case 'TARJETA': $badge_class = 'bg-warning'; break;
                            }
                            ?>
                            <span class="badge <?php echo e($badge_class); ?>"><?php echo e($pago['metodo_pago']); ?></span>
                        </td>
                        <td>
                            <?php if ($pago['referencia']): ?>
                                <code><?php echo e($pago['referencia']); ?></code>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small>
                                <?php echo e($pago['registrado_por_nombre']); ?>
                                <br>
                                <span class="text-muted"><?php echo e(formatDate($pago['created_at'])); ?></span>
                            </small>
                        </td>
                        <td>
                            <?php if ($pago['notas']): ?>
                                <small><?php echo e(nl2br(htmlspecialchars($pago['notas']))); ?></small>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <td colspan="3" class="text-end"><strong>TOTAL PAGADO:</strong></td>
                        <td><strong class="text-success"><?php echo e(formatMoney($total_pagado)); ?></strong></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-receipt" style="font-size: 4rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No hay pagos registrados para esta tienda</p>
            <button class="btn btn-primary btn-registrar-pago"
                    data-id="<?php echo e($tienda['id']); ?>"
                    data-nombre="<?php echo e($tienda['nombre_negocio']); ?>"
                    data-monto="<?php echo e($tienda['monto_mensual']); ?>">
                <i class="bi bi-plus-circle"></i> Registrar Primer Pago
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Registrar Pago -->
<div class="modal fade" id="modalRegistrarPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Pago de Suscripción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=registrar_pago">
                <div class="modal-body">
                    <input type="hidden" name="tienda_id" id="pago_tienda_id" value="<?php echo e($tienda_id); ?>">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Tienda:</strong> <span id="pago_tienda_nombre"><?php echo e($tienda['nombre_negocio']); ?></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Monto a Pagar *</label>
                            <div class="input-group">
                                <span class="input-group-text">S/.</span>
                                <input type="number" class="form-control" name="monto" id="pago_monto"
                                       value="<?php echo e($tienda['monto_mensual']); ?>"
                                       step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Pago *</label>
                            <input type="date" class="form-control" name="fecha_pago"
                                   value="<?php echo e(date('Y-m-d')); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Método de Pago *</label>
                        <select class="form-select" name="metodo_pago" required>
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TRANSFERENCIA">Transferencia Bancaria</option>
                            <option value="YAPE">Yape</option>
                            <option value="PLIN">Plin</option>
                            <option value="TARJETA">Tarjeta de Crédito/Débito</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Referencia / Nro. Operación</label>
                        <input type="text" class="form-control" name="referencia"
                               placeholder="Ej: OP-123456">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas / Observaciones</label>
                        <textarea class="form-control" name="notas" rows="3"
                                  placeholder="Información adicional sobre el pago..."></textarea>
                    </div>

                    <div class="alert alert-success">
                        <i class="bi bi-calendar-check"></i>
                        <strong>Período de Cobertura:</strong><br>
                        Este pago cubrirá el período desde <strong>HOY</strong> hasta
                        <strong><?php echo e(date('d/m/Y', strtotime('+30 days'))); ?></strong> (30 días)
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activar_tienda"
                               id="activar_tienda" <?php echo e($tienda['estado'] === 'SUSPENDIDA' ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="activar_tienda">
                            <strong>Activar tienda y usuarios automáticamente</strong>
                            <br>
                            <small class="text-muted">Si la tienda está suspendida, se activará junto con todos sus usuarios</small>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Registrar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$custom_js = "
<script>
$(document).ready(function() {
    // Abrir modal de registrar pago
    $('.btn-registrar-pago').click(function() {
        $('#modalRegistrarPago').modal('show');
    });
});
</script>
";
include '../layouts/footer.php';
?>
