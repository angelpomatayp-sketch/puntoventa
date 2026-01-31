<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Gestión de Tiendas';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener todas las tiendas con información de suscripción
$stmt = $db->query("
    SELECT t.*,
           (SELECT COUNT(*) FROM usuarios WHERE tienda_id = t.id) as total_usuarios,
           (SELECT COUNT(*) FROM ventas WHERE tienda_id = t.id AND estado = 'PAGADA') as total_ventas,
           (SELECT SUM(total) FROM ventas WHERE tienda_id = t.id AND estado = 'PAGADA') as monto_ventas,
           (SELECT MAX(fecha_pago) FROM pagos_suscripcion WHERE tienda_id = t.id) as fecha_ultimo_pago
    FROM tiendas t
    ORDER BY t.fecha_proximo_pago ASC, t.estado DESC
");
$tiendas = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="bi bi-shop-window"></i> Gestión de Tiendas</h2>
        <p class="text-muted">Administrar todas las tiendas del sistema</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/crear_tienda.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Tienda
        </a>
    </div>
</div>

<!-- Tabla de Tiendas -->
<div class="card">
    <div class="card-body">
        <?php if (count($tiendas) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tienda</th>
                        <th>RUC</th>
                        <th>Plan</th>
                        <th>Suscripción</th>
                        <th>Último Pago</th>
                        <th>Estado</th>
                        <th class="text-center">Usuarios</th>
                        <th class="text-center">Ventas</th>
                        <th class="text-end">Monto Total</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tiendas as $tienda):
                        $estadoSub = getEstadoSuscripcion($tienda['fecha_proximo_pago'], $tienda['estado']);
                    ?>
                    <tr>
                        <td><?php echo e($tienda['id']); ?></td>
                        <td>
                            <?php if ($tienda['logo']): ?>
                                <img src="<?php echo e(getLogoUrl($tienda['logo'])); ?>"
                                     alt="Logo"
                                     style="max-width: 40px; max-height: 30px; margin-right: 8px; object-fit: contain;">
                            <?php endif; ?>
                            <strong><?php echo e($tienda['nombre_negocio']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo e($tienda['slug']); ?></small>
                        </td>
                        <td><?php echo e($tienda['ruc']); ?></td>
                        <td>
                            <span class="badge bg-secondary"><?php echo e($tienda['plan']); ?></span>
                            <br>
                            <small class="text-muted"><?php echo e(formatMoney($tienda['monto_mensual'])); ?>/mes</small>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo e($estadoSub['clase']); ?>">
                                <i class="bi bi-<?php echo e($estadoSub['icono']); ?>"></i>
                                <?php echo e($estadoSub['texto']); ?>
                            </span>
                            <?php if ($tienda['fecha_proximo_pago']): ?>
                            <br><small class="text-muted">Vence: <?php echo e(date('d/m/Y', strtotime($tienda['fecha_proximo_pago']))); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($tienda['fecha_ultimo_pago']): ?>
                                <?php echo e(date('d/m/Y', strtotime($tienda['fecha_ultimo_pago']))); ?>
                            <?php else: ?>
                                <small class="text-muted">Sin pagos</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($tienda['estado'] === 'ACTIVA'): ?>
                                <span class="badge bg-success">ACTIVA</span>
                            <?php else: ?>
                                <span class="badge bg-danger">SUSPENDIDA</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info"><?php echo e($tienda['total_usuarios']); ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary"><?php echo e($tienda['total_ventas']); ?></span>
                        </td>
                        <td class="text-end">
                            <strong><?php echo e(formatMoney($tienda['monto_ventas'] ?? 0)); ?></strong>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm mb-1" role="group">
                                <a href="<?php echo e(BASE_URL); ?>/views/super_admin/detalle_tienda.php?id=<?php echo e($tienda['id']); ?>"
                                   class="btn btn-info"
                                   title="Ver Detalles">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo e(BASE_URL); ?>/views/super_admin/editar_tienda.php?id=<?php echo e($tienda['id']); ?>"
                                   class="btn btn-warning"
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-success btn-registrar-pago"
                                        data-id="<?php echo e($tienda['id']); ?>"
                                        data-nombre="<?php echo e($tienda['nombre_negocio']); ?>"
                                        data-monto="<?php echo e($tienda['monto_mensual']); ?>"
                                        title="Registrar Pago">
                                    <i class="bi bi-cash-coin"></i>
                                </button>
                                <a href="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=eliminar_tienda&id=<?php echo e($tienda['id']); ?>&csrf_token=<?php echo e(getCsrfToken()); ?>"
                                   class="btn btn-danger"
                                   title="Eliminar Tienda"
                                   onclick="return confirm('Esta accion es permanente. Se eliminara la tienda y toda su informacion. ¿Continuar?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <a href="<?php echo e(BASE_URL); ?>/views/super_admin/historial_pagos.php?tienda_id=<?php echo e($tienda['id']); ?>"
                                   class="btn btn-secondary"
                                   title="Historial de Pagos">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                            </div>
                            <br>
                            <div class="btn-group btn-group-sm" role="group">
                                <?php if ($tienda['estado'] === 'ACTIVA'): ?>
                                    <a href="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=suspender_tienda_completa&id=<?php echo e($tienda['id']); ?>&csrf_token=<?php echo e(getCsrfToken()); ?>"
                                       class="btn btn-danger"
                                       title="Suspender Tienda Completa"
                                       onclick="return confirm('¿Suspender esta tienda Y TODOS sus usuarios? No podrán ingresar al sistema.')">
                                        <i class="bi bi-pause-circle"></i> Suspender Todo
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=activar_tienda_completa&id=<?php echo e($tienda['id']); ?>&csrf_token=<?php echo e(getCsrfToken()); ?>"
                                       class="btn btn-success"
                                       title="Activar Tienda Completa"
                                       onclick="return confirm('¿Activar esta tienda Y TODOS sus usuarios?')">
                                        <i class="bi bi-play-circle"></i> Activar Todo
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-shop-window" style="font-size: 4rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No hay tiendas registradas</p>
            <a href="<?php echo e(BASE_URL); ?>/views/super_admin/crear_tienda.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Primera Tienda
            </a>
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
                    <input type="hidden" name="tienda_id" id="pago_tienda_id">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Tienda:</strong> <span id="pago_tienda_nombre"></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Monto a Pagar *</label>
                            <div class="input-group">
                                <span class="input-group-text">S/.</span>
                                <input type="number" class="form-control" name="monto" id="pago_monto"
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
                               id="activar_tienda" checked>
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
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const monto = $(this).data('monto');

        $('#pago_tienda_id').val(id);
        $('#pago_tienda_nombre').text(nombre);
        $('#pago_monto').val(monto);

        $('#modalRegistrarPago').modal('show');
    });
});
</script>
";
include '../layouts/footer.php';
?>
