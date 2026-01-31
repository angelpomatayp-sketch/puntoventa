<?php
require_once '../../config/config.php';
requireAdmin();

if (!isset($_GET['id'])) {
    redirect('/views/admin/cajas.php');
}

$caja_id = $_GET['id'];
$db = Database::getInstance()->getConnection();

// Obtener datos de la caja
$stmt = $db->prepare("
    SELECT c.*, u.nombre as usuario, v.nombre as validador
    FROM cajas c
    INNER JOIN usuarios u ON c.usuario_id = u.id
    LEFT JOIN usuarios v ON c.validado_por = v.id
    WHERE c.id = :id
");
$stmt->execute(['id' => $caja_id]);
$caja = $stmt->fetch();

if (!$caja) {
    redirect('/views/admin/cajas.php');
}

// Obtener ventas de esta caja
$stmt = $db->prepare("
    SELECT * FROM ventas
    WHERE caja_id = :caja_id
    ORDER BY fecha_hora ASC
");
$stmt->execute(['caja_id' => $caja_id]);
$ventas = $stmt->fetchAll();

// Obtener detalles de productos vendidos
$stmt = $db->prepare("
    SELECT p.nombre, SUM(dv.cantidad) as total_vendido, SUM(dv.subtotal) as total_monto
    FROM detalle_venta dv
    INNER JOIN productos p ON dv.producto_id = p.id
    INNER JOIN ventas v ON dv.venta_id = v.id
    WHERE v.caja_id = :caja_id AND v.estado = 'PAGADA'
    GROUP BY p.id, p.nombre
    ORDER BY total_vendido DESC
");
$stmt->execute(['caja_id' => $caja_id]);
$productos_vendidos = $stmt->fetchAll();

$page_title = 'Detalle de Caja #' . $caja_id;
include '../layouts/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <a href="<?php echo e(BASE_URL); ?>/views/admin/cajas.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-cash-stack"></i> Detalle de Caja #<?php echo e($caja['id']); ?></h4>
            </div>
            <div class="card-body">
                <!-- Información de la Caja -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Usuario:</th>
                                <td><?php echo e($caja['usuario']); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha Apertura:</th>
                                <td><?php echo e(formatDate($caja['fecha_apertura'])); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha Cierre:</th>
                                <td><?php echo e($caja['fecha_cierre'] ? formatDate($caja['fecha_cierre']) : '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Estado:</th>
                                <td>
                                    <?php if ($caja['estado'] === 'ABIERTA'): ?>
                                        <span class="badge bg-success">Abierta</span>
                                    <?php elseif ($caja['estado'] === 'CERRADA'): ?>
                                        <span class="badge bg-warning">Cerrada - Pendiente Validación</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Validada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Saldo Inicial:</th>
                                <td class="text-end"><?php echo e(formatMoney($caja['saldo_inicial'])); ?></td>
                            </tr>
                            <tr>
                                <th>Efectivo Esperado:</th>
                                <td class="text-end"><?php echo e(formatMoney($caja['saldo_inicial'] + $caja['efectivo_esperado'])); ?></td>
                            </tr>
                            <tr>
                                <th>Efectivo Contado:</th>
                                <td class="text-end"><?php echo e($caja['efectivo_contado'] ? formatMoney($caja['efectivo_contado']) : '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Yape Total:</th>
                                <td class="text-end"><?php echo e(formatMoney($caja['yape_total_registrado'])); ?></td>
                            </tr>
                            <tr>
                                <th>Diferencia:</th>
                                <td class="text-end">
                                    <?php
                                    $diff = $caja['diferencia_efectivo'];
                                    if ($diff > 0) {
                                        echo '<span class="text-success">+' . formatMoney($diff) . '</span>';
                                    } elseif ($diff < 0) {
                                        echo '<span class="text-danger">' . formatMoney($diff) . '</span>';
                                    } else {
                                        echo '<span class="text-muted">' . formatMoney(0) . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if ($caja['observacion']): ?>
                <div class="alert alert-info">
                    <strong>Observaciones:</strong> <?php echo e($caja['observacion']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Ventas de la Caja -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ventas Registradas (<?php echo e(count($ventas)); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Fecha/Hora</th>
                                <th>Subtotal</th>
                                <th>IGV</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th class="text-center">Ver</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td><?php echo e($venta['nro_ticket']); ?></td>
                                <td><?php echo e(formatDate($venta['fecha_hora'])); ?></td>
                                <td class="text-end"><?php echo e(formatMoney($venta['subtotal'])); ?></td>
                                <td class="text-end"><?php echo e(formatMoney($venta['igv'])); ?></td>
                                <td class="text-end"><strong><?php echo e(formatMoney($venta['total'])); ?></strong></td>
                                <td>
                                    <?php if ($venta['estado'] === 'PAGADA'): ?>
                                        <span class="badge bg-success">Pagada</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Anulada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo e(BASE_URL); ?>/views/pos/ticket.php?id=<?php echo e($venta['id']); ?>"
                                       class="btn btn-sm btn-info" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="2">TOTALES</th>
                                <th class="text-end"><?php echo e(formatMoney(array_sum(array_column($ventas, 'subtotal')))); ?></th>
                                <th class="text-end"><?php echo e(formatMoney(array_sum(array_column($ventas, 'igv')))); ?></th>
                                <th class="text-end"><?php echo e(formatMoney(array_sum(array_column($ventas, 'total')))); ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Productos Vendidos -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Productos Vendidos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad Vendida</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos_vendidos as $producto): ?>
                            <tr>
                                <td><?php echo e($producto['nombre']); ?></td>
                                <td class="text-center"><span class="badge bg-primary"><?php echo e($producto['total_vendido']); ?></span></td>
                                <td class="text-end"><?php echo e(formatMoney($producto['total_monto'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
