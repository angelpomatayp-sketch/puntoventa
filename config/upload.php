<?php
/**
 * Configuración para subida de logos de tienda
 * Sistema Multi-Tenant
 */

define('UPLOAD_DIR', BASE_PATH . '/uploads/logos/');
define('UPLOAD_URL', BASE_URL . '/uploads/logos/');
define('MAX_FILE_SIZE', 2097152); // 2MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

/**
 * Subir logo de tienda
 *
 * @param array $file Array de $_FILES
 * @param int $tienda_id ID de la tienda
 * @return array ['success' => bool, 'error' => string, 'filename' => string, 'path' => string]
 */
function uploadLogo($file, $tienda_id) {
    // Validar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir archivo'];
    }

    // Validar tamaño
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'Archivo muy grande (máximo 2MB)'];
    }

    // Validar tipo MIME real del archivo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido (solo JPG, PNG)'];
    }

    // Validar que sea una imagen vÃ¡lida
    if (!getimagesize($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Archivo de imagen invÃ¡lido'];
    }

    // Validar extensión
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Extensión de archivo no permitida (solo JPG, PNG)'];
    }

    // Crear directorio si no existe
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Eliminar logo anterior si existe
    deleteLogo($tienda_id);

    // Generar nombre único
    $filename = 'tienda_' . $tienda_id . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;

    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => 'uploads/logos/' . $filename
        ];
    }

    return ['success' => false, 'error' => 'Error al guardar archivo'];
}

/**
 * Eliminar logo de tienda
 *
 * @param int $tienda_id ID de la tienda
 * @return void
 */
function deleteLogo($tienda_id) {
    $pattern = UPLOAD_DIR . 'tienda_' . $tienda_id . '.*';
    $files = glob($pattern);
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

/**
 * Obtener URL del logo de tienda
 *
 * @param string|null $logo_path Ruta relativa del logo
 * @return string URL completa del logo
 */
function getLogoUrl($logo_path) {
    if ($logo_path && file_exists(BASE_PATH . '/' . $logo_path)) {
        return BASE_URL . '/' . $logo_path;
    }
    // Retornar logo por defecto si no existe
    return BASE_URL . '/assets/img/logo-default.png';
}
