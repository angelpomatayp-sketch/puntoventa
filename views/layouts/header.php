<?php
if (!defined('BASE_PATH')) {
    require_once '../../config/config.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo getCsrfToken(); ?>">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Sistema POS Fast Food</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">

    <?php if (isset($custom_css)): ?>
        <?php echo $custom_css; ?>
    <?php endif; ?>

    <script>
        window.CSRF_TOKEN = "<?php echo getCsrfToken(); ?>";
    </script>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?php echo BASE_URL; ?>/index.php" class="sidebar-brand">
                <i class="bi bi-shop"></i>
                <span class="brand-text">Mi Negocio</span>
            </a>
        </div>

        <div class="sidebar-menu">
            <?php if (isSuperAdmin()): ?>
            <!-- MULTI-TENANT: Menú Super Administrador -->
            <div class="menu-section">
                <div class="menu-section-title" style="color: #ffc107;">SUPER ADMIN</div>
                <a href="<?php echo BASE_URL; ?>/views/super_admin/dashboard.php" class="sidebar-link">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard Global</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/super_admin/tiendas.php" class="sidebar-link">
                    <i class="bi bi-shop-window"></i>
                    <span>Gestión de Tiendas</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/super_admin/reportes.php" class="sidebar-link">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Reportes Globales</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title" style="color: #ffc107;">CATÁLOGOS</div>
                <a href="<?php echo BASE_URL; ?>/views/super_admin/categorias_globales.php" class="sidebar-link">
                    <i class="bi bi-tags"></i>
                    <span>Categorías Globales</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/super_admin/unidades_medida.php" class="sidebar-link">
                    <i class="bi bi-rulers"></i>
                    <span>Unidades de Medida</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/super_admin/asignar_categorias.php" class="sidebar-link">
                    <i class="bi bi-diagram-3"></i>
                    <span>Asignar Categorías</span>
                </a>
            </div>

            <?php elseif (isAdmin()): ?>
            <!-- Menú Administrador -->
            <div class="menu-section">
                <div class="menu-section-title">PRINCIPAL</div>
                <a href="<?php echo BASE_URL; ?>/index.php" class="sidebar-link">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/pos/venta.php" class="sidebar-link">
                    <i class="bi bi-cart3"></i>
                    <span>Punto de Venta</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">INVENTARIO</div>
                <a href="<?php echo BASE_URL; ?>/views/admin/productos.php" class="sidebar-link">
                    <i class="bi bi-box-seam"></i>
                    <span>Productos</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/admin/movimientos.php" class="sidebar-link">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Movimientos</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">OPERACIONES</div>
                <a href="<?php echo BASE_URL; ?>/views/admin/cajas.php" class="sidebar-link">
                    <i class="bi bi-cash-stack"></i>
                    <span>Cajas</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/admin/clientes.php" class="sidebar-link">
                    <i class="bi bi-people"></i>
                    <span>Clientes</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">REPORTES</div>
                <a href="<?php echo BASE_URL; ?>/views/reportes/ventas.php" class="sidebar-link">
                    <i class="bi bi-graph-up"></i>
                    <span>Ventas</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/reportes/inventario.php" class="sidebar-link">
                    <i class="bi bi-clipboard-data"></i>
                    <span>Inventario</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/reportes/pagos.php" class="sidebar-link">
                    <i class="bi bi-credit-card"></i>
                    <span>Medios de Pago</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/reportes/ganancias.php" class="sidebar-link">
                    <i class="bi bi-cash-coin"></i>
                    <span>Ganancias</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/admin/ventas_anuladas.php" class="sidebar-link">
                    <i class="bi bi-x-circle"></i>
                    <span>Ventas Anuladas</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">CONFIGURACIÓN</div>
                <a href="<?php echo BASE_URL; ?>/views/admin/usuarios.php" class="sidebar-link">
                    <i class="bi bi-person-gear"></i>
                    <span>Usuarios</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/admin/configuracion.php" class="sidebar-link">
                    <i class="bi bi-gear"></i>
                    <span>Datos del Negocio</span>
                </a>
            </div>

            <?php else: ?>
            <!-- Menú Cajero -->
            <div class="menu-section">
                <div class="menu-section-title">PRINCIPAL</div>
                <a href="<?php echo BASE_URL; ?>/index.php" class="sidebar-link">
                    <i class="bi bi-house"></i>
                    <span>Inicio</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/pos/venta.php" class="sidebar-link">
                    <i class="bi bi-cart3"></i>
                    <span>Punto de Venta</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/cajero/ventas.php" class="sidebar-link">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span>Mis Ventas</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">GESTIÓN</div>
                <a href="<?php echo BASE_URL; ?>/views/cajero/productos.php" class="sidebar-link">
                    <i class="bi bi-box-seam"></i>
                    <span>Productos</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/cajero/inventario.php" class="sidebar-link">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Inventario</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/views/cajero/clientes.php" class="sidebar-link">
                    <i class="bi bi-people"></i>
                    <span>Clientes</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">CAJA</div>
                <a href="<?php echo BASE_URL; ?>/views/cajero/caja.php" class="sidebar-link">
                    <i class="bi bi-cash-stack"></i>
                    <span>Mi Caja</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="navbar-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <div class="navbar-title">
                <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
            </div>
        </div>
        <div class="navbar-right">
            <!-- MULTI-TENANT: Badge de Tienda -->
            <?php if (hasTienda()): ?>
            <div class="tienda-badge" style="margin-right: 20px; padding: 8px 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                <i class="bi bi-shop" style="color: #0d6efd; margin-right: 5px;"></i>
                <span style="font-size: 13px; font-weight: 600; color: #495057;"><?php echo getTiendaNombre(); ?></span>
            </div>
            <?php endif; ?>

            <div class="user-menu">
                <div class="user-info-top">
                    <i class="bi bi-person-circle"></i>
                    <div class="user-details-top">
                        <span class="user-name-top"><?php echo $_SESSION['nombre']; ?></span>
                        <span class="user-role-top"><?php echo $_SESSION['rol']; ?></span>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/controllers/AuthController.php?action=logout&csrf_token=<?php echo getCsrfToken(); ?>" class="btn-logout-top" title="Cerrar Sesión">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-content" id="mainContent">
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php
    $current_page = basename($_SERVER['SCRIPT_NAME']);
    if ($current_page !== 'login.php') {
        $flash = getFlashMessage();
        if ($flash):
    ?>
    <div class="container-fluid mt-3">
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo e($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; } ?>

    <!-- Page Content -->
    <div class="<?php echo isLoggedIn() ? 'page-content' : ''; ?>">
