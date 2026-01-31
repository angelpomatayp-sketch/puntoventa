<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Editar Tienda';
include '../layouts/header.php';

// Obtener tienda
$tienda_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM tiendas WHERE id = :id");
$stmt->execute(['id' => $tienda_id]);
$tienda = $stmt->fetch();

if (!$tienda) {
    setFlashMessage('Tienda no encontrada', 'danger');
    redirect('/views/super_admin/tiendas.php');
    exit;
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-pencil-square"></i> Editar Tienda</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo e(BASE_URL); ?>/views/super_admin/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php">Tiendas</a></li>
                <li class="breadcrumb-item active"><?php echo e($tienda['nombre_negocio']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <!-- Formulario de Edición -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-building"></i> Datos del Negocio</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=actualizar">
                    <input type="hidden" name="tienda_id" value="<?php echo e($tienda['id']); ?>">

                    <div class="mb-3">
                        <label class="form-label">Nombre del Negocio *</label>
                        <input type="text" class="form-control" name="nombre_negocio"
                               value="<?php echo e($tienda['nombre_negocio']); ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">RUC *</label>
                            <input type="text" class="form-control" name="ruc"
                                   value="<?php echo e($tienda['ruc']); ?>" maxlength="11" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug (URL)</label>
                            <input type="text" class="form-control" value="<?php echo e($tienda['slug']); ?>" disabled>
                            <small class="text-muted">El slug no se puede modificar</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dirección *</label>
                        <input type="text" class="form-control" name="direccion"
                               value="<?php echo e($tienda['direccion']); ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono"
                                   value="<?php echo e($tienda['telefono']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                   value="<?php echo e($tienda['email']); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Porcentaje de IGV *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="igv"
                                   value="<?php echo e($tienda['igv']); ?>" step="0.01" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-credit-card"></i> Configuración de Suscripción</h6>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Plan *</label>
                            <select class="form-select" name="plan" required>
                                <option value="BASICO" <?php echo e($tienda['plan'] === 'BASICO' ? 'selected' : ''); ?>>Básico</option>
                                <option value="PROFESIONAL" <?php echo e($tienda['plan'] === 'PROFESIONAL' ? 'selected' : ''); ?>>Profesional</option>
                                <option value="EMPRESARIAL" <?php echo e($tienda['plan'] === 'EMPRESARIAL' ? 'selected' : ''); ?>>Empresarial</option>
                                <option value="PREMIUM" <?php echo e($tienda['plan'] === 'PREMIUM' ? 'selected' : ''); ?>>Premium</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monto Mensual *</label>
                            <div class="input-group">
                                <span class="input-group-text">S/.</span>
                                <input type="number" class="form-control" name="monto_mensual"
                                       value="<?php echo e($tienda['monto_mensual']); ?>" step="0.01" min="0" required>
                            </div>
                            <small class="text-muted">Costo mensual de la suscripción</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas de Administración</label>
                        <textarea class="form-control" name="notas_admin" rows="3"
                                  placeholder="Notas internas sobre esta tienda (no visibles para el cliente)"><?php echo e($tienda['notas_admin']); ?></textarea>
                        <small class="text-muted">Ej: "Cliente corporativo - Descuento del 10%", "Contacto directo con gerente"</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>

        <!-- Gestión de Logo -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-image"></i> Logo de la Tienda</h5>
            </div>
            <div class="card-body">
                <?php if ($tienda['logo']): ?>
                <div class="text-center mb-4">
                    <img src="<?php echo e(getLogoUrl($tienda['logo'])); ?>"
                         alt="Logo actual"
                         class="img-thumbnail"
                         style="max-width: 200px; max-height: 150px; object-fit: contain;">
                    <div class="mt-2">
                        <a href="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=eliminar_logo&tienda_id=<?php echo e($tienda['id']); ?>&csrf_token=<?php echo e(getCsrfToken()); ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('¿Eliminar logo actual?')">
                            <i class="bi bi-trash"></i> Eliminar Logo
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Esta tienda no tiene logo personalizado. Los tickets usarán el logo por defecto.
                </div>
                <?php endif; ?>

                <form method="POST"
                      action="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=actualizar_logo"
                      enctype="multipart/form-data">
                    <input type="hidden" name="tienda_id" value="<?php echo e($tienda['id']); ?>">

                    <div class="mb-3">
                        <label class="form-label">
                            <?php echo e($tienda['logo'] ? 'Cambiar Logo' : 'Subir Logo'); ?>
                        </label>
                        <input type="file" class="form-control" name="logo"
                               accept="image/png, image/jpeg" required>
                        <small class="text-muted">Formatos: PNG, JPG. Tamaño máximo: 2MB. Recomendado: 200x100px</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i>
                        <?php echo e($tienda['logo'] ? 'Actualizar' : 'Subir'); ?> Logo
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Información Lateral -->
    <div class="col-md-4">
        <!-- Estado de Suscripción -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-credit-card"></i> Estado de Suscripción</h6>
            </div>
            <div class="card-body">
                <?php
                $estadoSub = getEstadoSuscripcion($tienda['fecha_proximo_pago'], $tienda['estado']);
                ?>
                <div class="text-center mb-3">
                    <h4>
                        <span class="badge bg-<?php echo e($estadoSub['clase']); ?>">
                            <i class="bi bi-<?php echo e($estadoSub['icono']); ?>"></i>
                            <?php echo e($estadoSub['texto']); ?>
                        </span>
                    </h4>
                </div>

                <div class="mb-2">
                    <strong>Plan:</strong>
                    <span class="badge bg-secondary"><?php echo e($tienda['plan']); ?></span>
                </div>

                <div class="mb-2">
                    <strong>Monto Mensual:</strong><br>
                    <span class="h5 text-primary"><?php echo e(formatMoney($tienda['monto_mensual'])); ?></span>
                </div>

                <?php if ($tienda['fecha_ultimo_pago']): ?>
                <div class="mb-2">
                    <strong>Último Pago:</strong><br>
                    <?php echo e(date('d/m/Y', strtotime($tienda['fecha_ultimo_pago']))); ?>
                </div>
                <?php endif; ?>

                <?php if ($tienda['fecha_proximo_pago']): ?>
                <div class="mb-3">
                    <strong>Próximo Pago:</strong><br>
                    <?php echo e(date('d/m/Y', strtotime($tienda['fecha_proximo_pago']))); ?>
                </div>
                <?php endif; ?>

                <hr>

                <a href="<?php echo e(BASE_URL); ?>/views/super_admin/historial_pagos.php?tienda_id=<?php echo e($tienda['id']); ?>"
                   class="btn btn-secondary w-100 mb-2">
                    <i class="bi bi-clock-history"></i> Ver Historial de Pagos
                </a>

                <button class="btn btn-success w-100 btn-registrar-pago"
                        data-id="<?php echo e($tienda['id']); ?>"
                        data-nombre="<?php echo e($tienda['nombre_negocio']); ?>"
                        data-monto="<?php echo e($tienda['monto_mensual']); ?>">
                    <i class="bi bi-cash-coin"></i> Registrar Pago
                </button>
            </div>
        </div>

        <!-- Estado de la Tienda -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Estado de la Tienda</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Estado Actual:</strong><br>
                    <?php if ($tienda['estado'] === 'ACTIVA'): ?>
                        <span class="badge bg-success">ACTIVA</span>
                    <?php else: ?>
                        <span class="badge bg-danger">SUSPENDIDA</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <strong>Fecha de Activación:</strong><br>
                    <?php echo e($tienda['fecha_activacion'] ? date('d/m/Y', strtotime($tienda['fecha_activacion'])) : 'N/A'); ?>
                </div>

                <div class="mb-3">
                    <strong>Fecha de Creación:</strong><br>
                    <?php echo e(formatDate($tienda['created_at'])); ?>
                </div>

                <hr>

                <?php if ($tienda['estado'] === 'ACTIVA'): ?>
                    <a href="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=suspender_tienda_completa&id=<?php echo e($tienda['id']); ?>&csrf_token=<?php echo e(getCsrfToken()); ?>"
                       class="btn btn-danger w-100"
                       onclick="return confirm('¿Suspender esta tienda Y TODOS sus usuarios? No podrán ingresar al sistema.')">
                        <i class="bi bi-pause-circle"></i> Suspender Todo
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=activar_tienda_completa&id=<?php echo e($tienda['id']); ?>&csrf_token=<?php echo e(getCsrfToken()); ?>"
                       class="btn btn-success w-100"
                       onclick="return confirm('¿Activar esta tienda Y TODOS sus usuarios?')">
                        <i class="bi bi-play-circle"></i> Activar Todo
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning-fill"></i> Acciones Rápidas</h6>
            </div>
            <div class="card-body">
                <a href="<?php echo e(BASE_URL); ?>/views/super_admin/detalle_tienda.php?id=<?php echo e($tienda['id']); ?>"
                   class="btn btn-info w-100 mb-2">
                    <i class="bi bi-eye"></i> Ver Detalle y Estadísticas
                </a>
                <a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php"
                   class="btn btn-secondary w-100">
                    <i class="bi bi-arrow-left"></i> Volver a Lista
                </a>
            </div>
        </div>
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
                    <input type="hidden" name="tienda_id" id="pago_tienda_id" value="<?php echo e($tienda['id']); ?>">

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
        const monto = $(this).data('monto');
        $('#pago_monto').val(monto);
        $('#modalRegistrarPago').modal('show');
    });
});
</script>
";
include '../layouts/footer.php';
?>
