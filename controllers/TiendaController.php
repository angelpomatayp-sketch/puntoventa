<?php
require_once '../config/config.php';

class TiendaController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crear nueva tienda (solo SUPER_ADMIN)
     */
    public function crear() {
        requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $nombre_negocio = sanitizeInput($_POST['nombre_negocio']);
            $ruc = sanitizeInput($_POST['ruc']);
            $direccion = sanitizeInput($_POST['direccion']);
            $telefono = sanitizeInput($_POST['telefono']);
            $email = sanitizeInput($_POST['email']);

            // Datos del admin de la tienda
            $admin_nombre = sanitizeInput($_POST['admin_nombre']);
            $admin_usuario = sanitizeInput($_POST['admin_usuario']);
            $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);

            try {
                $this->db->beginTransaction();

                // Generar slug único
                $slug = $this->generateSlug($nombre_negocio);

                // 1. Crear tienda
                $stmt = $this->db->prepare("
                    INSERT INTO tiendas (nombre_negocio, slug, ruc, direccion, telefono, email, estado, fecha_activacion)
                    VALUES (:nombre_negocio, :slug, :ruc, :direccion, :telefono, :email, 'ACTIVA', CURDATE())
                ");
                $stmt->execute([
                    'nombre_negocio' => $nombre_negocio,
                    'slug' => $slug,
                    'ruc' => $ruc,
                    'direccion' => $direccion,
                    'telefono' => $telefono,
                    'email' => $email
                ]);

                $tienda_id = $this->db->lastInsertId();

                // 2. Crear usuario ADMINISTRADOR de la tienda
                $stmt = $this->db->prepare("
                    INSERT INTO usuarios (tienda_id, nombre, usuario, password, rol, activo)
                    VALUES (:tienda_id, :nombre, :usuario, :password, 'ADMINISTRADOR', TRUE)
                ");
                $stmt->execute([
                    'tienda_id' => $tienda_id,
                    'nombre' => $admin_nombre,
                    'usuario' => $admin_usuario,
                    'password' => $admin_password
                ]);

                // 3. Crear cliente ANÓNIMO para esta tienda
                $stmt = $this->db->prepare("
                    INSERT INTO clientes (tienda_id, nombre, dni_ruc, telefono, activo)
                    VALUES (:tienda_id, 'ANÓNIMO', '00000000', '-', TRUE)
                ");
                $stmt->execute(['tienda_id' => $tienda_id]);

                // 4. Crear categorías por defecto
                $categorias = ['Bebidas', 'Golosinas', 'Galletas', 'Variado', 'Fast Food'];
                $stmt = $this->db->prepare("
                    INSERT INTO categorias (tienda_id, nombre, activo)
                    VALUES (:tienda_id, :nombre, TRUE)
                ");
                foreach ($categorias as $cat) {
                    $stmt->execute(['tienda_id' => $tienda_id, 'nombre' => $cat]);
                }

                $this->db->commit();

                setFlashMessage("Tienda '{$nombre_negocio}' creada exitosamente. Usuario: {$admin_usuario}", 'success');
                redirect('/views/super_admin/tiendas.php');

            } catch (PDOException $e) {
                $this->db->rollBack();

                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    if (strpos($e->getMessage(), 'slug') !== false) {
                        setFlashMessage('Ya existe una tienda con ese nombre', 'danger');
                    } else if (strpos($e->getMessage(), 'usuario') !== false) {
                        setFlashMessage('El nombre de usuario ya está en uso', 'danger');
                    } else {
                        setFlashMessage('El RUC ya está registrado', 'danger');
                    }
                } else {
                    setFlashMessage('Error al crear tienda: ' . $e->getMessage(), 'danger');
                }
                redirect('/views/super_admin/crear_tienda.php');
            }
        }
    }

    /**
     * Actualizar tienda (SUPER_ADMIN o ADMIN de la tienda)
     */
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $tienda_id = intval($_POST['tienda_id']);

            // Validar permisos
            if (!isSuperAdmin() && getTiendaId() !== $tienda_id) {
                setFlashMessage('No tienes permisos para editar esta tienda', 'danger');
                redirect('/index.php');
                return;
            }

            // Preparar campos a actualizar
            $fields = [
                'nombre_negocio' => sanitizeInput($_POST['nombre_negocio']),
                'ruc' => sanitizeInput($_POST['ruc']),
                'direccion' => sanitizeInput($_POST['direccion']),
                'telefono' => sanitizeInput($_POST['telefono']),
                'email' => sanitizeInput($_POST['email']),
                'igv' => floatval($_POST['igv']),
                'id' => $tienda_id
            ];

            // Si es SUPER_ADMIN, puede actualizar plan, monto_mensual y notas_admin
            if (isSuperAdmin() && isset($_POST['plan'])) {
                $sql = "
                    UPDATE tiendas
                    SET nombre_negocio = :nombre_negocio,
                        ruc = :ruc,
                        direccion = :direccion,
                        telefono = :telefono,
                        email = :email,
                        igv = :igv,
                        plan = :plan,
                        monto_mensual = :monto_mensual,
                        notas_admin = :notas_admin
                    WHERE id = :id
                ";

                $fields['plan'] = sanitizeInput($_POST['plan']);
                $fields['monto_mensual'] = floatval($_POST['monto_mensual']);
                $fields['notas_admin'] = sanitizeInput($_POST['notas_admin'] ?? '');
            } else {
                // Admin de tienda solo puede editar datos básicos
                $sql = "
                    UPDATE tiendas
                    SET nombre_negocio = :nombre_negocio,
                        ruc = :ruc,
                        direccion = :direccion,
                        telefono = :telefono,
                        email = :email,
                        igv = :igv
                    WHERE id = :id
                ";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($fields);

            // Actualizar sesión si es la tienda actual
            if (getTiendaId() === $tienda_id) {
                $_SESSION['tienda_nombre'] = $_POST['nombre_negocio'];
            }

            setFlashMessage('Configuración actualizada exitosamente', 'success');

            if (isSuperAdmin()) {
                redirect('/views/super_admin/tiendas.php');
            } else {
                redirect('/views/admin/configuracion.php');
            }
        }
    }

    /**
     * Cambiar estado de tienda (solo SUPER_ADMIN)
     */
    public function cambiar_estado() {
        requireSuperAdmin();

        if (isset($_GET['id']) && isset($_GET['estado'])) {
            requireCsrfToken();
            $id = intval($_GET['id']);
            $estado = $_GET['estado'] === 'ACTIVA' ? 'ACTIVA' : 'SUSPENDIDA';

            $stmt = $this->db->prepare("UPDATE tiendas SET estado = :estado WHERE id = :id");
            $stmt->execute(['estado' => $estado, 'id' => $id]);

            $mensaje = $estado === 'ACTIVA' ? 'Tienda activada' : 'Tienda suspendida';
            setFlashMessage($mensaje, 'success');
            redirect('/views/super_admin/tiendas.php');
        }
    }

    /**
     * Subir/actualizar logo de tienda
     */
    public function actualizar_logo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
            requireCsrfToken();
            $tienda_id = isSuperAdmin() ? intval($_POST['tienda_id']) : getTiendaId();

            // Validar permisos
            if (!isSuperAdmin() && getTiendaId() !== $tienda_id) {
                setFlashMessage('No tienes permisos', 'danger');
                redirect('/index.php');
                return;
            }

            $result = uploadLogo($_FILES['logo'], $tienda_id);

            if ($result['success']) {
                // Actualizar BD
                $stmt = $this->db->prepare("UPDATE tiendas SET logo = :logo WHERE id = :id");
                $stmt->execute(['logo' => $result['path'], 'id' => $tienda_id]);

                setFlashMessage('Logo actualizado exitosamente', 'success');
            } else {
                setFlashMessage($result['error'], 'danger');
            }

            if (isSuperAdmin()) {
                redirect('/views/super_admin/editar_tienda.php?id=' . $tienda_id);
            } else {
                redirect('/views/admin/configuracion.php');
            }
        }
    }

    /**
     * Eliminar logo de tienda
     */
    public function eliminar_logo() {
        $tienda_id = isSuperAdmin() && isset($_GET['tienda_id'])
            ? intval($_GET['tienda_id'])
            : getTiendaId();
        requireCsrfToken();

        // Validar permisos
        if (!isSuperAdmin() && getTiendaId() !== $tienda_id) {
            setFlashMessage('No tienes permisos', 'danger');
            redirect('/index.php');
            return;
        }

        deleteLogo($tienda_id);

        $stmt = $this->db->prepare("UPDATE tiendas SET logo = NULL WHERE id = :id");
        $stmt->execute(['id' => $tienda_id]);

        setFlashMessage('Logo eliminado exitosamente', 'success');

        if (isSuperAdmin()) {
            redirect('/views/super_admin/editar_tienda.php?id=' . $tienda_id);
        } else {
            redirect('/views/admin/configuracion.php');
        }
    }

    /**
     * Generar slug único para tienda
     */
    private function generateSlug($nombre) {
        $slug = strtolower(trim($nombre));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Verificar unicidad
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM tiendas WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            $slug .= '-' . time();
        }

        return $slug;
    }

    // ====================================
    // SISTEMA DE SUSCRIPCIONES
    // ====================================

    /**
     * Registrar pago de suscripción
     */
    public function registrar_pago() {
        requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCsrfToken();
            $tienda_id = intval($_POST['tienda_id']);
            $monto = floatval($_POST['monto']);
            $fecha_pago = $_POST['fecha_pago'];
            $metodo_pago = $_POST['metodo_pago'];
            $referencia = sanitizeInput($_POST['referencia'] ?? '');
            $notas = sanitizeInput($_POST['notas'] ?? '');
            $activar_tienda = isset($_POST['activar_tienda']);

            try {
                $this->db->beginTransaction();

                // Calcular período (1 mes desde la fecha de pago)
                $periodo_desde = $fecha_pago;
                $periodo_hasta = date('Y-m-d', strtotime($fecha_pago . ' +30 days'));

                // 1. Registrar pago en historial
                $stmt = $this->db->prepare("
                    INSERT INTO pagos_suscripcion
                    (tienda_id, monto, fecha_pago, periodo_desde, periodo_hasta, metodo_pago, referencia, notas, registrado_por)
                    VALUES (:tienda_id, :monto, :fecha_pago, :periodo_desde, :periodo_hasta, :metodo_pago, :referencia, :notas, :registrado_por)
                ");
                $stmt->execute([
                    'tienda_id' => $tienda_id,
                    'monto' => $monto,
                    'fecha_pago' => $fecha_pago,
                    'periodo_desde' => $periodo_desde,
                    'periodo_hasta' => $periodo_hasta,
                    'metodo_pago' => $metodo_pago,
                    'referencia' => $referencia,
                    'notas' => $notas,
                    'registrado_por' => $_SESSION['user_id']
                ]);

                // 2. Actualizar fecha de próximo pago en tienda
                $stmt = $this->db->prepare("
                    UPDATE tiendas
                    SET fecha_ultimo_pago = :fecha_pago,
                        fecha_proximo_pago = :fecha_proximo_pago
                    WHERE id = :id
                ");
                $stmt->execute([
                    'fecha_pago' => $fecha_pago,
                    'fecha_proximo_pago' => $periodo_hasta,
                    'id' => $tienda_id
                ]);

                // 3. Si se marcó "activar tienda", activarla y sus usuarios
                if ($activar_tienda) {
                    // Activar tienda
                    $stmt = $this->db->prepare("UPDATE tiendas SET estado = 'ACTIVA' WHERE id = :id");
                    $stmt->execute(['id' => $tienda_id]);

                    // Activar todos los usuarios de la tienda
                    $stmt = $this->db->prepare("UPDATE usuarios SET activo = TRUE WHERE tienda_id = :tienda_id");
                    $stmt->execute(['tienda_id' => $tienda_id]);
                }

                $this->db->commit();

                setFlashMessage('Pago registrado exitosamente', 'success');
                redirect('/views/super_admin/tiendas.php');

            } catch (PDOException $e) {
                $this->db->rollBack();
                setFlashMessage('Error al registrar pago: ' . $e->getMessage(), 'danger');
                redirect('/views/super_admin/tiendas.php');
            }
        }
    }

    /**
     * Suspender tienda completa (tienda + todos sus usuarios)
     */
    public function suspender_tienda_completa() {
        requireSuperAdmin();

        if (isset($_GET['id'])) {
            requireCsrfToken();
            $tienda_id = intval($_GET['id']);

            try {
                $this->db->beginTransaction();

                // 1. Suspender la tienda
                $stmt = $this->db->prepare("UPDATE tiendas SET estado = 'SUSPENDIDA' WHERE id = :id");
                $stmt->execute(['id' => $tienda_id]);

                // 2. Desactivar TODOS los usuarios de la tienda
                $stmt = $this->db->prepare("UPDATE usuarios SET activo = FALSE WHERE tienda_id = :tienda_id");
                $stmt->execute(['tienda_id' => $tienda_id]);

                $this->db->commit();

                setFlashMessage('Tienda y todos sus usuarios suspendidos correctamente', 'success');

            } catch (PDOException $e) {
                $this->db->rollBack();
                setFlashMessage('Error al suspender: ' . $e->getMessage(), 'danger');
            }

            redirect('/views/super_admin/tiendas.php');
        }
    }

    /**
     * Activar tienda completa (tienda + todos sus usuarios)
     */
    public function activar_tienda_completa() {
        requireSuperAdmin();

        if (isset($_GET['id'])) {
            requireCsrfToken();
            $tienda_id = intval($_GET['id']);

            try {
                $this->db->beginTransaction();

                // 1. Activar la tienda
                $stmt = $this->db->prepare("UPDATE tiendas SET estado = 'ACTIVA' WHERE id = :id");
                $stmt->execute(['id' => $tienda_id]);

                // 2. Activar TODOS los usuarios de la tienda
                $stmt = $this->db->prepare("UPDATE usuarios SET activo = TRUE WHERE tienda_id = :tienda_id");
                $stmt->execute(['tienda_id' => $tienda_id]);

                $this->db->commit();

                setFlashMessage('Tienda y todos sus usuarios activados correctamente', 'success');

            } catch (PDOException $e) {
                $this->db->rollBack();
                setFlashMessage('Error al activar: ' . $e->getMessage(), 'danger');
            }

            redirect('/views/super_admin/tiendas.php');
        }
    }

    /**
     * Eliminar tienda permanentemente (solo SUPER_ADMIN)
     */
    public function eliminar_tienda() {
        requireSuperAdmin();

        if (isset($_GET['id'])) {
            requireCsrfToken();
            $tienda_id = intval($_GET['id']);

            try {
                $this->db->beginTransaction();

                // Eliminar logo si existe
                deleteLogo($tienda_id);

                // Eliminar tienda (cascada elimina relaciones)
                $stmt = $this->db->prepare("DELETE FROM tiendas WHERE id = :id");
                $stmt->execute(['id' => $tienda_id]);

                $this->db->commit();

                setFlashMessage('Tienda eliminada permanentemente', 'success');

            } catch (PDOException $e) {
                $this->db->rollBack();
                setFlashMessage('Error al eliminar tienda: ' . $e->getMessage(), 'danger');
            }

            redirect('/views/super_admin/tiendas.php');
        }
    }

    /**
     * Ver historial de pagos de una tienda
     */
    public function historial_pagos() {
        requireSuperAdmin();

        // Este método solo retorna los datos, la vista se encarga de mostrarlos
        if (isset($_GET['tienda_id'])) {
            $tienda_id = intval($_GET['tienda_id']);

            $stmt = $this->db->prepare("
                SELECT ps.*, u.nombre as registrado_por_nombre
                FROM pagos_suscripcion ps
                INNER JOIN usuarios u ON ps.registrado_por = u.id
                WHERE ps.tienda_id = :tienda_id
                ORDER BY ps.fecha_pago DESC
            ");
            $stmt->execute(['tienda_id' => $tienda_id]);

            return $stmt->fetchAll();
        }

        return [];
    }
}

// Manejar acciones
if (isset($_GET['action'])) {
    $controller = new TiendaController();
    $action = $_GET['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
