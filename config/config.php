<?php
/**
 * Configuración General del Sistema
 * Sistema POS Fast Food
 */

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', getenv('SESSION_SECURE') ?: 0);

session_start();

// Zona horaria
date_default_timezone_set('America/Lima');

// Rutas del sistema
define('BASE_PATH', dirname(__DIR__));
$appUrl = getenv('APP_URL');
define('BASE_URL', $appUrl !== false ? $appUrl : '/fastfood');

// Incluir base de datos (esto carga las variables de entorno)
require_once BASE_PATH . '/config/database.php';

// Configuración de errores basada en el entorno
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// Incluir helpers de multi-tenancy
require_once BASE_PATH . '/config/tenant.php';

// Incluir helpers de upload
require_once BASE_PATH . '/config/upload.php';

// ============================================
// CONSTANTES DEL SISTEMA
// ============================================
define('IGV_PORCENTAJE', 18);
define('SESSION_TIMEOUT_SECONDS', 1800);

// Lista blanca de tablas permitidas para validación
define('TABLAS_PERMITIDAS', [
    'productos',
    'categorias',
    'clientes',
    'ventas',
    'detalle_venta',
    'pagos',
    'cajas',
    'usuarios',
    'tiendas',
    'movimientos_inventario'
]);

// ============================================
// FUNCIONES AUXILIARES
// ============================================

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['rol']);
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['rol'] === 'ADMINISTRADOR' || $_SESSION['rol'] === 'SUPER_ADMINISTRADOR');
}

function isCajero() {
    return isLoggedIn() && $_SESSION['rol'] === 'CAJERO';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/views/auth/login.php');
    }

    // Cierre de sesion por inactividad
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
        session_unset();
        session_destroy();
        session_start();
        setFlashMessage('Tu sesion expiro por inactividad. Inicia sesion nuevamente.', 'warning');
        redirect('/views/auth/login.php');
    }
    $_SESSION['last_activity'] = time();

    // VALIDACIÓN DE TIENDA SUSPENDIDA
    if (!isSuperAdmin() && hasTienda()) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT estado FROM tiendas WHERE id = :id");
        $stmt->execute(['id' => getTiendaId()]);
        $tienda = $stmt->fetch();

        if (!$tienda || $tienda['estado'] === 'SUSPENDIDA') {
            session_unset();
            session_destroy();
            session_start();
            setFlashMessage('Tu tienda ha sido suspendida. Contacta al administrador del sistema.', 'danger');
            redirect('/views/auth/login.php');
        }
    }

    // VALIDACIÓN DE USUARIO DESACTIVADO
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT activo FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario || !$usuario['activo']) {
        session_unset();
        session_destroy();
        session_start();
        setFlashMessage('Tu cuenta ha sido desactivada. Contacta al administrador.', 'danger');
        redirect('/views/auth/login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('/index.php');
    }
}

function formatMoney($amount) {
    return 'S/. ' . number_format($amount ?? 0, 2);
}

function formatDate($date) {
    return date('d/m/Y H:i:s', strtotime($date));
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Escapar salida de forma segura
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// ============================================
// CSRF
// ============================================

function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getRequestCsrfToken() {
    return $_POST['csrf_token']
        ?? $_GET['csrf_token']
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
}

function requireCsrfToken() {
    $token = getRequestCsrfToken();
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        die('Acceso denegado (CSRF).');
    }
}

function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Obtener el siguiente número de boleta (por tienda)
 * Formato: B[SERIE]-[CORRELATIVO]
 * Ejemplo: B001-00000001 (tienda 1), B002-00000001 (tienda 2)
 */
function getNextTicketNumber() {
    $db = Database::getInstance()->getConnection();
    $tiendaId = getTiendaId() ?: 1;

    // Serie basada en ID de tienda (3 dígitos)
    $serie = str_pad($tiendaId, 3, '0', STR_PAD_LEFT);

    // Buscar último correlativo de esta tienda
    $stmt = $db->prepare("SELECT nro_ticket FROM ventas WHERE tienda_id = :tienda_id ORDER BY id DESC LIMIT 1");
    $stmt->execute(['tienda_id' => $tiendaId]);
    $last = $stmt->fetch();

    if ($last) {
        // Extraer el correlativo después del guion (B001-XXXXXXXX)
        $parts = explode('-', $last['nro_ticket']);
        $correlativo = isset($parts[1]) ? intval($parts[1]) + 1 : 1;
    } else {
        $correlativo = 1;
    }

    // Formato final: B001-00000001
    return 'B' . $serie . '-' . str_pad($correlativo, 8, '0', STR_PAD_LEFT);
}

/**
 * Verificar si hay una caja abierta (filtrada por tienda)
 * CORREGIDO: Usa prepared statements
 */
function getCajaAbierta($usuario_id = null) {
    $db = Database::getInstance()->getConnection();
    $params = [];

    $sql = "SELECT * FROM cajas WHERE estado = 'ABIERTA'";

    if (hasTienda()) {
        $sql .= " AND tienda_id = :tienda_id";
        $params['tienda_id'] = getTiendaId();
    }

    if ($usuario_id) {
        $sql .= " AND usuario_id = :usuario_id";
        $params['usuario_id'] = $usuario_id;
    }

    $sql .= " ORDER BY id DESC LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch();
}

// ============================================
// FUNCIONES MULTI-TENANT
// ============================================

function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['rol'] === 'SUPER_ADMINISTRADOR';
}

function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        setFlashMessage('Acceso denegado. Requiere permisos de Super Administrador.', 'danger');
        redirect('/index.php');
    }
}

function getTiendaId() {
    return $_SESSION['tienda_id'] ?? null;
}

function getTiendaNombre() {
    return $_SESSION['tienda_nombre'] ?? 'Sistema';
}

function getTiendaSlug() {
    return $_SESSION['tienda_slug'] ?? null;
}

function hasTienda() {
    return isset($_SESSION['tienda_id']) && $_SESSION['tienda_id'] !== null;
}

function getTiendaActual() {
    if (!hasTienda()) {
        return null;
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM tiendas WHERE id = :id");
    $stmt->execute(['id' => getTiendaId()]);
    return $stmt->fetch();
}

/**
 * Validar que un registro pertenezca a la tienda actual
 * CORREGIDO: Valida nombre de tabla contra lista blanca
 */
function validarAccesoTienda($tabla, $id) {
    // SUPER_ADMIN puede acceder a todo
    if (isSuperAdmin()) {
        return true;
    }

    $tienda_id = getTiendaId();
    if (!$tienda_id) {
        return false;
    }

    // Validar que la tabla esté en la lista blanca
    if (!in_array($tabla, TABLAS_PERMITIDAS)) {
        error_log("Intento de acceso a tabla no permitida: " . $tabla);
        return false;
    }

    $db = Database::getInstance()->getConnection();

    // Construir query de forma segura (tabla ya validada contra lista blanca)
    $sql = "SELECT COUNT(*) as count FROM `{$tabla}` WHERE id = :id AND tienda_id = :tienda_id";

    $stmt = $db->prepare($sql);
    $stmt->execute(['id' => $id, 'tienda_id' => $tienda_id]);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}

/**
 * Validar que una categorÃ­a pertenezca a la tienda actual
 */
function validarCategoriaTienda($categoria_id) {
    if (isSuperAdmin()) {
        return true;
    }

    $tienda_id = getTiendaId();
    if (!$tienda_id) {
        return false;
    }

    $db = Database::getInstance()->getConnection();
    $sql = "
        SELECT COUNT(*) as count
        FROM categorias c
        LEFT JOIN tienda_categorias tc
            ON c.id = tc.categoria_id
            AND tc.tienda_id = :tienda_id_1
            AND tc.activo = 1
        WHERE c.id = :categoria_id
        AND c.activo = 1
        AND (c.tienda_id = :tienda_id_2 OR tc.id IS NOT NULL)
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'tienda_id_1' => $tienda_id,
        'tienda_id_2' => $tienda_id,
        'categoria_id' => $categoria_id
    ]);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}

/**
 * Validar que una unidad de medida exista y esté activa
 */
function validarUnidadMedida($unidad_medida_id) {
    if (!$unidad_medida_id) {
        return true; // Permitir NULL
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM unidades_medida WHERE id = :id AND activo = 1");
    $stmt->execute(['id' => $unidad_medida_id]);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}

// ============================================
// FUNCIONES DE SUSCRIPCIONES
// ============================================

function calcularDiasRestantes($fecha_proximo_pago) {
    if (!$fecha_proximo_pago) {
        return null;
    }

    $hoy = new DateTime();
    $proximo = new DateTime($fecha_proximo_pago);
    $diferencia = $hoy->diff($proximo);

    if ($proximo < $hoy) {
        return -$diferencia->days;
    }

    return $diferencia->days;
}

function getEstadoSuscripcion($fecha_proximo_pago, $estado_tienda) {
    if ($estado_tienda === 'SUSPENDIDA') {
        return [
            'estado' => 'SUSPENDIDA',
            'clase' => 'danger',
            'icono' => 'pause-circle',
            'texto' => 'SUSPENDIDA'
        ];
    }

    if (!$fecha_proximo_pago) {
        return [
            'estado' => 'SIN_FECHA',
            'clase' => 'secondary',
            'icono' => 'question-circle',
            'texto' => 'Sin fecha'
        ];
    }

    $dias = calcularDiasRestantes($fecha_proximo_pago);

    if ($dias === null) {
        return [
            'estado' => 'SIN_FECHA',
            'clase' => 'secondary',
            'icono' => 'question-circle',
            'texto' => 'Sin fecha'
        ];
    }

    if ($dias < 0) {
        return [
            'estado' => 'VENCIDA',
            'clase' => 'danger',
            'icono' => 'x-circle',
            'texto' => 'VENCIDA (' . abs($dias) . ' días)'
        ];
    } elseif ($dias <= 7) {
        return [
            'estado' => 'POR_VENCER',
            'clase' => 'warning',
            'icono' => 'exclamation-triangle',
            'texto' => 'Por vencer (' . $dias . ' días)'
        ];
    } else {
        return [
            'estado' => 'ACTIVA',
            'clase' => 'success',
            'icono' => 'check-circle',
            'texto' => $dias . ' días'
        ];
    }
}

function getCategoriasDisponibles($tienda_id = null) {
    $db = Database::getInstance()->getConnection();

    if (isSuperAdmin() && $tienda_id === null) {
        $stmt = $db->query("
            SELECT * FROM categorias
            WHERE es_global = 1 AND activo = 1
            ORDER BY tipo_negocio, nombre ASC
        ");
        return $stmt->fetchAll();
    }

    $tienda_id = $tienda_id ?? getTiendaId();

    if (!$tienda_id) {
        return [];
    }

    $stmt = $db->prepare("
        SELECT c.*
        FROM categorias c
        INNER JOIN tienda_categorias tc ON c.id = tc.categoria_id
        WHERE tc.tienda_id = :tienda_id
        AND tc.activo = 1
        AND c.activo = 1
        ORDER BY c.nombre ASC
    ");
    $stmt->execute(['tienda_id' => $tienda_id]);
    return $stmt->fetchAll();
}
