<?php
require_once '../config/config.php';

// Permitir búsqueda sin requireAdmin para AJAX desde POS
if (!isset($_GET['action']) || $_GET['action'] !== 'buscar') {
    requireLogin();
}

class ClienteController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $data = [
                'nombre' => sanitizeInput($_POST['nombre']),
                'dni_ruc' => sanitizeInput($_POST['dni_ruc']),
                'telefono' => sanitizeInput($_POST['telefono'])
            ];

            // MULTI-TENANT: Agregar tienda_id automáticamente
            TenantHelper::addTenantId($data);

            try {
                // MULTI-TENANT: Validar DNI/RUC único dentro de la tienda
                $sql = "SELECT id FROM clientes WHERE dni_ruc = :dni_ruc";
                TenantHelper::addTenantScope($sql);
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['dni_ruc' => $data['dni_ruc']]);

                if ($stmt->fetch()) {
                    setFlashMessage('Ya existe un cliente con el DNI/RUC: ' . $data['dni_ruc'], 'danger');
                    if (isAdmin()) {
                        redirect('/views/admin/clientes.php');
                    } else {
                        redirect('/views/cajero/clientes.php');
                    }
                    return;
                }

                // Insertar cliente
                $stmt = $this->db->prepare("
                    INSERT INTO clientes (tienda_id, nombre, dni_ruc, telefono, activo)
                    VALUES (:tienda_id, :nombre, :dni_ruc, :telefono, TRUE)
                ");

                $stmt->execute($data);

                setFlashMessage('Cliente registrado exitosamente', 'success');

                if (isAdmin()) {
                    redirect('/views/admin/clientes.php');
                } else {
                    redirect('/views/cajero/clientes.php');
                }

            } catch (PDOException $e) {
                setFlashMessage('Error al registrar cliente: ' . $e->getMessage(), 'danger');
                if (isAdmin()) {
                    redirect('/views/admin/clientes.php');
                } else {
                    redirect('/views/cajero/clientes.php');
                }
            }
        }
    }

    public function editar() {
        requireAdmin(); // Solo admin puede editar

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $id = $_POST['id'];
            $dni_ruc = sanitizeInput($_POST['dni_ruc']);

            // MULTI-TENANT: Validar que el cliente pertenece a la tienda
            if (!TenantHelper::validateAccess('clientes', $id)) {
                setFlashMessage('Acceso denegado', 'danger');
                redirect('/views/admin/clientes.php');
                return;
            }

            try {
                // No permitir editar cliente ANÓNIMO
                $sql = "SELECT nombre FROM clientes WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['id' => $id]);
                $cliente = $stmt->fetch();

                if ($cliente && $cliente['nombre'] === 'ANÓNIMO') {
                    setFlashMessage('No se puede editar el cliente ANÓNIMO', 'danger');
                    redirect('/views/admin/clientes.php');
                    return;
                }

                // MULTI-TENANT: Validar DNI único dentro de la tienda (excepto el actual)
                $sql = "SELECT id FROM clientes WHERE dni_ruc = :dni_ruc AND id != :id";
                TenantHelper::addTenantScope($sql);
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['dni_ruc' => $dni_ruc, 'id' => $id]);

                if ($stmt->fetch()) {
                    setFlashMessage('Ya existe otro cliente con el DNI/RUC: ' . $dni_ruc, 'danger');
                    redirect('/views/admin/clientes.php');
                    return;
                }

                // Actualizar cliente
                $stmt = $this->db->prepare("
                    UPDATE clientes
                    SET nombre = :nombre, dni_ruc = :dni_ruc, telefono = :telefono, activo = :activo
                    WHERE id = :id
                ");

                $stmt->execute([
                    'nombre' => sanitizeInput($_POST['nombre']),
                    'dni_ruc' => $dni_ruc,
                    'telefono' => sanitizeInput($_POST['telefono']),
                    'activo' => $_POST['activo'],
                    'id' => $id
                ]);

                setFlashMessage('Cliente actualizado exitosamente', 'success');
                redirect('/views/admin/clientes.php');

            } catch (PDOException $e) {
                setFlashMessage('Error al actualizar cliente: ' . $e->getMessage(), 'danger');
                redirect('/views/admin/clientes.php');
            }
        }
    }

    public function eliminar() {
        requireAdmin(); // Solo admin puede eliminar

        if (isset($_GET['id'])) {
            requireCsrfToken();
            $id = $_GET['id'];

            // MULTI-TENANT: Validar que el cliente pertenece a la tienda
            if (!TenantHelper::validateAccess('clientes', $id)) {
                setFlashMessage('Acceso denegado', 'danger');
                redirect('/views/admin/clientes.php');
                return;
            }

            try {
                // No permitir eliminar cliente ANÓNIMO
                $stmt = $this->db->prepare("SELECT nombre FROM clientes WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $cliente = $stmt->fetch();

                if ($cliente && $cliente['nombre'] === 'ANÓNIMO') {
                    setFlashMessage('No se puede eliminar el cliente ANÓNIMO', 'danger');
                    redirect('/views/admin/clientes.php');
                    return;
                }

                // MULTI-TENANT: Obtener el cliente anónimo de esta tienda
                $sql = "SELECT id FROM clientes WHERE nombre = 'ANÓNIMO'";
                TenantHelper::addTenantScope($sql);
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $anonimo = $stmt->fetch();

                if (!$anonimo) {
                    setFlashMessage('Error: No se encontró cliente ANÓNIMO de la tienda', 'danger');
                    redirect('/views/admin/clientes.php');
                    return;
                }

                // Actualizar ventas del cliente a ANÓNIMO antes de eliminar
                $stmt = $this->db->prepare("UPDATE ventas SET cliente_id = :anonimo_id WHERE cliente_id = :id");
                $stmt->execute(['anonimo_id' => $anonimo['id'], 'id' => $id]);

                // Eliminar cliente
                $stmt = $this->db->prepare("DELETE FROM clientes WHERE id = :id");
                $stmt->execute(['id' => $id]);

                setFlashMessage('Cliente eliminado exitosamente. Sus ventas fueron asignadas a ANÓNIMO.', 'success');
                redirect('/views/admin/clientes.php');

            } catch (PDOException $e) {
                setFlashMessage('Error al eliminar cliente: ' . $e->getMessage(), 'danger');
                redirect('/views/admin/clientes.php');
            }
        }
    }

    public function buscar() {
        requireLogin();

        if (isset($_GET['q'])) {
            $query = sanitizeInput($_GET['q']);

            try {
                $searchPattern = '%' . $query . '%';

                // MULTI-TENANT: Filtrar por tienda
                $sql = "
                    SELECT id, nombre, dni_ruc, telefono
                    FROM clientes
                    WHERE activo = TRUE
                    AND (nombre LIKE :query1 OR dni_ruc LIKE :query2)";
                TenantHelper::addTenantScope($sql);
                $sql .= " ORDER BY
                        CASE WHEN nombre = 'ANÓNIMO' THEN 0 ELSE 1 END,
                        nombre ASC
                    LIMIT 10";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'query1' => $searchPattern,
                    'query2' => $searchPattern
                ]);
                $clientes = $stmt->fetchAll();

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'clientes' => $clientes]);
                exit;

            } catch (PDOException $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        } else {
            // Devolver todos los clientes activos (primero ANÓNIMO)
            try {
                // MULTI-TENANT: Filtrar por tienda
                $sql = "
                    SELECT id, nombre, dni_ruc, telefono
                    FROM clientes
                    WHERE activo = TRUE";
                TenantHelper::addTenantScope($sql);
                $sql .= " ORDER BY
                        CASE WHEN nombre = 'ANÓNIMO' THEN 0 ELSE 1 END,
                        nombre ASC
                    LIMIT 10";

                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $clientes = $stmt->fetchAll();

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'clientes' => $clientes]);
                exit;

            } catch (PDOException $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        }
    }

    public function crear_ajax() {
        requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $data = [
                'nombre' => sanitizeInput($_POST['nombre']),
                'dni_ruc' => sanitizeInput($_POST['dni_ruc']),
                'telefono' => sanitizeInput($_POST['telefono'])
            ];

            // MULTI-TENANT: Agregar tienda_id automáticamente
            TenantHelper::addTenantId($data);

            try {
                // MULTI-TENANT: Validar DNI/RUC único dentro de la tienda
                $sql = "SELECT id FROM clientes WHERE dni_ruc = :dni_ruc";
                TenantHelper::addTenantScope($sql);
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['dni_ruc' => $data['dni_ruc']]);

                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Ya existe un cliente con ese DNI/RUC']);
                    exit;
                }

                // Insertar cliente
                $stmt = $this->db->prepare("
                    INSERT INTO clientes (tienda_id, nombre, dni_ruc, telefono, activo)
                    VALUES (:tienda_id, :nombre, :dni_ruc, :telefono, TRUE)
                ");

                $stmt->execute($data);
                $cliente_id = $this->db->lastInsertId();

                echo json_encode([
                    'success' => true,
                    'cliente' => [
                        'id' => $cliente_id,
                        'nombre' => $data['nombre'],
                        'dni_ruc' => $data['dni_ruc'],
                        'telefono' => $data['telefono']
                    ]
                ]);
                exit;

            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit;
            }
        }
    }
}

// Manejar acciones
if (isset($_POST['action'])) {
    $controller = new ClienteController();
    $action = $_POST['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
} elseif (isset($_GET['action'])) {
    $controller = new ClienteController();
    $action = $_GET['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
