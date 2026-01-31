<?php
require_once '../config/config.php';
requireAdmin();

class UsuarioController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $rol = $_POST['rol'];

            // MULTI-TENANT: Validar que no intente crear SUPER_ADMINISTRADOR
            if ($rol === 'SUPER_ADMINISTRADOR' && !isSuperAdmin()) {
                setFlashMessage('No tienes permisos para crear super administradores', 'danger');
                redirect('/views/admin/usuarios.php');
                return;
            }

            $data = [
                'nombre' => sanitizeInput($_POST['nombre']),
                'usuario' => sanitizeInput($_POST['usuario']),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'rol' => $rol,
                'activo' => $_POST['activo']
            ];

            // MULTI-TENANT: Agregar tienda_id (solo si no es SUPER_ADMIN)
            if ($rol !== 'SUPER_ADMINISTRADOR') {
                TenantHelper::addTenantId($data);
            } else {
                $data['tienda_id'] = null;
            }

            try {
                // Validar que el usuario no exista
                $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
                $stmt->execute(['usuario' => $data['usuario']]);

                if ($stmt->fetch()) {
                    setFlashMessage('El nombre de usuario ya existe', 'danger');
                    redirect('/views/admin/usuarios.php');
                    return;
                }

                // Insertar usuario
                $stmt = $this->db->prepare("
                    INSERT INTO usuarios (tienda_id, nombre, usuario, password, rol, activo)
                    VALUES (:tienda_id, :nombre, :usuario, :password, :rol, :activo)
                ");

                $stmt->execute($data);

                setFlashMessage('Usuario creado exitosamente', 'success');
                redirect('/views/admin/usuarios.php');

            } catch (PDOException $e) {
                setFlashMessage('Error al crear usuario: ' . $e->getMessage(), 'danger');
                redirect('/views/admin/usuarios.php');
            }
        }
    }

    public function editar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $id = $_POST['id'];

            // MULTI-TENANT: Validar acceso al usuario (excepto SUPER_ADMIN puede editar todos)
            if (!isSuperAdmin()) {
                if (!TenantHelper::validateAccess('usuarios', $id)) {
                    setFlashMessage('Acceso denegado', 'danger');
                    redirect('/views/admin/usuarios.php');
                    return;
                }
            }

            $nombre = sanitizeInput($_POST['nombre']);
            $usuario = sanitizeInput($_POST['usuario']);
            $password = $_POST['password'];
            $rol = $_POST['rol'];
            $activo = $_POST['activo'];

            // MULTI-TENANT: No permitir cambiar rol a SUPER_ADMINISTRADOR
            if ($rol === 'SUPER_ADMINISTRADOR' && !isSuperAdmin()) {
                setFlashMessage('No tienes permisos para asignar rol de super administrador', 'danger');
                redirect('/views/admin/usuarios.php');
                return;
            }

            try {
                // Validar que el usuario no exista en otro registro
                $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE usuario = :usuario AND id != :id");
                $stmt->execute(['usuario' => $usuario, 'id' => $id]);

                if ($stmt->fetch()) {
                    setFlashMessage('El nombre de usuario ya existe', 'danger');
                    redirect('/views/admin/usuarios.php');
                    return;
                }

                // Actualizar usuario
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $this->db->prepare("
                        UPDATE usuarios
                        SET nombre = :nombre, usuario = :usuario, password = :password,
                            rol = :rol, activo = :activo
                        WHERE id = :id
                    ");
                    $params = [
                        'nombre' => $nombre,
                        'usuario' => $usuario,
                        'password' => $password_hash,
                        'rol' => $rol,
                        'activo' => $activo,
                        'id' => $id
                    ];
                } else {
                    $stmt = $this->db->prepare("
                        UPDATE usuarios
                        SET nombre = :nombre, usuario = :usuario, rol = :rol, activo = :activo
                        WHERE id = :id
                    ");
                    $params = [
                        'nombre' => $nombre,
                        'usuario' => $usuario,
                        'rol' => $rol,
                        'activo' => $activo,
                        'id' => $id
                    ];
                }

                $stmt->execute($params);

                setFlashMessage('Usuario actualizado exitosamente', 'success');
                redirect('/views/admin/usuarios.php');

            } catch (PDOException $e) {
                setFlashMessage('Error al actualizar usuario: ' . $e->getMessage(), 'danger');
                redirect('/views/admin/usuarios.php');
            }
        }
    }

    public function eliminar() {
        if (isset($_GET['id'])) {
            requireCsrfToken();
            $id = $_GET['id'];

            // MULTI-TENANT: Validar acceso (excepto SUPER_ADMIN puede eliminar todos)
            if (!isSuperAdmin()) {
                if (!TenantHelper::validateAccess('usuarios', $id)) {
                    setFlashMessage('Acceso denegado', 'danger');
                    redirect('/views/admin/usuarios.php');
                    return;
                }
            }

            try {
                // No permitir eliminar el usuario actual
                if ($id == $_SESSION['user_id']) {
                    setFlashMessage('No puedes eliminar tu propio usuario', 'danger');
                    redirect('/views/admin/usuarios.php');
                    return;
                }

                $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute(['id' => $id]);

                setFlashMessage('Usuario eliminado exitosamente', 'success');
                redirect('/views/admin/usuarios.php');

            } catch (PDOException $e) {
                setFlashMessage('No se puede eliminar el usuario. Puede tener registros asociados.', 'danger');
                redirect('/views/admin/usuarios.php');
            }
        }
    }
}

// Manejar acciones
if (isset($_POST['action'])) {
    $controller = new UsuarioController();
    $action = $_POST['action'];

    if (method_exists($controller, $action)) {
        $controller->$action();
    }
} elseif (isset($_GET['action'])) {
    $controller = new UsuarioController();
    $action = $_GET['action'];

    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
