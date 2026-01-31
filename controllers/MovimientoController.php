<?php
require_once '../config/config.php';
requireLogin();

class MovimientoController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $producto_id = $_POST['producto_id'];
            $tipo_form = $_POST['tipo'];
            $cantidad = intval($_POST['cantidad']);
            $motivo = sanitizeInput($_POST['nota'] ?? $_POST['motivo'] ?? '') ?: null;

            try {
                $this->db->beginTransaction();

                // MULTI-TENANT: Validar que el producto pertenece a la tienda
                if (!TenantHelper::validateAccess('productos', $producto_id)) {
                    throw new Exception('Acceso denegado al producto');
                }

                // Obtener stock actual
                $stmt = $this->db->prepare("SELECT stock_actual, nombre FROM productos WHERE id = :id");
                $stmt->execute(['id' => $producto_id]);
                $producto = $stmt->fetch();

                if (!$producto) {
                    throw new Exception('Producto no encontrado');
                }

                $stock_anterior = $producto['stock_actual'];

                // Mapear tipo del formulario al tipo de la BD y calcular nuevo stock
                if ($tipo_form === 'AJUSTE_NEGATIVO' || $tipo_form === 'SALIDA') {
                    if ($cantidad > $stock_anterior) {
                        throw new Exception('La cantidad a descontar es mayor al stock actual');
                    }
                    $tipo = 'SALIDA';
                    $stock_nuevo = $stock_anterior - $cantidad;
                } else {
                    $tipo = 'ENTRADA';
                    $stock_nuevo = $stock_anterior + $cantidad;
                }

                // MULTI-TENANT: Preparar datos con tienda_id
                $data = [
                    'producto_id' => $producto_id,
                    'usuario_id' => $_SESSION['user_id'],
                    'tipo' => $tipo,
                    'cantidad' => $cantidad,
                    'stock_anterior' => $stock_anterior,
                    'stock_nuevo' => $stock_nuevo,
                    'motivo' => $motivo
                ];
                TenantHelper::addTenantId($data);

                // Registrar movimiento
                $stmt = $this->db->prepare("
                    INSERT INTO movimientos_inventario
                    (tienda_id, producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                    VALUES (:tienda_id, :producto_id, :usuario_id, :tipo, :cantidad, :stock_anterior, :stock_nuevo, :motivo)
                ");

                $stmt->execute($data);

                // Actualizar stock del producto
                $stmt = $this->db->prepare("
                    UPDATE productos
                    SET stock_actual = :stock_nuevo
                    WHERE id = :id
                ");

                $stmt->execute([
                    'stock_nuevo' => $stock_nuevo,
                    'id' => $producto_id
                ]);

                $this->db->commit();

                setFlashMessage('Movimiento registrado exitosamente', 'success');

                // Redirigir según rol
                if (isAdmin()) {
                    redirect('/views/admin/movimientos.php');
                } else {
                    redirect('/views/cajero/inventario.php');
                }

            } catch (Exception $e) {
                $this->db->rollBack();
                setFlashMessage('Error al registrar movimiento: ' . $e->getMessage(), 'danger');

                if (isAdmin()) {
                    redirect('/views/admin/movimientos.php');
                } else {
                    redirect('/views/cajero/inventario.php');
                }
            }
        }
    }
}

// Manejar acciones
if (isset($_GET['action'])) {
    $controller = new MovimientoController();
    $action = $_GET['action'];

    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
