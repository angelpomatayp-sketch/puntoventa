<?php
require_once '../config/config.php';

class UnidadMedidaController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crear nueva unidad de medida (solo SUPER_ADMIN)
     */
    public function crear() {
        requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $nombre = sanitizeInput($_POST['nombre']);
            $abreviatura = strtolower(sanitizeInput($_POST['abreviatura']));
            $descripcion = sanitizeInput($_POST['descripcion'] ?? '');

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO unidades_medida (nombre, abreviatura, descripcion, activo)
                    VALUES (:nombre, :abreviatura, :descripcion, 1)
                ");

                $stmt->execute([
                    'nombre' => $nombre,
                    'abreviatura' => $abreviatura,
                    'descripcion' => $descripcion
                ]);

                setFlashMessage("Unidad de medida '{$nombre}' creada exitosamente", 'success');
                redirect('/views/super_admin/unidades_medida.php');

            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    if (strpos($e->getMessage(), 'unique_nombre') !== false) {
                        setFlashMessage('Ya existe una unidad de medida con ese nombre', 'danger');
                    } else {
                        setFlashMessage('Ya existe una unidad de medida con esa abreviatura', 'danger');
                    }
                } else {
                    setFlashMessage('Error al crear unidad de medida: ' . $e->getMessage(), 'danger');
                }
                redirect('/views/super_admin/crear_unidad_medida.php');
            }
        }
    }

    /**
     * Actualizar unidad de medida
     */
    public function actualizar() {
        requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $id = intval($_POST['id']);
            $nombre = sanitizeInput($_POST['nombre']);
            $abreviatura = strtolower(sanitizeInput($_POST['abreviatura']));
            $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;

            try {
                $stmt = $this->db->prepare("
                    UPDATE unidades_medida
                    SET nombre = :nombre,
                        abreviatura = :abreviatura,
                        descripcion = :descripcion,
                        activo = :activo
                    WHERE id = :id
                ");

                $stmt->execute([
                    'nombre' => $nombre,
                    'abreviatura' => $abreviatura,
                    'descripcion' => $descripcion,
                    'activo' => $activo,
                    'id' => $id
                ]);

                setFlashMessage('Unidad de medida actualizada exitosamente', 'success');
                redirect('/views/super_admin/unidades_medida.php');

            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    setFlashMessage('Ya existe una unidad con ese nombre o abreviatura', 'danger');
                } else {
                    setFlashMessage('Error al actualizar: ' . $e->getMessage(), 'danger');
                }
                redirect('/views/super_admin/editar_unidad_medida.php?id=' . $id);
            }
        }
    }

    /**
     * Eliminar unidad de medida (solo si no está en uso)
     */
    public function eliminar() {
        requireSuperAdmin();

        if (isset($_GET['id'])) {
            requireCsrfToken();
            $id = intval($_GET['id']);

            // Verificar si está en uso por productos
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM productos WHERE unidad_medida_id = :id
            ");
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                setFlashMessage('No se puede eliminar: la unidad está siendo utilizada por ' . $result['count'] . ' productos', 'danger');
                redirect('/views/super_admin/unidades_medida.php');
                return;
            }

            // Eliminar unidad
            $stmt = $this->db->prepare("DELETE FROM unidades_medida WHERE id = :id");
            $stmt->execute(['id' => $id]);

            setFlashMessage('Unidad de medida eliminada exitosamente', 'success');
            redirect('/views/super_admin/unidades_medida.php');
        }
    }

    /**
     * Cambiar estado de unidad de medida
     */
    public function cambiar_estado() {
        requireSuperAdmin();

        if (isset($_GET['id']) && isset($_GET['activo'])) {
            requireCsrfToken();
            $id = intval($_GET['id']);
            $activo = intval($_GET['activo']);

            $stmt = $this->db->prepare("
                UPDATE unidades_medida SET activo = :activo WHERE id = :id
            ");
            $stmt->execute(['activo' => $activo, 'id' => $id]);

            $mensaje = $activo ? 'Unidad activada' : 'Unidad desactivada';
            setFlashMessage($mensaje, 'success');
            redirect('/views/super_admin/unidades_medida.php');
        }
    }

    /**
     * Obtener todas las unidades de medida (AJAX)
     */
    public function listar_ajax() {
        requireLogin();

        $stmt = $this->db->query("
            SELECT id, nombre, abreviatura, descripcion
            FROM unidades_medida
            WHERE activo = 1
            ORDER BY nombre ASC
        ");
        $unidades = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'unidades' => $unidades]);
        exit;
    }

    /**
     * Obtener una unidad específica (AJAX)
     */
    public function obtener() {
        requireSuperAdmin();

        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);

            $stmt = $this->db->prepare("SELECT * FROM unidades_medida WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $unidad = $stmt->fetch();

            header('Content-Type: application/json');
            if ($unidad) {
                echo json_encode(['success' => true, 'unidad' => $unidad]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unidad no encontrada']);
            }
            exit;
        }
    }
}

// Manejar acciones
if (isset($_GET['action'])) {
    $controller = new UnidadMedidaController();
    $action = $_GET['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
