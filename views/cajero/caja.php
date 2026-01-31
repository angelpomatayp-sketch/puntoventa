<?php
require_once '../../config/config.php';
requireLogin();

$page_title = 'Mi Caja';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Verificar si hay caja abierta del usuario actual
$caja_abierta = getCajaAbierta($_SESSION['user_id']);

// Obtener historial de cajas del usuario
$stmt = $db->prepare("
    SELECT * FROM cajas
    WHERE usuario_id = :usuario_id
    ORDER BY id DESC
    LIMIT 10
");
$stmt->execute(['usuario_id' => $_SESSION['user_id']]);
$mis_cajas = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cash-stack"></i> Mi Caja</h2>
            <?php if (!$caja_abierta): ?>
            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">
                <i class="bi bi-unlock"></i> Abrir Mi Caja
            </button>
            <?php else: ?>
            <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#modalCerrarCaja">
                <i class="bi bi-lock"></i> Cerrar Mi Caja
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($caja_abierta): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="bi bi-check-circle"></i> Caja Abierta #<?php echo e($caja_abierta['id']); ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Saldo Inicial</h6>
                                <h3 class="text-primary"><?php echo e(formatMoney($caja_abierta['saldo_inicial'])); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Apertura</h6>
                                <h5><?php echo e(formatDate($caja_abierta['fecha_apertura'])); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">
                                    <i class="bi bi-cash"></i> Efectivo en Caja
                                </h6>
                                <h4 class="text-success mb-0">
                                    <?php echo e(formatMoney($caja_abierta['saldo_inicial'] + $caja_abierta['efectivo_esperado'])); ?>
                                </h4>
                                <small class="text-muted">Saldo Inicial + Ventas</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">
                                    <i class="bi bi-phone"></i> Yape Registrado
                                </h6>
                                <h4 class="text-primary mb-0">
                                    <?php echo e(formatMoney($caja_abierta['yape_total_registrado'])); ?>
                                </h4>
                                <small class="text-muted">Total en Yape</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">
                                    <i class="bi bi-calculator"></i> Total General
                                </h6>
                                <h4 class="text-info mb-0">
                                    <?php echo e(formatMoney($caja_abierta['saldo_inicial'] + $caja_abierta['efectivo_esperado'] + $caja_abierta['yape_total_registrado'])); ?>
                                </h4>
                                <small class="text-muted">Efectivo + Yape</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle"></i> <strong>Recuerde:</strong> Al finalizar su turno, debe cerrar la caja contando el efectivo físico total y confirmando el monto de Yape.
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> No tiene una caja abierta</h5>
            <p>Debe abrir una caja antes de poder realizar ventas. Haga clic en el botón "Abrir Mi Caja" para comenzar.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Historial de Mis Cajas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Saldo Inicial</th>
                                <th>Total Efectivo</th>
                                <th>Total Yape</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mis_cajas as $caja): ?>
                            <tr>
                                <td><strong>#<?php echo e($caja['id']); ?></strong></td>
                                <td><?php echo e(formatDate($caja['fecha_apertura'])); ?></td>
                                <td><?php echo e($caja['fecha_cierre'] ? formatDate($caja['fecha_cierre']) : '-'); ?></td>
                                <td><?php echo e(formatMoney($caja['saldo_inicial'])); ?></td>
                                <td><?php echo e(formatMoney($caja['efectivo_contado'] ?? 0)); ?></td>
                                <td><?php echo e(formatMoney($caja['yape_total_registrado'])); ?></td>
                                <td>
                                    <?php if ($caja['estado'] === 'ABIERTA'): ?>
                                        <span class="badge bg-success">Abierta</span>
                                    <?php elseif ($caja['estado'] === 'CERRADA'): ?>
                                        <span class="badge bg-warning">Pendiente Validación</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Validada</span>
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

<!-- Modal Abrir Caja -->
<div class="modal fade" id="modalAbrirCaja" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Abrir Mi Caja</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/CajaController.php?action=abrir">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Ingrese el monto en efectivo con el que inicia su caja (billetes y monedas).
                    </div>

                    <div class="mb-3">
                        <label for="saldo_inicial" class="form-label">Saldo Inicial (Efectivo) *</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">S/.</span>
                            <input type="number" class="form-control" id="saldo_inicial" name="saldo_inicial"
                                   step="0.01" min="0" required autofocus>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-unlock"></i> Abrir Caja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cerrar Caja -->
<?php if ($caja_abierta): ?>
<div class="modal fade" id="modalCerrarCaja" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Cerrar Mi Caja #<?php echo e($caja_abierta['id']); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/CajaController.php?action=cerrar">
                <div class="modal-body">
                    <input type="hidden" name="caja_id" value="<?php echo e($caja_abierta['id']); ?>">

                    <div class="alert alert-warning">
                        <strong><i class="bi bi-exclamation-triangle"></i> Importante:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Cuente todo el efectivo físico en caja</li>
                            <li>Verifique el total de operaciones Yape</li>
                            <li>La caja será validada por el administrador</li>
                        </ul>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Montos Esperados:</h6>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Efectivo Esperado:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="text-success fw-bold">
                                        <?php echo e(formatMoney($caja_abierta['saldo_inicial'] + $caja_abierta['efectivo_esperado'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>Yape Registrado:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="text-primary fw-bold">
                                        <?php echo e(formatMoney($caja_abierta['yape_total_registrado'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="efectivo_contado" class="form-label">
                            <i class="bi bi-cash"></i> Efectivo Contado (Total en Caja) *
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">S/.</span>
                            <input type="number" class="form-control" id="efectivo_contado" name="efectivo_contado"
                                   step="0.01" min="0" required>
                        </div>
                        <small class="text-muted">Cuente todo el efectivo físico (billetes + monedas)</small>
                    </div>

                    <div class="mb-3">
                        <label for="yape_contado" class="form-label">
                            <i class="bi bi-phone"></i> Yape Total *
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">S/.</span>
                            <input type="number" class="form-control" id="yape_contado" name="yape_contado"
                                   step="0.01" min="0" value="<?php echo e($caja_abierta['yape_total_registrado']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observacion" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observacion" name="observacion" rows="3"
                                  placeholder="Ingrese cualquier observación o incidencia..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="bi bi-lock"></i> Cerrar Caja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>
