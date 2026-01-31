<?php
require_once '../config/config.php';

class CategoriaGlobalController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crear nueva categoría global (solo SUPER_ADMIN)
     */
    public function crear() {
        requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $nombre = sanitizeInput($_POST['nombre']);
            $descripcion = sanitizeInput($_POST['descripcion']);
            $tipo_negocio = $_POST['tipo_negocio'];

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO categorias (tienda_id, nombre, descripcion, tipo_negocio, es_global, activo)
                    VALUES (NULL, :nombre, :descripcion, :tipo_negocio, 1, TRUE)
                ");

                $stmt->execute([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'tipo_negocio' => $tipo_negocio
                ]);

                setFlashMessage("Categoría global '{$nombre}' creada exitosamente", 'success');
                redirect('/views/super_admin/categorias_globales.php');

            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    setFlashMessage('Ya existe una categoría global con ese nombre', 'danger');
                } else {
                    setFlashMessage('Error al crear categoría: ' . $e->getMessage(), 'danger');
                }
                redirect('/views/super_admin/crear_categoria.php');
            }
        }
    }

    /**
     * Actualizar categoría global
     */
    public function actualizar() {
        requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $id = intval($_POST['id']);
            $nombre = sanitizeInput($_POST['nombre']);
            $descripcion = sanitizeInput($_POST['descripcion']);
            $tipo_negocio = $_POST['tipo_negocio'];
            $activo = isset($_POST['activo']) ? 1 : 0;

            try {
                $stmt = $this->db->prepare("
                    UPDATE categorias
                    SET nombre = :nombre,
                        descripcion = :descripcion,
                        tipo_negocio = :tipo_negocio,
                        activo = :activo
                    WHERE id = :id AND es_global = 1
                ");

                $stmt->execute([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'tipo_negocio' => $tipo_negocio,
                    'activo' => $activo,
                    'id' => $id
                ]);

                setFlashMessage('Categoría actualizada exitosamente', 'success');
                redirect('/views/super_admin/categorias_globales.php');

            } catch (PDOException $e) {
                setFlashMessage('Error al actualizar categoría: ' . $e->getMessage(), 'danger');
                redirect('/views/super_admin/editar_categoria.php?id=' . $id);
            }
        }
    }

    /**
     * Eliminar categoría global (solo si no está en uso)
     */
    public function eliminar() {
        requireSuperAdmin();

        if (isset($_GET['id'])) {
            requireCsrfToken();
            $id = intval($_GET['id']);

            // Verificar si está en uso
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM productos WHERE categoria_id = :id
            ");
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                setFlashMessage('No se puede eliminar: la categoría está siendo utilizada por ' . $result['count'] . ' productos', 'danger');
                redirect('/views/super_admin/categorias_globales.php');
                return;
            }

            // Eliminar asignaciones a tiendas
            $stmt = $this->db->prepare("DELETE FROM tienda_categorias WHERE categoria_id = :id");
            $stmt->execute(['id' => $id]);

            // Eliminar categoría
            $stmt = $this->db->prepare("DELETE FROM categorias WHERE id = :id AND es_global = 1");
            $stmt->execute(['id' => $id]);

            setFlashMessage('Categoría eliminada exitosamente', 'success');
            redirect('/views/super_admin/categorias_globales.php');
        }
    }

    /**
     * Asignar categorías a una tienda
     */
    public function asignar_a_tienda() {
        requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $tienda_id = intval($_POST['tienda_id']);
            $categorias = isset($_POST['categorias']) ? $_POST['categorias'] : [];

            try {
                $this->db->beginTransaction();

                // Eliminar asignaciones anteriores
                $stmt = $this->db->prepare("DELETE FROM tienda_categorias WHERE tienda_id = :tienda_id");
                $stmt->execute(['tienda_id' => $tienda_id]);

                // Insertar nuevas asignaciones
                if (!empty($categorias)) {
                    $stmt = $this->db->prepare("
                        INSERT INTO tienda_categorias (tienda_id, categoria_id, activo, asignado_por)
                        VALUES (:tienda_id, :categoria_id, 1, :usuario_id)
                    ");

                    foreach ($categorias as $categoria_id) {
                        $stmt->execute([
                            'tienda_id' => $tienda_id,
                            'categoria_id' => intval($categoria_id),
                            'usuario_id' => $_SESSION['user_id']
                        ]);
                    }
                }

                $this->db->commit();

                setFlashMessage('Categorías asignadas exitosamente', 'success');
                redirect('/views/super_admin/asignar_categorias.php?tienda_id=' . $tienda_id);

            } catch (PDOException $e) {
                $this->db->rollBack();
                setFlashMessage('Error al asignar categorías: ' . $e->getMessage(), 'danger');
                redirect('/views/super_admin/asignar_categorias.php?tienda_id=' . $tienda_id);
            }
        }
    }

    /**
     * Obtener categorías asignadas a una tienda (AJAX)
     */
    public function obtener_categorias_tienda() {
        requireSuperAdmin();

        if (isset($_GET['tienda_id'])) {
            $tienda_id = intval($_GET['tienda_id']);

            $stmt = $this->db->prepare("
                SELECT categoria_id FROM tienda_categorias
                WHERE tienda_id = :tienda_id AND activo = 1
            ");
            $stmt->execute(['tienda_id' => $tienda_id]);
            $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'categorias' => $categorias]);
            exit;
        }
    }

    /**
     * Cambiar estado de categoría global
     */
    public function cambiar_estado() {
        requireSuperAdmin();

        if (isset($_GET['id']) && isset($_GET['activo'])) {
            requireCsrfToken();
            $id = intval($_GET['id']);
            $activo = intval($_GET['activo']);

            $stmt = $this->db->prepare("
                UPDATE categorias SET activo = :activo
                WHERE id = :id AND es_global = 1
            ");
            $stmt->execute(['activo' => $activo, 'id' => $id]);

            $mensaje = $activo ? 'Categoría activada' : 'Categoría desactivada';
            setFlashMessage($mensaje, 'success');
            redirect('/views/super_admin/categorias_globales.php');
        }
    }
}

// Manejar acciones
if (isset($_GET['action'])) {
    $controller = new CategoriaGlobalController();
    $action = $_GET['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
