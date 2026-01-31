<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Ventas Anuladas';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener ventas anuladas
$stmt = $db->query("
    SELECT v.*, u.nombre as cajero, a.nombre as anulo
    FROM ventas v
    INNER JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN usuarios a ON v.anulado_por = a.id
    WHERE v.estado = 'ANULADA'
    ORDER BY v.fecha_anulacion DESC
    LIMIT 100
");
$ventas_anuladas = $stmt->fetchAll();

// Obtener ventas del día que pueden ser anuladas
$stmt = $db->query("
    SELECT v.*, u.nombre as cajero
    FROM ventas v
    INNER JOIN usuarios u ON v.usuario_id = u.id
    WHERE DATE(v.fecha_hora) = CURDATE() AND v.estado = 'PAGADA'
    ORDER BY v.fecha_hora DESC
");
$ventas_dia = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-x-circle"></i> Ventas Anuladas</h2>
    </div>
</div>

<!-- Ventas del Día (Para Anular) -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Ventas del Día (Disponibles para Anular)</h5>
            </div>
            <div class="card-body">
                <?php if (count($ventas_dia) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Fecha/Hora</th>
                                <th>Cajero</th>
                                <th>Total</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas_dia as $venta): ?>
                            <tr>
                                <td><strong><?php echo e($venta['nro_ticket']); ?></strong></td>
                                <td><?php echo e(formatDate($venta['fecha_hora'])); ?></td>
                                <td><?php echo e($venta['cajero']); ?></td>
                                <td class="text-end"><?php echo e(formatMoney($venta['total'])); ?></td>
                                <td class="text-center">
                                    <a href="<?php echo e(BASE_URL); ?>/views/pos/ticket.php?id=<?php echo e($venta['id']); ?>"
                                       class="btn btn-sm btn-info" target="_blank">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-anular"
                                            data-id="<?php echo e($venta['id']); ?>"
                                            data-ticket="<?php echo e($venta['nro_ticket']); ?>"
                                            data-total="<?php echo e(formatMoney($venta['total'])); ?>">
                                        <i class="bi bi-x-circle"></i> Anular
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> No hay ventas registradas hoy.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Historial de Ventas Anuladas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-archive"></i> Historial de Ventas Anuladas</h5>
            </div>
            <div class="card-body">
                <?php if (count($ventas_anuladas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Fecha Venta</th>
                                <th>Cajero</th>
                                <th>Total</th>
                                <th>Anulado Por</th>
                                <th>Fecha Anulación</th>
                                <th>Motivo</th>
                                <th class="text-center">Ver</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas_anuladas as $venta): ?>
                            <tr>
                                <td><strong><?php echo e($venta['nro_ticket']); ?></strong></td>
                                <td><?php echo e(formatDate($venta['fecha_hora'])); ?></td>
                                <td><?php echo e($venta['cajero']); ?></td>
                                <td class="text-end"><?php echo e(formatMoney($venta['total'])); ?></td>
                                <td><?php echo e($venta['anulo']); ?></td>
                                <td><?php echo e(formatDate($venta['fecha_anulacion'])); ?></td>
                                <td><small><?php echo e($venta['motivo_anulacion']); ?></small></td>
                                <td class="text-center">
                                    <a href="<?php echo e(BASE_URL); ?>/views/pos/ticket.php?id=<?php echo e($venta['id']); ?>"
                                       class="btn btn-sm btn-secondary" target="_blank">
                                        <i class="bi bi-file-text"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> No hay ventas anuladas registradas.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Anular Venta -->
<div class="modal fade" id="modalAnular" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Anular Venta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/VentaController.php?action=anular">
                <div class="modal-body">
                    <input type="hidden" name="venta_id" id="venta_id">

                    <div class="alert alert-danger">
                        <strong><i class="bi bi-exclamation-triangle"></i> ¡Atención!</strong>
                        <p class="mb-0 mt-2">Está por anular la venta:</p>
                        <p class="mb-0"><strong>Ticket:</strong> <span id="ticket_info"></span></p>
                        <p class="mb-0"><strong>Total:</strong> <span id="total_info"></span></p>
                    </div>

                    <div class="alert alert-warning">
                        <small>
                            <strong>Consecuencias:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Se repondrá el stock de los productos</li>
                                <li>Se ajustarán los totales de la caja</li>
                                <li>Esta acción quedará registrada</li>
                            </ul>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo de Anulación *</label>
                        <textarea class="form-control" id="motivo" name="motivo" rows="4" required
                                  placeholder="Ingrese el motivo detallado de la anulación..."></textarea>
                        <small class="text-muted">Este motivo quedará registrado permanentemente</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Anulación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$custom_js = "
<script>
$(document).ready(function() {
    $('.btn-anular').click(function() {
        $('#venta_id').val($(this).data('id'));
        $('#ticket_info').text($(this).data('ticket'));
        $('#total_info').text($(this).data('total'));
        $('#motivo').val('');
        $('#modalAnular').modal('show');
    });
});
</script>
";
include '../layouts/footer.php';
?>
