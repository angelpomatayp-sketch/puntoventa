<?php
require_once '../config/config.php';

class AuthController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $usuario = sanitizeInput($_POST['usuario']);
            $password = $_POST['password'];

            try {
                // Query modificado para incluir datos de tienda
                $stmt = $this->db->prepare("
                    SELECT u.*, t.nombre_negocio, t.estado as tienda_estado, t.slug
                    FROM usuarios u
                    LEFT JOIN tiendas t ON u.tienda_id = t.id
                    WHERE u.usuario = :usuario AND u.activo = TRUE
                ");
                $stmt->execute(['usuario' => $usuario]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);

                    // Validar estado de tienda (excepto SUPER_ADMIN)
                    if ($user['rol'] !== 'SUPER_ADMINISTRADOR') {
                        if (!$user['tienda_id']) {
                            setFlashMessage('Usuario sin tienda asignada', 'danger');
                            redirect('/views/auth/login.php');
                            return;
                        }

                        if ($user['tienda_estado'] !== 'ACTIVA') {
                            setFlashMessage('Tienda suspendida o inactiva. Contacte al administrador.', 'danger');
                            redirect('/views/auth/login.php');
                            return;
                        }
                    }

                    // Almacenar en sesión (INCLUIR DATOS DE TIENDA)
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['usuario'] = $user['usuario'];
                    $_SESSION['rol'] = $user['rol'];
                    $_SESSION['tienda_id'] = $user['tienda_id']; // NUEVO
                    $_SESSION['tienda_nombre'] = $user['nombre_negocio']; // NUEVO
                    $_SESSION['tienda_slug'] = $user['slug']; // NUEVO

                    setFlashMessage('Bienvenido/a ' . $user['nombre'], 'success');

                    // Redirigir según rol
                    if ($user['rol'] === 'SUPER_ADMINISTRADOR') {
                        redirect('/views/super_admin/dashboard.php');
                    } else if ($user['rol'] === 'ADMINISTRADOR') {
                        redirect('/index.php');
                    } else {
                        redirect('/views/pos/venta.php');
                    }
                } else {
                    setFlashMessage('Usuario o contraseña incorrectos', 'danger');
                    redirect('/views/auth/login.php');
                }
            } catch (PDOException $e) {
                setFlashMessage('Error en el sistema', 'danger');
                redirect('/views/auth/login.php');
            }
        }
    }

    public function logout() {
        requireCsrfToken();
        session_destroy();
        redirect('/views/auth/login.php');
    }
}

// Manejar acciones
if (isset($_GET['action'])) {
    $controller = new AuthController();
    $action = $_GET['action'];

    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        redirect('/views/auth/login.php');
    }
}
