<?php
/**
 * Helpers para Multi-Tenancy
 * Filtrado automático de queries por tienda
 */

class TenantHelper {

    /**
     * Agregar filtro WHERE tienda_id a una query SQL
     * SUPER_ADMIN ve todo (no filtra)
     *
     * @param string $sql Query SQL (se modifica por referencia)
     * @param int|null $tiendaId ID de tienda específica (opcional)
     * @return void
     */
    public static function addTenantScope(&$sql, $tiendaId = null) {
        // SUPER_ADMIN ve todos los datos
        if (isSuperAdmin() && $tiendaId === null) {
            return;
        }

        $tiendaId = $tiendaId ?? getTiendaId();

        if (!$tiendaId) {
            return;
        }

        // Detectar si ya tiene WHERE
        if (stripos($sql, 'WHERE') !== false) {
            $sql .= " AND tienda_id = " . intval($tiendaId);
        } else {
            $sql .= " WHERE tienda_id = " . intval($tiendaId);
        }
    }

    /**
     * Agregar tienda_id a un array de datos para INSERT
     *
     * @param array $data Array de datos (se modifica por referencia)
     * @return void
     */
    public static function addTenantId(&$data) {
        $tiendaId = getTiendaId();
        if ($tiendaId) {
            $data['tienda_id'] = $tiendaId;
        }
    }

    /**
     * Validar que un registro pertenezca a la tienda actual
     *
     * @param string $tabla Nombre de la tabla
     * @param int $id ID del registro
     * @return bool
     */
    public static function validateAccess($tabla, $id) {
        return validarAccesoTienda($tabla, $id);
    }
}

/**
 * Helper rápido para queries con filtro automático de tienda
 *
 * @param string $sql Query SQL
 * @param array $params Parámetros para prepared statement
 * @param int|null $tiendaId ID de tienda específica (opcional)
 * @return PDOStatement
 */
function queryWithTenant($sql, $params = [], $tiendaId = null) {
    $db = Database::getInstance()->getConnection();

    // Agregar filtro de tienda automáticamente
    TenantHelper::addTenantScope($sql, $tiendaId);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
