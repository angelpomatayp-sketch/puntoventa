<?php
require_once '../config/config.php';

// Solo las funciones crear_cajero no requieren admin
if (isset($_POST['action']) && $_POST['action'] === 'crear_cajero') {
    requireLogin();
} else {
    requireAdmin();
}

class ProductoController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $unidad_medida_id = !empty($_POST['unidad_medida_id']) ? intval($_POST['unidad_medida_id']) : null;
            $data = [
                'categoria_id' => $_POST['categoria_id'],
                'unidad_medida_id' => $unidad_medida_id,
                'nombre' => sanitizeInput($_POST['nombre']),
                'precio_venta' => floatval($_POST['precio_venta']),
                'precio_compra' => floatval($_POST['precio_compra'] ?? 0),
                'stock_minimo' => intval($_POST['stock_minimo'] ?? 5),
                'activo' => $_POST['activo']
            ];

            // MULTI-TENANT: Agregar tienda_id automaticamente
            TenantHelper::addTenantId($data);

            // Validar categoria segun tienda
            if (!validarCategoriaTienda($data['categoria_id'])) {
                setFlashMessage('Categoria no valida para esta tienda', 'danger');
                redirect('/views/admin/productos.php');
                return;
            }

            if (!validarUnidadMedida($unidad_medida_id)) {
                setFlashMessage('Unidad de medida no valida', 'danger');
                redirect('/views/admin/productos.php');
                return;
            }

            try {
                $stmt = $this->db->prepare("\n                    INSERT INTO productos (tienda_id, categoria_id, unidad_medida_id, nombre, precio_venta, precio_compra, stock_minimo, stock_actual, activo)\n                    VALUES (:tienda_id, :categoria_id, :unidad_medida_id, :nombre, :precio_venta, :precio_compra, :stock_minimo, 0, :activo)\n                ");

                $stmt->execute($data);

                setFlashMessage('Producto creado exitosamente', 'success');
                redirect('/views/admin/productos.php');

            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setFlashMessage('Ya existe un producto con ese nombre en la categoria seleccionada', 'danger');
                } else {
                    setFlashMessage('Error al crear producto: ' . $e->getMessage(), 'danger');
                }
                redirect('/views/admin/productos.php');
            }
        }
    }

    public function editar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $id = $_POST['id'];
            $unidad_medida_id = !empty($_POST['unidad_medida_id']) ? intval($_POST['unidad_medida_id']) : null;

            // MULTI-TENANT: Validar que el producto pertenece a la tienda
            if (!TenantHelper::validateAccess('productos', $id)) {
                setFlashMessage('Acceso denegado', 'danger');
                redirect('/views/admin/productos.php');
                return;
            }

            // Validar categoria segun tienda
            if (!validarCategoriaTienda($_POST['categoria_id'])) {
                setFlashMessage('Categoria no valida para esta tienda', 'danger');
                redirect('/views/admin/productos.php');
                return;
            }

            if (!validarUnidadMedida($unidad_medida_id)) {
                setFlashMessage('Unidad de medida no valida', 'danger');
                redirect('/views/admin/productos.php');
                return;
            }

            try {
                $stmt = $this->db->prepare("\n                    UPDATE productos\n                    SET categoria_id = :categoria_id,\n                        unidad_medida_id = :unidad_medida_id,\n                        nombre = :nombre,\n                        precio_venta = :precio_venta,\n                        precio_compra = :precio_compra,\n                        stock_minimo = :stock_minimo,\n                        activo = :activo\n                    WHERE id = :id\n                ");

                $stmt->execute([
                    'categoria_id' => $_POST['categoria_id'],
                    'unidad_medida_id' => $unidad_medida_id,
                    'nombre' => sanitizeInput($_POST['nombre']),
                    'precio_venta' => floatval($_POST['precio_venta']),
                    'precio_compra' => floatval($_POST['precio_compra'] ?? 0),
                    'stock_minimo' => intval($_POST['stock_minimo'] ?? 5),
                    'activo' => $_POST['activo'],
                    'id' => $id
                ]);

                setFlashMessage('Producto actualizado exitosamente', 'success');
                redirect('/views/admin/productos.php');

            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setFlashMessage('Ya existe un producto con ese nombre en la categoria seleccionada', 'danger');
                } else {
                    setFlashMessage('Error al actualizar producto: ' . $e->getMessage(), 'danger');
                }
                redirect('/views/admin/productos.php');
            }
        }
    }

    public function eliminar() {
        if (isset($_GET['id'])) {
            requireCsrfToken();
            $id = $_GET['id'];

            // MULTI-TENANT: Validar que el producto pertenece a la tienda
            if (!TenantHelper::validateAccess('productos', $id)) {
                setFlashMessage('Acceso denegado', 'danger');
                redirect('/views/admin/productos.php');
                return;
            }

            try {
                $stmt = $this->db->prepare("DELETE FROM productos WHERE id = :id");
                $stmt->execute(['id' => $id]);

                setFlashMessage('Producto eliminado exitosamente', 'success');
                redirect('/views/admin/productos.php');

            } catch (PDOException $e) {
                setFlashMessage('No se puede eliminar el producto. Tiene ventas asociadas.', 'danger');
                redirect('/views/admin/productos.php');
            }
        }
    }

    public function crear_cajero() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $unidad_medida_id = !empty($_POST['unidad_medida_id']) ? intval($_POST['unidad_medida_id']) : null;
            $data = [
                'categoria_id' => $_POST['categoria_id'],
                'unidad_medida_id' => $unidad_medida_id,
                'nombre' => sanitizeInput($_POST['nombre']),
                'precio_venta' => floatval($_POST['precio_venta']),
                'stock_minimo' => intval($_POST['stock_min'])
            ];

            // MULTI-TENANT: Agregar tienda_id automaticamente
            TenantHelper::addTenantId($data);

            // Validar categoria segun tienda
            if (!validarCategoriaTienda($data['categoria_id'])) {
                setFlashMessage('Categoria no valida para esta tienda', 'danger');
                redirect('/views/cajero/productos.php');
                return;
            }

            if (!validarUnidadMedida($unidad_medida_id)) {
                setFlashMessage('Unidad de medida no valida', 'danger');
                redirect('/views/cajero/productos.php');
                return;
            }

            try {
                $stmt = $this->db->prepare("\n                    INSERT INTO productos (tienda_id, categoria_id, unidad_medida_id, nombre, precio_venta, stock_minimo, stock_actual, activo)\n                    VALUES (:tienda_id, :categoria_id, :unidad_medida_id, :nombre, :precio_venta, :stock_minimo, 0, 1)\n                ");

                $stmt->execute($data);

                setFlashMessage('Producto registrado exitosamente', 'success');
                redirect('/views/cajero/productos.php');

            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setFlashMessage('Ya existe un producto con ese nombre en la categoria seleccionada', 'danger');
                } else {
                    setFlashMessage('Error al registrar producto: ' . $e->getMessage(), 'danger');
                }
                redirect('/views/cajero/productos.php');
            }
        }
    }
}

// Manejar acciones
if (isset($_POST['action'])) {
    $controller = new ProductoController();
    $action = $_POST['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
} elseif (isset($_GET['action'])) {
    $controller = new ProductoController();
    $action = $_GET['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
