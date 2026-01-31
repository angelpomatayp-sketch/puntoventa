<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Crear Nueva Tienda';
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-plus-circle"></i> Crear Nueva Tienda</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo e(BASE_URL); ?>/views/super_admin/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php">Tiendas</a></li>
                <li class="breadcrumb-item active">Nueva Tienda</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-shop-window"></i> Información de la Nueva Tienda</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/TiendaController.php?action=crear">
                    <!-- Datos de la Tienda -->
                    <h6 class="text-primary mb-3">
                        <i class="bi bi-building"></i> Datos del Negocio
                    </h6>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Nombre del Negocio *</label>
                            <input type="text" class="form-control" name="nombre_negocio" required
                                   placeholder="Ej: Bodega San Juan">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">RUC *</label>
                            <input type="text" class="form-control" name="ruc" required
                                   maxlength="11" pattern="[0-9]{11}"
                                   placeholder="12345678901">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dirección *</label>
                        <input type="text" class="form-control" name="direccion" required
                               placeholder="Av. Principal 123, Lima">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono"
                                   placeholder="987654321">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                   placeholder="contacto@negocio.com">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Datos del Administrador -->
                    <h6 class="text-success mb-3">
                        <i class="bi bi-person-badge"></i> Usuario Administrador de la Tienda
                    </h6>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Importante:</strong> Este será el usuario administrador principal de la tienda.
                        Podrá crear cajeros y gestionar toda la operación de su tienda.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre Completo del Administrador *</label>
                        <input type="text" class="form-control" name="admin_nombre" required
                               placeholder="Juan Pérez García">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre de Usuario *</label>
                            <input type="text" class="form-control" name="admin_usuario" required
                                   placeholder="admin_tienda1"
                                   pattern="[a-zA-Z0-9_]{4,20}"
                                   title="Solo letras, números y guión bajo. Mínimo 4 caracteres.">
                            <small class="form-text text-muted">Solo letras, números y guión bajo</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" name="admin_password" required
                                   minlength="6"
                                   placeholder="Mínimo 6 caracteres">
                            <small class="form-text text-muted">Mínimo 6 caracteres</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Nota sobre lo que se creará -->
                    <div class="alert alert-success">
                        <h6><i class="bi bi-check-circle"></i> Al crear la tienda se generará automáticamente:</h6>
                        <ul class="mb-0">
                            <li>Usuario administrador con los datos proporcionados</li>
                            <li>Cliente "ANÓNIMO" para ventas sin cliente específico</li>
                            <li>Categorías por defecto (Bebidas, Golosinas, Galletas, Variado, Fast Food)</li>
                            <li>Slug único para identificar la tienda (basado en el nombre)</li>
                        </ul>
                    </div>

                    <!-- Botones -->
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Crear Tienda
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
