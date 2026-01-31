<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Nueva Categoría Global';
include '../layouts/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="bi bi-plus-circle"></i> Crear Nueva Categoría Global</h2>
        <p class="text-muted">Las categorías globales pueden ser asignadas a múltiples tiendas</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Datos de la Categoría</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/CategoriaGlobalController.php?action=crear">

                    <div class="mb-3">
                        <label for="tipo_negocio" class="form-label">Tipo de Negocio *</label>
                        <select class="form-select" id="tipo_negocio" name="tipo_negocio" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="ALIMENTOS">Alimentos (Bodegas, Restaurantes, Fast Food)</option>
                            <option value="FERRETERIA">Ferretería (Materiales, Herramientas)</option>
                            <option value="FARMACIA">Farmacia (Medicamentos, Cuidado Personal)</option>
                            <option value="ROPA">Ropa y Accesorios</option>
                            <option value="TECNOLOGIA">Tecnología (Electrónica, Computadoras)</option>
                            <option value="LIMPIEZA">Limpieza</option>
                            <option value="LIBRERIA">Librería (Útiles Escolares)</option>
                            <option value="MASCOTAS">Mascotas</option>
                            <option value="VARIADO">Variado (Otros)</option>
                        </select>
                        <small class="text-muted">Agrupa la categoría según el tipo de negocio</small>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                               required minlength="3" maxlength="100"
                               placeholder="Ej: Bebidas, Herramientas, Medicamentos">
                        <small class="text-muted">Nombre descriptivo y único para la categoría</small>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion"
                                  rows="3" maxlength="255"
                                  placeholder="Descripción opcional de la categoría"></textarea>
                        <small class="text-muted">Ayuda a las tiendas a entender qué productos van en esta categoría</small>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Nota:</strong> Una vez creada, esta categoría estará disponible para asignar a las tiendas.
                        Las tiendas solo verán las categorías que les hayas asignado.
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo e(BASE_URL); ?>/views/super_admin/categorias_globales.php"
                           class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Crear Categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Consejos</h6>
            </div>
            <div class="card-body">
                <h6>Buenas prácticas:</h6>
                <ul class="small">
                    <li>Usa nombres claros y descriptivos</li>
                    <li>Agrupa categorías similares por tipo de negocio</li>
                    <li>Evita duplicados (revisa las existentes primero)</li>
                    <li>Sé específico cuando sea necesario</li>
                </ul>

                <hr>

                <h6>Ejemplos por tipo:</h6>
                <p class="small mb-1"><strong>Alimentos:</strong> Bebidas, Lácteos, Snacks</p>
                <p class="small mb-1"><strong>Ferretería:</strong> Herramientas, Pinturas</p>
                <p class="small mb-1"><strong>Farmacia:</strong> Medicamentos, Vitaminas</p>
                <p class="small mb-1"><strong>Ropa:</strong> Ropa Hombre, Calzado</p>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
