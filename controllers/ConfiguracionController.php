<?php
require_once '../config/config.php';
requireAdmin();

class ConfiguracionController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            // MULTI-TENANT: Obtener tienda_id del usuario actual
            $tienda_id = getTiendaId();

            if (!$tienda_id && !isSuperAdmin()) {
                setFlashMessage('Error: Usuario sin tienda asignada', 'danger');
                redirect('/views/admin/configuracion.php');
                return;
            }

            try {
                // MULTI-TENANT: Actualizar tabla tiendas en lugar de configuracion
                $stmt = $this->db->prepare("
                    UPDATE tiendas
                    SET nombre_negocio = :nombre_negocio,
                        ruc = :ruc,
                        direccion = :direccion,
                        telefono = :telefono,
                        igv = :igv
                    WHERE id = :tienda_id
                ");

                $stmt->execute([
                    'nombre_negocio' => sanitizeInput($_POST['nombre_negocio']),
                    'ruc' => sanitizeInput($_POST['ruc']),
                    'direccion' => sanitizeInput($_POST['direccion']),
                    'telefono' => sanitizeInput($_POST['telefono']),
                    'igv' => floatval($_POST['igv']),
                    'tienda_id' => $tienda_id
                ]);

                // Actualizar sesión con el nuevo nombre
                $_SESSION['tienda_nombre'] = $_POST['nombre_negocio'];

                setFlashMessage('Configuración actualizada exitosamente', 'success');
                redirect('/views/admin/configuracion.php');

            } catch (PDOException $e) {
                setFlashMessage('Error al actualizar configuración: ' . $e->getMessage(), 'danger');
                redirect('/views/admin/configuracion.php');
            }
        }
    }
}

// Manejar acciones
if (isset($_GET['action'])) {
    $controller = new ConfiguracionController();
    $action = $_GET['action'];

    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
