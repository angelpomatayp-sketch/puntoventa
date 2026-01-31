<?php
require_once '../../config/config.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirect('/index.php');
}

$page_title = 'Iniciar Sesión';
include '../layouts/header.php';
?>

<style>
    /* Login Page Styles */
    .login-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
    }

    .login-wrapper::before {
        content: '';
        position: absolute;
        width: 300%;
        height: 300%;
        background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.05) 50%, transparent 70%);
        animation: shimmer 15s infinite;
        transform: rotate(45deg);
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }

    .login-container {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 480px;
        padding: 2rem;
    }

    .login-card {
        background: white;
        border-radius: 24px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-header {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        padding: 3rem 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .login-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: pulse 8s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }

    .login-logo {
        width: 110px;
        height: 110px;
        margin: 0 auto 1.5rem;
        position: relative;
        z-index: 1;
        overflow: hidden;
        /* Fondo blanco circular para resaltar */
        background: white;
        border-radius: 50%;
        padding: 15px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2),
                    0 0 0 4px rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-logo img {
        width: 100%;
        height: auto;
        display: block;
        /* Mostrar solo el ícono, escalado para llenar el círculo */
        clip-path: inset(0 0 38% 0);
        transform: scale(2.2);
        transform-origin: center 35%;
    }

    .login-title {
        color: white;
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }

    .login-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1rem;
        font-weight: 500;
        position: relative;
        z-index: 1;
    }

    .login-body {
        padding: 2.5rem 2rem;
    }

    .form-floating-modern {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .form-floating-modern input {
        width: 100%;
        padding: 1rem 1rem 1rem 3.5rem;
        border: 2px solid #E5E7EB;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #F9FAFB;
    }

    .form-floating-modern input:focus {
        outline: none;
        border-color: #4F46E5;
        background: white;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    .form-floating-modern label {
        position: absolute;
        left: 3.5rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7280;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .form-floating-modern input:focus + label,
    .form-floating-modern input:not(:placeholder-shown) + label {
        top: -0.5rem;
        left: 2.75rem;
        font-size: 0.75rem;
        color: #4F46E5;
        background: white;
        padding: 0 0.5rem;
    }

    .input-icon {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9CA3AF;
        font-size: 1.25rem;
        transition: all 0.3s ease;
    }

    .form-floating-modern input:focus ~ .input-icon {
        color: #4F46E5;
    }

    .btn-login {
        width: 100%;
        padding: 1rem;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        color: white;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        margin-top: 1rem;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(79, 70, 229, 0.5);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    /* Responsive */
    @media (max-width: 576px) {
        .login-container {
            padding: 1rem;
        }

        .login-header {
            padding: 2rem 1.5rem;
        }

        .login-body {
            padding: 2rem 1.5rem;
        }

        .login-title {
            font-size: 1.5rem;
        }

        .login-subtitle {
            font-size: 0.9rem;
        }
    }
</style>

<div class="login-wrapper">
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="login-logo">
                    <img src="<?php echo BASE_URL; ?>/assets/images/logotipo.png" alt="DYM SAC">
                </div>
                <h1 class="login-title">DYM SAC</h1>
                <p class="login-subtitle">Servicios que construyen confianza</p>
            </div>

            <!-- Body -->
            <div class="login-body">
                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                <div class="alert alert-<?php echo e($flash['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo e($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <form id="loginForm" method="POST" action="<?php echo BASE_URL; ?>/controllers/AuthController.php?action=login">
                    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                    <div class="form-floating-modern">
                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            placeholder=" "
                            required
                            autofocus
                        >
                        <label for="usuario">Usuario</label>
                        <i class="bi bi-person-fill input-icon"></i>
                    </div>

                    <div class="form-floating-modern">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder=" "
                            required
                        >
                        <label for="password">Contraseña</label>
                        <i class="bi bi-lock-fill input-icon"></i>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer Copyright -->
        <div class="text-center mt-4">
            <small style="color: rgba(255, 255, 255, 0.8); font-weight: 500;">
                &copy; <?php echo date('Y'); ?> DYM SAC. Todos los derechos reservados.
            </small>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
