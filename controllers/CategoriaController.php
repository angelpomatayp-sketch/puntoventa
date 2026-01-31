<?php
require_once '../config/config.php';
requireAdmin();

class CategoriaController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $data = [
                'nombre' => sanitizeInput($_POST['nombre']),
                'descripcion' => sanitizeInput($_POST['descripcion']),
                'activo' => $_POST['activo']
            ];

            // MULTI-TENANT: Agregar tienda_id automáticamente
            TenantHelper::addTenantId($data);

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO categorias (tienda_id, nombre, descripcion, activo)
                    VALUES (:tienda_id, :nombre, :descripcion, :activo)
                ");

                $stmt->execute($data);

                setFlashMessage('Categoría creada exitosamente', 'success');
                redirect('/views/admin/categorias.php');

            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setFlashMessage('La categoría ya existe', 'danger');
                } else {
                    setFlashMessage('Error al crear categoría: ' . $e->getMessage(), 'danger');
                }
                redirect('/views/admin/categorias.php');
            }
        }
    }

    public function editar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $id = $_POST['id'];

            // MULTI-TENANT: Validar que la categoría pertenece a la tienda
            if (!TenantHelper::validateAccess('categorias', $id)) {
                setFlashMessage('Acceso denegado', 'danger');
                redirect('/views/admin/categorias.php');
                return;
            }

            try {
                $stmt = $this->db->prepare("
                    UPDATE categorias
                    SET nombre = :nombre, descripcion = :descripcion, activo = :activo
                    WHERE id = :id
                ");

                $stmt->execute([
                    'nombre' => sanitizeInput($_POST['nombre']),
                    'descripcion' => sanitizeInput($_POST['descripcion']),
                    'activo' => $_POST['activo'],
                    'id' => $id
                ]);

                setFlashMessage('Categoría actualizada exitosamente', 'success');
                redirect('/views/admin/categorias.php');

            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setFlashMessage('La categoría ya existe', 'danger');
                } else {
                    setFlashMessage('Error al actualizar categoría: ' . $e->getMessage(), 'danger');
                }
                redirect('/views/admin/categorias.php');
            }
        }
    }

    public function eliminar() {
        if (isset($_GET['id'])) {
            requireCsrfToken();
            $id = $_GET['id'];

            // MULTI-TENANT: Validar que la categoría pertenece a la tienda
            if (!TenantHelper::validateAccess('categorias', $id)) {
                setFlashMessage('Acceso denegado', 'danger');
                redirect('/views/admin/categorias.php');
                return;
            }

            try {
                $stmt = $this->db->prepare("DELETE FROM categorias WHERE id = :id");
                $stmt->execute(['id' => $id]);

                setFlashMessage('Categoría eliminada exitosamente', 'success');
                redirect('/views/admin/categorias.php');

            } catch (PDOException $e) {
                setFlashMessage('No se puede eliminar la categoría. Tiene productos asociados.', 'danger');
                redirect('/views/admin/categorias.php');
            }
        }
    }
}

// Manejar acciones
if (isset($_POST['action'])) {
    $controller = new CategoriaController();
    $action = $_POST['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
} elseif (isset($_GET['action'])) {
    $controller = new CategoriaController();
    $action = $_GET['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
