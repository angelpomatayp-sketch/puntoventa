<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Gestión de Cajas';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener caja abierta (si hay)
$caja_abierta = getCajaAbierta();

// Obtener cajas cerradas pendientes de validación
$sql_pendientes = "
    SELECT c.*, u.nombre as usuario
    FROM cajas c
    INNER JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.estado = 'CERRADA'
";
if (!isSuperAdmin()) {
    $sql_pendientes .= " AND c.tienda_id = :tienda_id";
}
$sql_pendientes .= " ORDER BY c.fecha_cierre DESC LIMIT 20";
$stmt = $db->prepare($sql_pendientes);
$params = [];
if (!isSuperAdmin()) {
    $params['tienda_id'] = getTiendaId();
}
$stmt->execute($params);
$cajas_pendientes = $stmt->fetchAll();

// Obtener todas las cajas (historial)
$sql_historial = "
    SELECT c.*, u.nombre as usuario, v.nombre as validador
    FROM cajas c
    INNER JOIN usuarios u ON c.usuario_id = u.id
    LEFT JOIN usuarios v ON c.validado_por = v.id
    WHERE 1=1
";
if (!isSuperAdmin()) {
    $sql_historial .= " AND c.tienda_id = :tienda_id";
}
$sql_historial .= " ORDER BY c.id DESC LIMIT 50";
$stmt = $db->prepare($sql_historial);
$params = [];
if (!isSuperAdmin()) {
    $params['tienda_id'] = getTiendaId();
}
$stmt->execute($params);
$cajas = $stmt->fetchAll();
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="bi bi-cash-stack"></i> Gestión de Cajas</h2>
        <p class="text-muted">Visualiza y valida las cajas de los cajeros</p>
    </div>
</div>

<!-- Caja actualmente abierta -->
<?php if ($caja_abierta): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-unlock"></i> Caja Actualmente Abierta</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Caja #:</strong> <?php echo e($caja_abierta['id']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Cajero:</strong>
                        <?php
                        $stmt = $db->prepare("SELECT nombre FROM usuarios WHERE id = :id");
                        $stmt->execute(['id' => $caja_abierta['usuario_id']]);
                        $cajero = $stmt->fetch();
                        echo $cajero['nombre'];
                        ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Saldo Inicial:</strong> <?php echo e(formatMoney($caja_abierta['saldo_inicial'])); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Apertura:</strong> <?php echo e(formatDate($caja_abierta['fecha_apertura'])); ?>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted">Efectivo Esperado</small>
                                <h4 class="text-success mb-0"><?php echo e(formatMoney($caja_abierta['saldo_inicial'] + $caja_abierta['efectivo_esperado'])); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted">Yape Registrado</small>
                                <h4 class="text-primary mb-0"><?php echo e(formatMoney($caja_abierta['yape_total_registrado'])); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted">Total General</small>
                                <h4 class="text-dark mb-0"><?php echo e(formatMoney($caja_abierta['saldo_inicial'] + $caja_abierta['efectivo_esperado'] + $caja_abierta['yape_total_registrado'])); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 text-center">
                        <button class="btn btn-info" onclick="window.open('<?php echo e(BASE_URL); ?>/views/admin/detalle_caja.php?id=<?php echo e($caja_abierta['id']); ?>', '_blank')">
                            <i class="bi bi-eye"></i> Ver Detalle Completo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No hay ninguna caja abierta actualmente. Los cajeros deben abrir su caja para poder vender.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cajas cerradas pendientes de validación -->
<?php if (count($cajas_pendientes) > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Cajas Pendientes de Validación (<?php echo e(count($cajas_pendientes)); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Caja #</th>
                                <th>Cajero</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Efectivo Contado</th>
                                <th>Diferencia</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cajas_pendientes as $caja): ?>
                            <tr>
                                <td><strong>#<?php echo e($caja['id']); ?></strong></td>
                                <td><?php echo e($caja['usuario']); ?></td>
                                <td><?php echo e(date('d/m/Y H:i', strtotime($caja['fecha_apertura']))); ?></td>
                                <td><?php echo e(date('d/m/Y H:i', strtotime($caja['fecha_cierre']))); ?></td>
                                <td><?php echo e(formatMoney($caja['efectivo_contado'])); ?></td>
                                <td>
                                    <?php
                                    $diff = $caja['diferencia_efectivo'];
                                    if ($diff > 0) {
                                        echo '<span class="badge bg-success">+' . formatMoney($diff) . '</span>';
                                    } elseif ($diff < 0) {
                                        echo '<span class="badge bg-danger">' . formatMoney($diff) . '</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">' . formatMoney(0) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info btn-ver"
                                            data-id="<?php echo e($caja['id']); ?>">
                                        <i class="bi bi-eye"></i> Ver
                                    </button>
                                    <button class="btn btn-sm btn-success btn-validar"
                                            data-id="<?php echo e($caja['id']); ?>">
                                        <i class="bi bi-check-circle"></i> Validar
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
<?php endif; ?>

<!-- Historial de todas las cajas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Cajas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cajero</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Saldo Inicial</th>
                                <th>Efectivo</th>
                                <th>Yape</th>
                                <th>Diferencia</th>
                                <th>Estado</th>
                                <th>Validado Por</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cajas as $caja): ?>
                            <tr>
                                <td><strong>#<?php echo e($caja['id']); ?></strong></td>
                                <td><?php echo e($caja['usuario']); ?></td>
                                <td><?php echo e(date('d/m/Y H:i', strtotime($caja['fecha_apertura']))); ?></td>
                                <td><?php echo e($caja['fecha_cierre'] ? date('d/m/Y H:i', strtotime($caja['fecha_cierre'])) : '-'); ?></td>
                                <td><?php echo e(formatMoney($caja['saldo_inicial'])); ?></td>
                                <td><?php echo e(formatMoney($caja['efectivo_contado'] ?? 0)); ?></td>
                                <td><?php echo e(formatMoney($caja['yape_total_registrado'])); ?></td>
                                <td>
                                    <?php
                                    $diff = $caja['diferencia_efectivo'];
                                    if ($diff > 0) {
                                        echo '<span class="text-success">+' . formatMoney($diff) . '</span>';
                                    } elseif ($diff < 0) {
                                        echo '<span class="text-danger">' . formatMoney($diff) . '</span>';
                                    } else {
                                        echo '<span class="text-muted">' . formatMoney(0) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($caja['estado'] === 'ABIERTA'): ?>
                                        <span class="badge bg-success">Abierta</span>
                                    <?php elseif ($caja['estado'] === 'CERRADA'): ?>
                                        <span class="badge bg-warning">Pendiente</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Validada</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($caja['validador'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info btn-ver"
                                            data-id="<?php echo e($caja['id']); ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if ($caja['estado'] === 'CERRADA'): ?>
                                    <button class="btn btn-sm btn-success btn-validar"
                                            data-id="<?php echo e($caja['id']); ?>">
                                        <i class="bi bi-check-circle"></i>
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

<!-- Modal Confirmación Validar Caja -->
<div class="modal fade" id="modalConfirmarValidacion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle"></i> Validar Cierre de Caja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-cash-stack text-success" style="font-size: 3rem;"></i>
                <h4 class="mt-3">¿Validar el cierre de caja <span id="cajaNumero" class="text-primary"></span>?</h4>
                <p class="text-muted mt-2">Esto confirmará que el conteo es correcto.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmarValidacion">
                    <i class="bi bi-check-circle"></i> Sí, Validar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$custom_js = "
<script>
$(document).ready(function() {
    let cajaIdAValidar = null;

    // Validar caja con modal de confirmación
    $('.btn-validar').click(function() {
        cajaIdAValidar = $(this).data('id');
        $('#cajaNumero').text('#' + cajaIdAValidar);

        const modal = new bootstrap.Modal(document.getElementById('modalConfirmarValidacion'));
        modal.show();
    });

    // Confirmar validación
    $('#btnConfirmarValidacion').click(function() {
        if (cajaIdAValidar) {
            window.location.href = '" . BASE_URL . "/controllers/CajaController.php?action=validar&id=' + cajaIdAValidar + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
        }
    });

    // Ver detalle de caja
    $('.btn-ver').click(function() {
        const id = $(this).data('id');
        window.open('" . BASE_URL . "/views/admin/detalle_caja.php?id=' + id, '_blank');
    });
});
</script>
";
include '../layouts/footer.php';
?>
