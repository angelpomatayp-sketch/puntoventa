<?php
require_once '../../config/config.php';
requireAdmin();

$page_title = 'Reporte de Ventas';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Procesar filtros
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-d');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

// Obtener ventas filtradas (MULTI-TENANT)
$sql = "
    SELECT v.*, u.nombre as cajero
    FROM ventas v
    INNER JOIN usuarios u ON v.usuario_id = u.id
    WHERE DATE(v.fecha_hora) BETWEEN :desde AND :hasta
    AND v.estado = 'PAGADA'
";

// Filtrar por tienda
if (!isSuperAdmin()) {
    $sql .= " AND v.tienda_id = " . getTiendaId();
}

$sql .= " ORDER BY v.fecha_hora DESC";

$stmt = $db->prepare($sql);
$stmt->execute(['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
$ventas = $stmt->fetchAll();

// Calcular totales
$total_ventas = count($ventas);
$total_monto = array_sum(array_column($ventas, 'total'));
$total_con_igv = 0;
$total_sin_igv = 0;

foreach ($ventas as $venta) {
    if ($venta['aplica_igv']) {
        $total_con_igv += $venta['total'];
    } else {
        $total_sin_igv += $venta['total'];
    }
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-file-earmark-bar-graph"></i> Reporte de Ventas</h2>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="desde" class="form-label">Fecha Desde</label>
                        <input type="date" class="form-control" id="desde" name="desde" value="<?php echo e($fecha_desde); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="hasta" class="form-label">Fecha Hasta</label>
                        <input type="date" class="form-control" id="hasta" name="hasta" value="<?php echo e($fecha_hasta); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Total Ventas</h6>
                <h3><?php echo e($total_ventas); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Monto Total</h6>
                <h3><?php echo e(formatMoney($total_monto)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Con IGV</h6>
                <h3><?php echo e(formatMoney($total_con_igv)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning">
            <div class="card-body text-center">
                <h6>Sin IGV</h6>
                <h3><?php echo e(formatMoney($total_sin_igv)); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Botón Exportar PDF -->
<div class="row mb-3">
    <div class="col-12 text-end">
        <a href="<?php echo e(BASE_URL); ?>/controllers/ReporteController.php?action=ventas_pdf&desde=<?php echo e($fecha_desde); ?>&hasta=<?php echo e($fecha_hasta); ?>"
           class="btn btn-danger" target="_blank">
            <i class="bi bi-file-pdf"></i> Exportar a PDF
        </a>
    </div>
</div>

<!-- Tabla de Ventas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Detalle de Ventas</h5>
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
                                <th>Subtotal</th>
                                <th>IGV</th>
                                <th>Total</th>
                                <th class="text-center">Ver</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td><strong><?php echo e($venta['nro_ticket']); ?></strong></td>
                                <td><?php echo e(formatDate($venta['fecha_hora'])); ?></td>
                                <td><?php echo e($venta['cajero']); ?></td>
                                <td class="text-end"><?php echo e(formatMoney($venta['subtotal'])); ?></td>
                                <td class="text-end"><?php echo e(formatMoney($venta['igv'])); ?></td>
                                <td class="text-end"><strong><?php echo e(formatMoney($venta['total'])); ?></strong></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary btn-ver-ticket"
                                            data-id="<?php echo e($venta['id']); ?>"
                                            data-ticket="<?php echo e($venta['nro_ticket']); ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="3">TOTALES</th>
                                <th class="text-end"><?php echo e(formatMoney(array_sum(array_column($ventas, 'subtotal')))); ?></th>
                                <th class="text-end"><?php echo e(formatMoney(array_sum(array_column($ventas, 'igv')))); ?></th>
                                <th class="text-end"><?php echo e(formatMoney($total_monto)); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> No se encontraron ventas en el rango seleccionado.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ticket -->
<div class="modal fade" id="modalTicket" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt"></i> Ticket de Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ticketContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3">Cargando ticket...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnImprimirTicket">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$custom_js = "
<script>
const BASE_URL = '" . BASE_URL . "';

$(document).ready(function() {
    // Ver ticket
    $('.btn-ver-ticket').click(function() {
        const ventaId = $(this).data('id');
        const numeroTicket = $(this).data('ticket');
        mostrarTicket(ventaId, numeroTicket);
    });

    function mostrarTicket(ventaId, numeroTicket) {
        const modal = new bootstrap.Modal(document.getElementById('modalTicket'));
        modal.show();

        // Cargar el contenido del ticket
        $.ajax({
            url: BASE_URL + '/controllers/VentaController.php?action=obtener_ticket',
            method: 'GET',
            data: { id: ventaId },
            success: function(html) {
                $('#ticketContent').html(html);
            },
            error: function() {
                $('#ticketContent').html('<div class=\"alert alert-danger\">Error al cargar el ticket</div>');
            }
        });

        // Botón de imprimir
        $('#btnImprimirTicket').off('click').on('click', function() {
            const contenido = $('#ticketContent').html();
            const ventana = window.open('', '_blank');
            ventana.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Ticket - ` + numeroTicket + `</title>
                    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
                    <link rel=\"stylesheet\" href=\"` + BASE_URL + `/assets/css/style.css\">
                    <style>
                        @media print {
                            .no-print { display: none !important; }
                        }
                    </style>
                </head>
                <body onload=\"window.print(); window.close();\">
                    ` + contenido + `
                </body>
                </html>
            `);
            ventana.document.close();
        });
    }
});
</script>
";
include '../layouts/footer.php';
?>
