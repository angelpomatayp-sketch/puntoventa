<?php
require_once '../../config/config.php';
requireLogin();

$page_title = 'Mis Ventas';
include '../layouts/header.php';

$db = Database::getInstance()->getConnection();

// Obtener filtros (por defecto: hoy)
$filtro = $_GET['filtro'] ?? 'hoy';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Construir query según el filtro
$sql = "
    SELECT v.*, cl.nombre as cliente_nombre, c.id as caja_nro
    FROM ventas v
    INNER JOIN clientes cl ON v.cliente_id = cl.id
    INNER JOIN cajas c ON v.caja_id = c.id
    WHERE v.usuario_id = :usuario_id
";

$params = ['usuario_id' => $_SESSION['user_id']];

// Aplicar filtro de fecha
if ($filtro === 'hoy') {
    $sql .= " AND DATE(v.fecha_hora) = CURDATE()";
    $titulo_periodo = "del Día";
} elseif ($filtro === 'rango') {
    $sql .= " AND DATE(v.fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin";
    $params['fecha_inicio'] = $fecha_inicio;
    $params['fecha_fin'] = $fecha_fin;
    $titulo_periodo = "del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
} else { // todas
    // MULTI-TENANT: Filtrar por tienda
    if (!isSuperAdmin()) {
        $sql .= " AND v.tienda_id = " . getTiendaId();
    }
    $titulo_periodo = "- Todas las Ventas";
}

$sql .= " ORDER BY v.fecha_hora DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

// Calcular totales
$total_ventas = count($ventas);
$ventas_pagadas = array_filter($ventas, fn($v) => $v['estado'] === 'PAGADA');
$total_monto = array_sum(array_column($ventas_pagadas, 'total'));
$total_anuladas = $total_ventas - count($ventas_pagadas);
?>

<div class="row mb-3">
    <div class="col-12">
        <h2><i class="bi bi-receipt-cutoff"></i> Mis Ventas <?php echo e($titulo_periodo); ?></h2>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Filtrar por:</label>
                        <select class="form-select" name="filtro" id="filtroSelect">
                            <option value="hoy" <?php echo e($filtro === 'hoy' ? 'selected' : ''); ?>>Ventas de Hoy</option>
                            <option value="rango" <?php echo e($filtro === 'rango' ? 'selected' : ''); ?>>Rango de Fechas</option>
                            <option value="todas" <?php echo e($filtro === 'todas' ? 'selected' : ''); ?>>Todas las Ventas</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="fechaInicioDiv" style="<?php echo $filtro !== 'rango' ? 'display:none;' : ''; ?>">
                        <label class="form-label">Desde:</label>
                        <input type="date" class="form-control" name="fecha_inicio"
                               value="<?php echo e($fecha_inicio); ?>">
                    </div>

                    <div class="col-md-3" id="fechaFinDiv" style="<?php echo $filtro !== 'rango' ? 'display:none;' : ''; ?>">
                        <label class="form-label">Hasta:</label>
                        <input type="date" class="form-control" name="fecha_fin"
                               value="<?php echo e($fecha_fin); ?>">
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="<?php echo e(BASE_URL); ?>/views/cajero/ventas.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas del día -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-receipt"></i> Total Ventas</h6>
                <h3><?php echo e($total_ventas); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-check-circle"></i> Ventas Pagadas</h6>
                <h3><?php echo e(count($ventas_pagadas)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-dark text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-cash-stack"></i> Total del Día</h6>
                <h4><?php echo e(formatMoney($total_monto)); ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de ventas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Listado de Ventas (<?php echo e($total_ventas); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($ventas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Fecha/Hora</th>
                                <th>Cliente</th>
                                <th>Caja</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">IGV</th>
                                <th class="text-end">Total</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td><strong><?php echo e($venta['nro_ticket']); ?></strong></td>
                                <td>
                                    <?php
                                    if ($filtro === 'hoy') {
                                        echo date('H:i:s', strtotime($venta['fecha_hora']));
                                    } else {
                                        echo date('d/m/Y H:i', strtotime($venta['fecha_hora']));
                                    }
                                    ?>
                                </td>
                                <td><?php echo e($venta['cliente_nombre']); ?></td>
                                <td>#<?php echo e($venta['caja_nro']); ?></td>
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
                                    <button class="btn btn-sm btn-primary btn-ver-ticket"
                                            data-id="<?php echo e($venta['id']); ?>"
                                            data-ticket="<?php echo e($venta['nro_ticket']); ?>">
                                        <i class="bi bi-eye"></i> Ver Ticket
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="4">TOTALES</th>
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
                    <i class="bi bi-info-circle"></i>
                    <?php
                    if ($filtro === 'hoy') {
                        echo 'No has realizado ninguna venta hoy.';
                    } elseif ($filtro === 'rango') {
                        echo 'No hay ventas en el rango de fechas seleccionado.';
                    } else {
                        echo 'No tienes ninguna venta registrada.';
                    }
                    ?>
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

// Variable para almacenar el modal
let ticketModal = null;

$(document).ready(function() {
    // Inicializar el modal una sola vez
    ticketModal = new bootstrap.Modal(document.getElementById('modalTicket'));

    // Mostrar/ocultar campos de rango de fechas
    $('#filtroSelect').change(function() {
        if ($(this).val() === 'rango') {
            $('#fechaInicioDiv, #fechaFinDiv').show();
        } else {
            $('#fechaInicioDiv, #fechaFinDiv').hide();
        }
    });

    // Ver ticket
    $('.btn-ver-ticket').click(function() {
        const ventaId = $(this).data('id');
        const numeroTicket = $(this).data('ticket');
        console.log('Ver ticket - Venta ID:', ventaId, 'Ticket:', numeroTicket);
        mostrarTicket(ventaId, numeroTicket);
    });
});

function mostrarTicket(ventaId, numeroTicket) {
    // Resetear contenido del modal con spinner
    $('#ticketContent').html(`
        <div class=\"text-center py-5\">
            <div class=\"spinner-border text-primary\" role=\"status\">
                <span class=\"visually-hidden\">Cargando...</span>
            </div>
            <p class=\"mt-3\">Cargando ticket...</p>
        </div>
    `);

    // Mostrar el modal
    ticketModal.show();

    // Cargar el contenido del ticket
    console.log('Cargando ticket desde:', BASE_URL + '/controllers/VentaController.php?action=obtener_ticket&id=' + ventaId);

    $.ajax({
        url: BASE_URL + '/controllers/VentaController.php',
        method: 'GET',
        data: {
            action: 'obtener_ticket',
            id: ventaId
        },
        dataType: 'html',
        success: function(html) {
            console.log('Respuesta recibida, longitud:', html ? html.length : 0);
            if (html && html.trim() !== '') {
                $('#ticketContent').html(html);
            } else {
                $('#ticketContent').html('<div class=\"alert alert-warning\">No se pudo cargar el contenido del ticket</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar ticket:', status, error);
            console.error('Respuesta:', xhr.responseText);
            $('#ticketContent').html('<div class=\"alert alert-danger\">Error al cargar el ticket: ' + error + '</div>');
        }
    });

    // Botón de imprimir
    $('#btnImprimirTicket').off('click').on('click', function() {
        imprimirTicketVenta(numeroTicket);
    });
}

// Función para imprimir ticket
function imprimirTicketVenta(numeroTicket) {
    const contenido = $('#ticketContent').html();
    const ventana = window.open('', '_blank', 'width=350,height=600');

    if (!ventana) {
        alert('Por favor, permite las ventanas emergentes para imprimir el ticket.');
        return;
    }

    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket - ` + numeroTicket + `</title>
            <style>
                /* Reset y configuración base */
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                /* Configuración de página para impresión/PDF */
                @page {
                    size: 80mm auto; /* Ancho 80mm, alto automático */
                    margin: 2mm;
                }

                body {
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 12px;
                    line-height: 1.3;
                    width: 76mm; /* 80mm - 4mm de márgenes */
                    max-width: 76mm;
                    margin: 0 auto;
                    padding: 5px;
                    background: #fff;
                    color: #000;
                }

                /* Estilos del ticket */
                .ticket {
                    width: 100%;
                    max-width: 76mm;
                }

                .ticket img {
                    max-width: 50mm !important;
                    height: auto !important;
                }

                .ticket h4 {
                    font-size: 14px;
                    font-weight: bold;
                    margin: 5px 0;
                }

                .ticket p {
                    font-size: 11px;
                    margin: 2px 0;
                }

                .ticket table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 11px;
                }

                .ticket table th,
                .ticket table td {
                    padding: 2px 1px;
                    text-align: left;
                    vertical-align: top;
                }

                .ticket table th {
                    border-bottom: 1px dashed #000;
                    font-weight: bold;
                }

                .ticket .text-end,
                .ticket [style*=\"text-align: right\"] {
                    text-align: right !important;
                }

                .ticket .text-center,
                .ticket [style*=\"text-align: center\"] {
                    text-align: center !important;
                }

                /* Líneas divisorias */
                .ticket [style*=\"border-top: 2px dashed\"],
                .ticket [style*=\"border-bottom: 2px dashed\"] {
                    border-color: #000 !important;
                }

                /* Totales */
                .ticket strong {
                    font-weight: bold;
                }

                /* Botones de acción (no se imprimen) */
                .no-print {
                    margin-bottom: 10px;
                    text-align: center;
                }

                .btn {
                    display: inline-block;
                    padding: 8px 16px;
                    margin: 3px;
                    font-size: 12px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }

                .btn-primary {
                    background: #0d6efd;
                    color: white;
                }

                .btn-success {
                    background: #198754;
                    color: white;
                }

                .btn:hover {
                    opacity: 0.9;
                }

                /* Ocultar en impresión */
                @media print {
                    .no-print {
                        display: none !important;
                    }

                    body {
                        width: 76mm;
                        padding: 0;
                        margin: 0;
                    }

                    .ticket {
                        page-break-inside: avoid;
                    }
                }

                /* Vista previa en pantalla */
                @media screen {
                    body {
                        background: #f0f0f0;
                        padding: 10px;
                    }

                    .ticket {
                        background: white;
                        padding: 10px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        border-radius: 4px;
                    }
                }
            </style>
        </head>
        <body>
            <div class=\"no-print\">
                <button class=\"btn btn-primary\" onclick=\"window.print()\">
                    Imprimir / Guardar PDF
                </button>
                <button class=\"btn btn-success\" onclick=\"window.close()\">
                    Cerrar
                </button>
                <p style=\"font-size:11px; color:#666; margin-top:8px;\">
                    Para guardar como PDF: Imprimir → Destino: \"Guardar como PDF\"
                </p>
            </div>
            ` + contenido + `
        </body>
        </html>
    `);
    ventana.document.close();

    // Esperar a que cargue antes de imprimir
    ventana.onload = function() {
        setTimeout(function() {
            ventana.print();
        }, 300);
    };
}
</script>
";
include '../layouts/footer.php';
?>
