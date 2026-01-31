<?php
require_once '../config/config.php';
requireAdmin();

class ReporteController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function ventas_pdf() {
        $fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-d');
        $fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

        // MULTI-TENANT: Obtener configuración de la tienda actual
        $tienda = getTiendaActual();

        if (!$tienda) {
            die('Error: No se encontró la tienda');
        }

        // MULTI-TENANT: Obtener ventas filtradas por tienda
        $sql = "
            SELECT v.*, u.nombre as cajero
            FROM ventas v
            INNER JOIN usuarios u ON v.usuario_id = u.id
            WHERE DATE(v.fecha_hora) BETWEEN :desde AND :hasta
            AND v.estado = 'PAGADA'";
        TenantHelper::addTenantScope($sql);
        $sql .= " ORDER BY v.fecha_hora DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
        $ventas = $stmt->fetchAll();

        $total_monto = array_sum(array_column($ventas, 'total'));

        // Generar HTML
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Reporte de Ventas</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                h1 { text-align: center; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #4CAF50; color: white; }
                .text-right { text-align: right; }
                .header { text-align: center; margin-bottom: 20px; }
                .total { background-color: #f2f2f2; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2><?php echo e($tienda['nombre_negocio']); ?></h2>
                <p>RUC: <?php echo e($tienda['ruc']); ?></p>
                <h3>REPORTE DE VENTAS</h3>
                <p>Período: <?php echo e(date('d/m/Y', strtotime($fecha_desde))); ?> al <?php echo e(date('d/m/Y', strtotime($fecha_hasta))); ?></p>
                <p>Fecha de impresión: <?php echo e(date('d/m/Y H:i:s')); ?></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Fecha/Hora</th>
                        <th>Cajero</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-right">IGV</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas as $venta): ?>
                    <tr>
                        <td><?php echo e($venta['nro_ticket']); ?></td>
                        <td><?php echo e(date('d/m/Y H:i', strtotime($venta['fecha_hora']))); ?></td>
                        <td><?php echo e($venta['cajero']); ?></td>
                        <td class="text-right">S/. <?php echo e(number_format($venta['subtotal'], 2)); ?></td>
                        <td class="text-right">S/. <?php echo e(number_format($venta['igv'], 2)); ?></td>
                        <td class="text-right">S/. <?php echo e(number_format($venta['total'], 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total">
                        <td colspan="3">TOTAL GENERAL</td>
                        <td class="text-right">S/. <?php echo e(number_format(array_sum(array_column($ventas, 'subtotal')), 2)); ?></td>
                        <td class="text-right">S/. <?php echo e(number_format(array_sum(array_column($ventas, 'igv')), 2)); ?></td>
                        <td class="text-right">S/. <?php echo e(number_format($total_monto, 2)); ?></td>
                    </tr>
                </tfoot>
            </table>

            <p style="margin-top: 30px; text-align: center;">
                <strong>Total de ventas: <?php echo e(count($ventas)); ?></strong>
            </p>

            <script>
                window.onload = function() { window.print(); }
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    public function inventario_pdf() {
        // MULTI-TENANT: Obtener configuración de la tienda actual
        $tienda = getTiendaActual();

        if (!$tienda) {
            die('Error: No se encontró la tienda');
        }

          // MULTI-TENANT: Obtener productos filtrados por tienda
          $sql = "
              SELECT p.*, c.nombre as categoria, um.abreviatura as unidad
              FROM productos p
              LEFT JOIN categorias c ON p.categoria_id = c.id
              LEFT JOIN unidades_medida um ON p.unidad_medida_id = um.id
              WHERE p.activo = TRUE";
          if (!isSuperAdmin()) {
              $sql .= " AND p.tienda_id = :tienda_id";
          }
          $sql .= " ORDER BY c.nombre, p.nombre";

          $stmt = $this->db->prepare($sql);
          $params = [];
          if (!isSuperAdmin()) {
              $params['tienda_id'] = getTiendaId();
          }
          $stmt->execute($params);
        $productos = $stmt->fetchAll();

        // Calcular totales
        $total_productos = count($productos);
        $total_valor_stock = array_sum(array_map(function($p) {
            return $p['stock_actual'] * $p['precio_compra'];
        }, $productos));

        // Generar HTML
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Reporte de Inventario</title>
            <style>
                @page { margin: 18mm 14mm; }
                body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
                .header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
                .header .logo { width: 70px; height: 70px; object-fit: contain; }
                .header .meta { flex: 1; }
                .header h2 { margin: 0; font-size: 18px; }
                .header p { margin: 2px 0; font-size: 11px; }
                .title { text-align: center; font-size: 14px; font-weight: bold; margin: 6px 0 2px; }
                .subtitle { text-align: center; font-size: 10px; color: #555; margin: 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 14px; }
                th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                th { background-color: #1e88e5; color: white; font-size: 10.5px; }
                tbody tr:nth-child(even) { background: #fafafa; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                th:nth-child(4), td:nth-child(4) { width: 60px; text-align: center; }
                .total { background-color: #e3f2fd; font-weight: bold; }
                .alerta { background-color: #ffebee; }
                .footer { margin-top: 16px; font-size: 10.5px; }
                .note { background-color: #ffebee; padding: 6px 8px; display: inline-block; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="header">
                <?php if (!empty($tienda['logo'])): ?>
                <img class="logo" src="<?php echo e(getLogoUrl($tienda['logo'])); ?>" alt="Logo">
                <?php endif; ?>
                <div class="meta">
                    <h2><?php echo e($tienda['nombre_negocio']); ?></h2>
                    <p>RUC: <?php echo e($tienda['ruc']); ?></p>
                    <?php if (!empty($tienda['direccion'])): ?>
                    <p><?php echo e($tienda['direccion']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="title">REPORTE DE INVENTARIO</div>
            <p class="subtitle">Fecha de impresión: <?php echo e(date('d/m/Y H:i:s')); ?></p>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Categor?a</th>
                        <th>Producto</th>
                        <th>Unidad</th>
                        <th class="text-right">Stock Actual</th>
                        <th class="text-right">Stock Min.</th>
                        <th class="text-right">Costo Unit.</th>
                        <th class="text-right">Precio Venta</th>
                        <th class="text-right">Valor Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $index => $prod):
                        $alerta = $prod['stock_actual'] <= $prod['stock_minimo'] ? 'alerta' : '';
                        $valor_stock = $prod['stock_actual'] * $prod['precio_compra'];
                    ?>
                    <tr class="<?php echo $alerta; ?>">
                        <td class="text-center"><?php echo e($index + 1); ?></td>
                        <td><?php echo e($prod['categoria']); ?></td>
                        <td><?php echo e($prod['nombre']); ?></td>
                        <td class="text-center"><?php echo $prod['unidad'] ? e($prod['unidad']) : '-'; ?></td>
                        <td class="text-right"><?php echo e($prod['stock_actual']); ?></td>
                        <td class="text-right"><?php echo e($prod['stock_minimo']); ?></td>
                        <td class="text-right">S/. <?php echo e(number_format($prod['precio_compra'], 2)); ?></td>
                        <td class="text-right">S/. <?php echo e(number_format($prod['precio_venta'], 2)); ?></td>
                        <td class="text-right">S/. <?php echo e(number_format($valor_stock, 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total">
                        <td colspan="8">TOTAL</td>
                        <td class="text-right">S/. <?php echo e(number_format($total_valor_stock, 2)); ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="footer">
                <p><strong>Total de productos: <?php echo e($total_productos); ?></strong></p>
                <p class="note">
                    <strong>Nota:</strong> Los productos con fondo rojo están en stock mínimo o por debajo.
                </p>
            </div>

            <script>
                window.onload = function() { window.print(); }
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    public function pagos_pdf() {
        $fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-d');
        $fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

        // MULTI-TENANT: Obtener configuración de la tienda actual
        $tienda = getTiendaActual();

        if (!$tienda) {
            die('Error: No se encontró la tienda');
        }

        // MULTI-TENANT: Obtener pagos filtrados por tienda
        $sql = "
            SELECT p.*, v.nro_ticket, v.fecha_hora, u.nombre as cajero
            FROM pagos p
            INNER JOIN ventas v ON p.venta_id = v.id
            INNER JOIN usuarios u ON v.usuario_id = u.id
            WHERE DATE(v.fecha_hora) BETWEEN :desde AND :hasta";
        TenantHelper::addTenantScope($sql);
        $sql .= " ORDER BY v.fecha_hora DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
        $pagos = $stmt->fetchAll();

        // Calcular totales por método de pago
        $total_efectivo = 0;
        $total_yape = 0;
        $total_otro = 0;

        foreach ($pagos as $pago) {
            switch ($pago['medio']) {
                case 'EFECTIVO':
                    $total_efectivo += $pago['monto'];
                    break;
                case 'YAPE':
                    $total_yape += $pago['monto'];
                    break;
                default:
                    $total_otro += $pago['monto'];
            }
        }

        $total_general = $total_efectivo + $total_yape + $total_otro;

        // Generar HTML
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Reporte de Pagos</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #FF9800; color: white; }
                .text-right { text-align: right; }
                .header { text-align: center; margin-bottom: 20px; }
                .total { background-color: #f2f2f2; font-weight: bold; }
                .resumen { margin-top: 30px; padding: 15px; background-color: #f5f5f5; border: 1px solid #ddd; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2><?php echo e($tienda['nombre_negocio']); ?></h2>
                <p>RUC: <?php echo e($tienda['ruc']); ?></p>
                <h3>REPORTE DE PAGOS</h3>
                <p>Período: <?php echo e(date('d/m/Y', strtotime($fecha_desde))); ?> al <?php echo e(date('d/m/Y', strtotime($fecha_hasta))); ?></p>
                <p>Fecha de impresión: <?php echo e(date('d/m/Y H:i:s')); ?></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Fecha/Hora</th>
                        <th>Cajero</th>
                        <th>Método de Pago</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td><?php echo e($pago['nro_ticket']); ?></td>
                        <td><?php echo e(date('d/m/Y H:i', strtotime($pago['fecha_hora']))); ?></td>
                        <td><?php echo e($pago['cajero']); ?></td>
                        <td><?php echo e($pago['medio']); ?></td>
                        <td class="text-right">S/. <?php echo e(number_format($pago['monto'], 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="resumen">
                <h4>RESUMEN POR MÉTODO DE PAGO</h4>
                <table style="width: 50%;">
                    <tr>
                        <td><strong>Efectivo:</strong></td>
                        <td class="text-right">S/. <?php echo e(number_format($total_efectivo, 2)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Yape:</strong></td>
                        <td class="text-right">S/. <?php echo e(number_format($total_yape, 2)); ?></td>
                    </tr>
                    <?php if ($total_otro > 0): ?>
                    <tr>
                        <td><strong>Otros:</strong></td>
                        <td class="text-right">S/. <?php echo e(number_format($total_otro, 2)); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total">
                        <td><strong>TOTAL GENERAL:</strong></td>
                        <td class="text-right">S/. <?php echo e(number_format($total_general, 2)); ?></td>
                    </tr>
                </table>

                <p style="margin-top: 15px;"><strong>Total de transacciones: <?php echo e(count($pagos)); ?></strong></p>
            </div>

            <script>
                window.onload = function() { window.print(); }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// Manejar acciones
if (isset($_GET['action'])) {
    $controller = new ReporteController();
    $action = $_GET['action'];

    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
