<?php
require_once '../../config/config.php';
requireSuperAdmin();

$page_title = 'Asignar Categorías a Tienda';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener tienda seleccionada
$tienda_id = isset($_GET['tienda_id']) ? intval($_GET['tienda_id']) : 0;

// Obtener todas las tiendas
$stmt = $db->query("SELECT id, nombre_negocio, estado FROM tiendas ORDER BY nombre_negocio");
$tiendas = $stmt->fetchAll();

$tienda_actual = null;
$categorias_asignadas = [];

if ($tienda_id) {
    // Obtener datos de la tienda
    $stmt = $db->prepare("SELECT * FROM tiendas WHERE id = :id");
    $stmt->execute(['id' => $tienda_id]);
    $tienda_actual = $stmt->fetch();

    // Obtener categorías ya asignadas a esta tienda
    $stmt = $db->prepare("
        SELECT categoria_id FROM tienda_categorias
        WHERE tienda_id = :tienda_id AND activo = 1
    ");
    $stmt->execute(['tienda_id' => $tienda_id]);
    $categorias_asignadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Obtener todas las categorías globales agrupadas por tipo
$stmt = $db->query("
    SELECT * FROM categorias
    WHERE es_global = 1 AND activo = 1
    ORDER BY tipo_negocio, nombre
");
$categorias = $stmt->fetchAll();

// Agrupar por tipo
$categorias_por_tipo = [];
foreach ($categorias as $cat) {
    $categorias_por_tipo[$cat['tipo_negocio']][] = $cat;
}
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="bi bi-diagram-3"></i> Asignar Categorías a Tienda</h2>
        <p class="text-muted">Selecciona las categorías que la tienda podrá usar para sus productos</p>
    </div>
</div>

<!-- Selector de tienda -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label"><strong>Seleccionar Tienda:</strong></label>
                        <select class="form-select" name="tienda_id" required>
                            <option value="">Selecciona una tienda...</option>
                            <?php foreach ($tiendas as $t): ?>
                            <option value="<?php echo e($t['id']); ?>" <?php echo e($t['id'] == $tienda_id ? 'selected' : ''); ?>>
                                <?php echo e($t['nombre_negocio']); ?>
                                <?php if ($t['estado'] === 'SUSPENDIDA'): ?> (SUSPENDIDA)<?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Ver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($tienda_actual): ?>
<!-- Información de la tienda -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-0">
                        <i class="bi bi-shop"></i> <?php echo e($tienda_actual['nombre_negocio']); ?>
                    </h5>
                    <small>RUC: <?php echo e($tienda_actual['ruc']); ?> | Estado: <?php echo e($tienda_actual['estado']); ?></small>
                </div>
                <div class="col-md-4 text-end">
                    <strong class="text-primary"><?php echo e(count($categorias_asignadas)); ?></strong> categorías asignadas
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario de asignación -->
<form method="POST" action="<?php echo e(BASE_URL); ?>/controllers/CategoriaGlobalController.php?action=asignar_a_tienda" id="formAsignar">
    <input type="hidden" name="tienda_id" value="<?php echo e($tienda_id); ?>">

    <!-- Botones de acción superiores -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-success btn-sm" onclick="seleccionarTodas()">
                        <i class="bi bi-check-all"></i> Seleccionar Todas
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="deseleccionarTodas()">
                        <i class="bi bi-x"></i> Deseleccionar Todas
                    </button>
                </div>
                <div>
                    <a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Guardar Asignación
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Categorías agrupadas por tipo -->
    <?php foreach ($categorias_por_tipo as $tipo => $cats): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <?php
                                $tipo_nombres = [
                                    'ALIMENTOS' => 'Alimentos',
                                    'FERRETERIA' => 'Ferretería',
                                    'FARMACIA' => 'Farmacia',
                                    'ROPA' => 'Ropa y Accesorios',
                                    'TECNOLOGIA' => 'Tecnología',
                                    'LIMPIEZA' => 'Limpieza',
                                    'LIBRERIA' => 'Librería',
                                    'MASCOTAS' => 'Mascotas',
                                    'VARIADO' => 'Variado'
                                ];
                                $icono_tipos = [
                                    'ALIMENTOS' => 'egg-fried',
                                    'FERRETERIA' => 'tools',
                                    'FARMACIA' => 'capsule',
                                    'ROPA' => 'bag',
                                    'TECNOLOGIA' => 'laptop',
                                    'LIMPIEZA' => 'droplet',
                                    'LIBRERIA' => 'journal-text',
                                    'MASCOTAS' => 'heart',
                                    'VARIADO' => 'box'
                                ];
                                $icono = $icono_tipos[$tipo] ?? 'folder';
                                ?>
                                <i class="bi bi-<?php echo e($icono); ?>"></i>
                                <?php echo e($tipo_nombres[$tipo] ?? $tipo); ?>
                            </h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="seleccionarTipo('<?php echo e($tipo); ?>')">
                                Seleccionar todas de este tipo
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($cats as $cat): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="form-check">
                                <input class="form-check-input categoria-check categoria-tipo-<?php echo e($tipo); ?>"
                                       type="checkbox"
                                       name="categorias[]"
                                       value="<?php echo e($cat['id']); ?>"
                                       id="cat_<?php echo e($cat['id']); ?>"
                                       <?php echo e(in_array($cat['id'], $categorias_asignadas) ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="cat_<?php echo e($cat['id']); ?>">
                                    <strong><?php echo e($cat['nombre']); ?></strong>
                                    <?php if ($cat['descripcion']): ?>
                                    <br>
                                    <small class="text-muted"><?php echo e($cat['descripcion']); ?></small>
                                    <?php endif; ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Botones de acción inferiores -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <a href="<?php echo e(BASE_URL); ?>/views/super_admin/tiendas.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Tiendas
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Guardar Categorías Asignadas
                </button>
            </div>
        </div>
    </div>
</form>

<script>
function seleccionarTodas() {
    document.querySelectorAll('.categoria-check').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deseleccionarTodas() {
    document.querySelectorAll('.categoria-check').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function seleccionarTipo(tipo) {
    document.querySelectorAll('.categoria-tipo-' + tipo).forEach(checkbox => {
        checkbox.checked = true;
    });
}

// Contador de seleccionadas
document.querySelectorAll('.categoria-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const totalSeleccionadas = document.querySelectorAll('.categoria-check:checked').length;
        console.log('Categorías seleccionadas: ' + totalSeleccionadas);
    });
});
</script>

<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-info text-center">
            <i class="bi bi-arrow-up"></i>
            <h5>Selecciona una tienda para asignar categorías</h5>
            <p class="mb-0">Usa el selector de arriba para elegir la tienda</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>
