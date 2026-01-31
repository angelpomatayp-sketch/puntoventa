<?php
require_once '../config/config.php';
requireLogin();

class CajaController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function abrir() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            // MULTI-TENANT: Verificar que no haya una caja abierta EN LA TIENDA
            $caja_abierta = getCajaAbierta(); // Ya filtrada por tienda en config.php
            if ($caja_abierta) {
                setFlashMessage('Ya existe una caja abierta en esta tienda', 'danger');
                if (isAdmin()) {
                    redirect('/views/admin/cajas.php');
                } else {
                    redirect('/views/cajero/caja.php');
                }
                return;
            }

            $data = [
                'usuario_id' => $_SESSION['user_id'],
                'saldo_inicial' => floatval($_POST['saldo_inicial'])
            ];

            // MULTI-TENANT: Agregar tienda_id automáticamente
            TenantHelper::addTenantId($data);

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO cajas (tienda_id, usuario_id, saldo_inicial, estado)
                    VALUES (:tienda_id, :usuario_id, :saldo_inicial, 'ABIERTA')
                ");

                $stmt->execute($data);

                setFlashMessage('Caja abierta exitosamente', 'success');

            } catch (PDOException $e) {
                setFlashMessage('Error al abrir caja: ' . $e->getMessage(), 'danger');
            }

            if (isAdmin()) {
                redirect('/views/admin/cajas.php');
            } else {
                redirect('/views/cajero/caja.php');
            }
        }
    }

    public function cerrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $caja_id = $_POST['caja_id'];
            $efectivo_contado = floatval($_POST['efectivo_contado']);
            $yape_contado = floatval($_POST['yape_contado']);
            $observacion = sanitizeInput($_POST['observacion']);

            try {
                // MULTI-TENANT: Validar que la caja pertenece a la tienda
                if (!TenantHelper::validateAccess('cajas', $caja_id)) {
                    setFlashMessage('Acceso denegado', 'danger');
                    if (isAdmin()) {
                        redirect('/views/admin/cajas.php');
                    } else {
                        redirect('/views/cajero/caja.php');
                    }
                    return;
                }

                // Obtener datos de la caja
                $stmt = $this->db->prepare("SELECT * FROM cajas WHERE id = :id AND estado = 'ABIERTA'");
                $stmt->execute(['id' => $caja_id]);
                $caja = $stmt->fetch();

                if (!$caja) {
                    setFlashMessage('Caja no encontrada o ya cerrada', 'danger');
                    if (isAdmin()) {
                        redirect('/views/admin/cajas.php');
                    } else {
                        redirect('/views/cajero/caja.php');
                    }
                    return;
                }

                // Calcular diferencia de efectivo
                $efectivo_esperado = $caja['saldo_inicial'] + $caja['efectivo_esperado'];
                $diferencia_efectivo = $efectivo_contado - $efectivo_esperado;

                // Cerrar caja
                $stmt = $this->db->prepare("
                    UPDATE cajas
                    SET fecha_cierre = NOW(),
                        efectivo_contado = :efectivo_contado,
                        yape_total_registrado = :yape_contado,
                        diferencia_efectivo = :diferencia,
                        observacion = :observacion,
                        estado = 'CERRADA'
                    WHERE id = :id
                ");

                $stmt->execute([
                    'efectivo_contado' => $efectivo_contado,
                    'yape_contado' => $yape_contado,
                    'diferencia' => $diferencia_efectivo,
                    'observacion' => $observacion,
                    'id' => $caja_id
                ]);

                setFlashMessage('Caja cerrada. Pendiente de validación del administrador.', 'success');

            } catch (PDOException $e) {
                setFlashMessage('Error al cerrar caja: ' . $e->getMessage(), 'danger');
            }

            if (isAdmin()) {
                redirect('/views/admin/cajas.php');
            } else {
                redirect('/views/cajero/caja.php');
            }
        }
    }

    public function validar() {
        requireAdmin();

        if (isset($_GET['id'])) {
            requireCsrfToken();
            $caja_id = $_GET['id'];

            // MULTI-TENANT: Validar que la caja pertenece a la tienda
            if (!TenantHelper::validateAccess('cajas', $caja_id)) {
                setFlashMessage('Acceso denegado', 'danger');
                redirect('/views/admin/cajas.php');
                return;
            }

            try {
                $stmt = $this->db->prepare("
                    UPDATE cajas
                    SET estado = 'VALIDADA',
                        validado_por = :validado_por,
                        fecha_validacion = NOW()
                    WHERE id = :id AND estado = 'CERRADA'
                ");

                $stmt->execute([
                    'validado_por' => $_SESSION['user_id'],
                    'id' => $caja_id
                ]);

                setFlashMessage('Caja validada exitosamente', 'success');
                redirect('/views/admin/cajas.php');

            } catch (PDOException $e) {
                setFlashMessage('Error al validar caja: ' . $e->getMessage(), 'danger');
                redirect('/views/admin/cajas.php');
            }
        }
    }
}

// Manejar acciones
if (isset($_GET['action'])) {
    $controller = new CajaController();
    $action = $_GET['action'];

    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
