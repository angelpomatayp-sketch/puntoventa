<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Nueva Unidad de Medida';
include '../layouts/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo e(BASE_URL); ?>/views/super_admin/dashboard.php">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo e(BASE_URL); ?>/views/super_admin/unidades_medida.php">Unidades de Medida</a>
                </li>
                <li class="breadcrumb-item active">Nueva Unidad</li>
            </ol>
        </nav>
        <h2><i class="bi bi-plus-circle"></i> Nueva Unidad de Medida</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-rulers"></i> Datos de la Unidad</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo e(BASE_URL); ?>/controllers/UnidadMedidaController.php?action=crear" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" required
                                   placeholder="Ej: Kilogramo, Litro, Unidad"
                                   maxlength="50">
                            <small class="text-muted">Nombre completo de la unidad de medida</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Abreviatura <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="abreviatura" required
                                   placeholder="Ej: kg, L, und"
                                   maxlength="10"
                                   style="text-transform: lowercase;">
                            <small class="text-muted">Símbolo o abreviatura</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"
                                  placeholder="Descripción opcional de la unidad de medida"
                                  maxlength="255"></textarea>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/unidades_medida.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Crear Unidad de Medida
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Ejemplos comunes</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Abrev.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Unidad</td><td>und</td></tr>
                        <tr><td>Kilogramo</td><td>kg</td></tr>
                        <tr><td>Gramo</td><td>g</td></tr>
                        <tr><td>Litro</td><td>L</td></tr>
                        <tr><td>Mililitro</td><td>ml</td></tr>
                        <tr><td>Metro</td><td>m</td></tr>
                        <tr><td>Docena</td><td>doc</td></tr>
                        <tr><td>Caja</td><td>cja</td></tr>
                        <tr><td>Paquete</td><td>paq</td></tr>
                        <tr><td>Bolsa</td><td>bls</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Importante</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0 small">
                    <li>El nombre y la abreviatura deben ser únicos.</li>
                    <li>La abreviatura se convertirá a minúsculas automáticamente.</li>
                    <li>Las unidades activas estarán disponibles para asignar a productos.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
