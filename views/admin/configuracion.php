<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Configuración de la Tienda';
include '../layouts/header.php';

// MULTI-TENANT: Obtener configuración de la tienda actual
$tienda = getTiendaActual();

if (!$tienda) {
    die('<div class="alert alert-danger">Error: No se encontró la tienda</div>');
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-gear"></i> Configuración de la Tienda</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Formulario de datos de tienda -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Datos del Negocio</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=actualizar">
                    <input type="hidden" name="tienda_id" value="<?php echo e($tienda['id']); ?>">

                    <div class="mb-3">
                        <label class="form-label">Nombre del Negocio *</label>
                        <input type="text" class="form-control" name="nombre_negocio"
                               value="<?php echo e($tienda['nombre_negocio']); ?>" required>
                        <small class="text-muted">Aparece en los tickets y reportes</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RUC *</label>
                            <input type="text" class="form-control" name="ruc"
                                   value="<?php echo e($tienda['ruc']); ?>" maxlength="11" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono"
                                   value="<?php echo e($tienda['telefono']); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dirección *</label>
                        <input type="text" class="form-control" name="direccion"
                               value="<?php echo e($tienda['direccion']); ?>" required>
                        <small class="text-muted">Dirección completa del negocio</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email"
                               value="<?php echo e($tienda['email']); ?>">
                        <small class="text-muted">Email de contacto (opcional)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Porcentaje de IGV *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="igv"
                                   value="<?php echo e($tienda['igv']); ?>" step="0.01" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Tasa de IGV aplicable (generalmente 18%)</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>

        <!-- FASE 8: Sección de Logo -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-image"></i> Logo de la Tienda</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">El logo aparecerá en todos los tickets de venta impresos</p>

                <?php if ($tienda['logo']): ?>
                <div class="text-center mb-4 p-3" style="background: #f8f9fa; border-radius: 8px;">
                    <p class="mb-2"><strong>Logo Actual:</strong></p>
                    <img src="<?php echo e(getLogoUrl($tienda['logo'])); ?>"
                         alt="Logo actual"
                         class="img-thumbnail"
                         style="max-width: 200px; max-height: 150px; object-fit: contain;">
                    <div class="mt-3">
                        <a href="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=eliminar_logo&csrf_token=<?php echo e(getCsrfToken()); ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('¿Está seguro de eliminar el logo actual?')">
                            <i class="bi bi-trash"></i> Eliminar Logo
                        </a>
                    </div>
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
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            Formatos permitidos: PNG, JPG • Tamaño máximo: 2MB • Dimensiones recomendadas: 200x100px
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i>
                        <?php echo e($tienda['logo'] ? 'Actualizar Logo' : 'Subir Logo'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Vista previa del ticket -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Vista Previa del Ticket</h5>
            </div>
            <div class="card-body">
                <div class="ticket-preview" style="transform: scale(0.85); transform-origin: top; font-family: 'Courier New', monospace; font-size: 12px;">
                    <!-- Logo -->
                    <?php if ($tienda['logo']): ?>
                    <div class="text-center mb-2">
                        <img src="<?php echo e(getLogoUrl($tienda['logo'])); ?>"
                             alt="Logo"
                             style="max-width: 120px; max-height: 60px; object-fit: contain;">
                    </div>
                    <?php endif; ?>

                    <!-- Encabezado -->
                    <div style="text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 16px;"><strong><?php echo e($tienda['nombre_negocio']); ?></strong></h4>
                        <p style="margin: 3px 0; font-size: 11px;">RUC: <?php echo e($tienda['ruc']); ?></p>
                        <p style="margin: 3px 0; font-size: 11px;"><?php echo e($tienda['direccion']); ?></p>
                        <?php if ($tienda['telefono']): ?>
                        <p style="margin: 3px 0; font-size: 11px;">Tel: <?php echo e($tienda['telefono']); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Info ticket -->
                    <div style="text-align: center; margin: 10px 0;">
                        <p style="margin: 5px 0;"><strong>BOLETA: T00000001</strong></p>
                        <p style="margin: 5px 0; font-size: 10px;"><?php echo e(date('d/m/Y H:i:s')); ?></p>
                    </div>

                    <!-- Productos -->
                    <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin: 10px 0;">
                        <table style="width: 100%; font-size: 10px;">
                            <tr>
                                <td>1</td>
                                <td>Producto Ejemplo</td>
                                <td style="text-align: right;">S/. 10.00</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Total -->
                    <div style="text-align: right; font-size: 13px; margin: 10px 0;">
                        <strong>TOTAL: S/. 10.00</strong>
                    </div>

                    <!-- Footer -->
                    <div style="text-align: center; border-top: 2px dashed #000; padding-top: 10px; margin-top: 10px;">
                        <p style="margin: 5px 0;"><strong>¡GRACIAS POR SU COMPRA!</strong></p>
                        <p style="margin: 3px 0; font-size: 9px;">Impulsado por Servicios y Proyectos DYM S.A.C. - Soporte y Ventas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de la tienda -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información de la Tienda</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Estado:</strong></td>
                        <td>
                            <span class="badge bg-<?php echo e($tienda['estado'] === 'ACTIVA' ? 'success' : 'danger'); ?>">
                                <?php echo e($tienda['estado']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Slug:</strong></td>
                        <td><code><?php echo e($tienda['slug']); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Activación:</strong></td>
                        <td><?php echo e(date('d/m/Y', strtotime($tienda['fecha_activacion']))); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Última actualización:</strong></td>
                        <td><?php echo e(date('d/m/Y H:i', strtotime($tienda['updated_at']))); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Info del sistema -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Sistema</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Versión:</strong></td>
                        <td>2.0.0 Multi-Tenant</td>
                    </tr>
                    <tr>
                        <td><strong>PHP:</strong></td>
                        <td><?php echo e(phpversion()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Base de Datos:</strong></td>
                        <td>MySQL</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
