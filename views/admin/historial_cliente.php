<?php
require_once '../../config/config.php';
requireLogin();

if (!isset($_GET['id'])) {
    redirect('/views/admin/clientes.php');
}

$cliente_id = $_GET['id'];
$db = Database::getInstance()->getConnection();

// Obtener datos del cliente
$stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id");
$stmt->execute(['id' => $cliente_id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    redirect('/views/admin/clientes.php');
}

// Obtener ventas del cliente
$stmt = $db->prepare("
    SELECT v.*, u.nombre as cajero
    FROM ventas v
    INNER JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.cliente_id = :cliente_id
    ORDER BY v.fecha_hora DESC
");
$stmt->execute(['cliente_id' => $cliente_id]);
$ventas = $stmt->fetchAll();

// Calcular estadísticas
$total_ventas = count($ventas);
$ventas_pagadas = array_filter($ventas, fn($v) => $v['estado'] === 'PAGADA');
$total_monto = array_sum(array_column($ventas_pagadas, 'total'));
$total_anuladas = $total_ventas - count($ventas_pagadas);

// Obtener productos más comprados
$stmt = $db->prepare("
    SELECT p.nombre, SUM(dv.cantidad) as total_comprado, SUM(dv.subtotal) as total_gastado
    FROM detalle_venta dv
    INNER JOIN productos p ON dv.producto_id = p.id
    INNER JOIN ventas v ON dv.venta_id = v.id
    WHERE v.cliente_id = :cliente_id AND v.estado = 'PAGADA'
    GROUP BY p.id, p.nombre
    ORDER BY total_comprado DESC
    LIMIT 5
");
$stmt->execute(['cliente_id' => $cliente_id]);
$productos_favoritos = $stmt->fetchAll();

$page_title = 'Historial de ' . $cliente['nombre'];
include '../layouts/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <a href="<?php echo e(BASE_URL); ?>/views/admin/clientes.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Clientes
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person"></i> Información del Cliente</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Nombre:</th>
                                <td><strong><?php echo e($cliente['nombre']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>DNI/RUC:</th>
                                <td><?php echo e($cliente['dni_ruc']); ?></td>
                            </tr>
                            <tr>
                                <th>Teléfono:</th>
                                <td><?php echo e($cliente['telefono']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Estado:</th>
                                <td>
                                    <?php if ($cliente['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Registrado:</th>
                                <td><?php echo e(formatDate($cliente['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actualización:</th>
                                <td><?php echo e(formatDate($cliente['updated_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-receipt"></i> Total Ventas</h6>
                <h3><?php echo e($total_ventas); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-check-circle"></i> Pagadas</h6>
                <h3><?php echo e(count($ventas_pagadas)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-x-circle"></i> Anuladas</h6>
                <h3><?php echo e($total_anuladas); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-cash-stack"></i> Total Gastado</h6>
                <h4><?php echo e(formatMoney($total_monto)); ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Productos Favoritos -->
<?php if (count($productos_favoritos) > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-star"></i> Productos Más Comprados</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad Comprada</th>
                                <th class="text-end">Total Gastado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos_favoritos as $producto): ?>
                            <tr>
                                <td><?php echo e($producto['nombre']); ?></td>
                                <td class="text-center"><span class="badge bg-primary"><?php echo e($producto['total_comprado']); ?></span></td>
                                <td class="text-end"><?php echo e(formatMoney($producto['total_gastado'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Historial de Ventas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Compras (<?php echo e($total_ventas); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($ventas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Fecha/Hora</th>
                                <th>Cajero</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">IGV</th>
                                <th class="text-end">Total</th>
                                <th>Estado</th>
                                <th class="text-center">Ver</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td><?php echo e($venta['nro_ticket']); ?></td>
                                <td><?php echo e(formatDate($venta['fecha_hora'])); ?></td>
                                <td><?php echo e($venta['cajero']); ?></td>
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
                                <th colspan="3">TOTALES</th>
                                <th class="text-end"><?php echo e(formatMoney(array_sum(array_column($ventas_pagadas, 'subtotal')))); ?></th>
                                <th class="text-end"><?php echo e(formatMoney(array_sum(array_column($ventas_pagadas, 'igv')))); ?></th>
                                <th class="text-end"><?php echo e(formatMoney($total_monto)); ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> Este cliente aún no ha realizado ninguna compra.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
