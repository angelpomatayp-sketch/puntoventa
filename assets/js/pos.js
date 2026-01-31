/**
 * Sistema POS Fast Food
 * JavaScript para Punto de Venta
 */

let carrito = [];
let clienteSeleccionadoId = 1; // Se inicializa en ready()
const IGV_PORCENTAJE = 18;
const PRODUCTOS_POR_PAGINA = 10;
let currentProductoPage = 1;

// Al cargar la página
$(document).ready(function() {
    // MULTI-TENANT: Inicializar con el ID del cliente ANÓNIMO de la tienda
    clienteSeleccionadoId = typeof CLIENTE_ANONIMO_ID !== 'undefined' ? CLIENTE_ANONIMO_ID : 1;

    inicializarPOS();
    inicializarClientes();
});

function inicializarPOS() {
    // Búsqueda de productos
    $('#buscarProducto').on('keyup', filtrarProductos);
    $('#filtroCategoria').on('change', filtrarProductos);

    // Agregar productos al carrito
    $(document).on('click', '.producto-item', function() {
        agregarAlCarrito(
            $(this).data('id'),
            $(this).data('nombre'),
            $(this).data('precio'),
            $(this).data('stock')
        );
    });

    // Limpiar carrito
    $('#btnLimpiarCarrito').click(function() {
        mostrarConfirmacion(
            'Limpiar Carrito',
            '¿Está seguro de que desea limpiar todos los productos del carrito?',
            function() {
                carrito = [];
                actualizarCarrito();
            }
        );
    });

    // Cambio de medio de pago
    $('input[name="medioPago"]').change(function() {
        const medio = $(this).val();
        if (medio === 'EFECTIVO') {
            $('#camposEfectivo').show();
            $('#camposYape').hide();
        } else {
            $('#camposEfectivo').hide();
            $('#camposYape').show();
        }
    });

    // Calcular vuelto al ingresar monto recibido
    $('#montoRecibido').on('keyup', calcularVuelto);

    // Aplicar/quitar IGV
    $('#aplicarIGV').change(function() {
        actualizarTotales();
    });

    // Finalizar venta
    $('#btnFinalizarVenta').click(finalizarVenta);

    // Eliminar item del carrito
    $(document).on('click', '.btn-eliminar-item', function() {
        const index = $(this).data('index');
        carrito.splice(index, 1);
        actualizarCarrito();
    });

    // Cambiar cantidad
    $(document).on('change', '.input-cantidad', function() {
        const index = $(this).data('index');
        const nuevaCantidad = parseInt($(this).val());
        const stock = carrito[index].stock;

        if (nuevaCantidad > stock) {
            mostrarAlerta('Stock Insuficiente', `Solo hay ${stock} unidades disponibles de este producto.`, 'warning');
            $(this).val(carrito[index].cantidad);
            return;
        }

        if (nuevaCantidad < 1) {
            $(this).val(1);
            return;
        }

        carrito[index].cantidad = nuevaCantidad;
        carrito[index].subtotal = nuevaCantidad * carrito[index].precio;
        actualizarCarrito();
    });

    // Render inicial de productos con paginacion
    renderProductos();
}

function inicializarClientes() {
    let timeoutBusqueda = null;

    // Búsqueda de clientes con debounce
    $('#buscarClienteInput').on('keyup', function() {
        const query = $(this).val().trim();

        clearTimeout(timeoutBusqueda);

        if (query.length < 2) {
            $('#resultadosCliente').hide().empty();
            return;
        }

        timeoutBusqueda = setTimeout(function() {
            buscarClientes(query);
        }, 300);
    });

    // Ocultar resultados al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#buscarClienteInput, #resultadosCliente').length) {
            $('#resultadosCliente').hide();
        }
    });

    // Seleccionar ANÓNIMO
    $('#btnSeleccionarAnonimo').click(function() {
        const anonimoId = typeof CLIENTE_ANONIMO_ID !== 'undefined' ? CLIENTE_ANONIMO_ID : 1;
        seleccionarCliente(anonimoId, 'ANÓNIMO', '00000000');
        $('#buscarClienteInput').val('');
        $('#resultadosCliente').hide().empty();
    });

    // Validar solo números en DNI y teléfono del modal
    $('#nuevoClienteDni, #nuevoClienteTelefono').on('keypress', function(e) {
        if (e.which < 48 || e.which > 57) {
            e.preventDefault();
        }
    });

    // Formulario de nuevo cliente
    $('#formNuevoCliente').submit(function(e) {
        e.preventDefault();
        crearNuevoCliente();
    });

    // Resetear modal al cerrar
    $('#modalNuevoCliente').on('hidden.bs.modal', function() {
        $('#formNuevoCliente')[0].reset();
    });
}

function buscarClientes(query) {
    $.ajax({
        url: BASE_URL + '/controllers/ClienteController.php?action=buscar',
        method: 'GET',
        data: { q: query },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            console.log('Success:', response.success);
            console.log('Clientes:', response.clientes);

            if (response.success && response.clientes && response.clientes.length > 0) {
                mostrarResultadosClientes(response.clientes);
            } else {
                $('#resultadosCliente').html('<div class="list-group-item text-muted">No se encontraron clientes</div>').show();
            }
        },
        error: function(xhr, status, error) {
            console.log('Error AJAX:', error);
            console.log('Status:', status);
            console.log('Response:', xhr.responseText);
            $('#resultadosCliente').html('<div class="list-group-item text-danger">Error al buscar clientes</div>').show();
        }
    });
}

function mostrarResultadosClientes(clientes) {
    const $resultados = $('#resultadosCliente');
    $resultados.empty();

    clientes.forEach(cliente => {
        const item = $(`
            <a href="#" class="list-group-item list-group-item-action cliente-resultado"
               data-id="${cliente.id}"
               data-nombre="${cliente.nombre}"
               data-dni="${cliente.dni_ruc}">
                <div><strong>${cliente.nombre}</strong></div>
                <div><small class="text-muted">DNI/RUC: ${cliente.dni_ruc} | Tel: ${cliente.telefono}</small></div>
            </a>
        `);

        item.click(function(e) {
            e.preventDefault();
            seleccionarCliente($(this).data('id'), $(this).data('nombre'), $(this).data('dni'));
            $('#buscarClienteInput').val('');
            $resultados.hide();
        });

        $resultados.append(item);
    });

    $resultados.show();
}

function seleccionarCliente(id, nombre, dni) {
    clienteSeleccionadoId = id;
    $('#clienteId').val(id);
    $('#clienteNombre').text(nombre);

    const anonimoId = typeof CLIENTE_ANONIMO_ID !== 'undefined' ? CLIENTE_ANONIMO_ID : 1;
    if (id === anonimoId) {
        $('#clienteDni').text('');
    } else {
        $('#clienteDni').text('(DNI/RUC: ' + dni + ')');
    }

    $('#clienteSeleccionado').show();
}

function crearNuevoCliente() {
    const nombre = $('#nuevoClienteNombre').val().trim();
    const dni = $('#nuevoClienteDni').val().trim();
    const telefono = $('#nuevoClienteTelefono').val().trim();

    if (!nombre || !dni || !telefono) {
        mostrarAlerta('Campos Incompletos', 'Todos los campos son obligatorios para registrar un cliente.', 'warning');
        return;
    }

    $.ajax({
        url: BASE_URL + '/controllers/ClienteController.php?action=crear_ajax',
        method: 'POST',
        data: {
            nombre: nombre,
            dni_ruc: dni,
            telefono: telefono
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#modalNuevoCliente').modal('hide');
                seleccionarCliente(response.cliente_id, nombre, dni);
                mostrarAlerta('Cliente Registrado', `El cliente <strong>${nombre}</strong> ha sido registrado exitosamente y seleccionado para la venta.`, 'success');
            } else {
                mostrarAlerta('Error al Registrar', response.message, 'error');
            }
        },
        error: function() {
            mostrarAlerta('Error del Sistema', 'Ocurrió un error al registrar el cliente. Por favor intente nuevamente.', 'error');
        }
    });
}

function filtrarProductos() {
    currentProductoPage = 1;
    renderProductos();
}

function getFilteredProductos() {
    const busqueda = $('#buscarProducto').val().toLowerCase();
    const categoria = $('#filtroCategoria').val();

    return $('.producto-item').filter(function() {
        const nombre = ($(this).data('nombre') || '').toString().toLowerCase();
        const categoriaId = ($(this).data('categoria') || '').toString();

        if (busqueda && nombre.indexOf(busqueda) === -1) {
            return false;
        }
        if (categoria && categoriaId !== categoria) {
            return false;
        }
        return true;
    }).toArray();
}

function renderProductos(page = 1) {
    const $todos = $('.producto-item');
    const filtrados = getFilteredProductos();
    const totalPages = Math.max(1, Math.ceil(filtrados.length / PRODUCTOS_POR_PAGINA));
    currentProductoPage = Math.min(Math.max(1, page), totalPages);

    $todos.hide();

    const start = (currentProductoPage - 1) * PRODUCTOS_POR_PAGINA;
    const end = start + PRODUCTOS_POR_PAGINA;
    filtrados.slice(start, end).forEach(item => {
        $(item).show();
    });

    renderProductosPaginacion(totalPages, filtrados.length);
}

function renderProductosPaginacion(totalPages, totalItems) {
    let $pagination = $('#paginacionProductos');
    if (!$pagination.length) {
        $pagination = $('<div id="paginacionProductos" class="d-flex justify-content-center mt-2"></div>');
        $('.productos-list').after($pagination);
    }

    $pagination.empty();

    if (totalItems <= PRODUCTOS_POR_PAGINA) {
        $pagination.hide();
        return;
    }

    $pagination.show();

    const $ul = $('<ul class="pagination pagination-sm mb-0"></ul>');

    const addItem = (label, page, disabled = false, active = false) => {
        const $li = $('<li class="page-item"></li>');
        if (disabled) $li.addClass('disabled');
        if (active) $li.addClass('active');
        const $a = $('<a class="page-link" href="#"></a>').text(label);
        $a.on('click', function(e) {
            e.preventDefault();
            if (disabled || page === currentProductoPage) return;
            renderProductos(page);
        });
        $li.append($a);
        $ul.append($li);
    };

    addItem('«', currentProductoPage - 1, currentProductoPage === 1);

    const maxPagesToShow = 5;
    let start = Math.max(1, currentProductoPage - 2);
    let end = Math.min(totalPages, start + maxPagesToShow - 1);
    if (end - start < maxPagesToShow - 1) {
        start = Math.max(1, end - maxPagesToShow + 1);
    }

    for (let i = start; i <= end; i++) {
        addItem(String(i), i, false, i === currentProductoPage);
    }

    addItem('»', currentProductoPage + 1, currentProductoPage === totalPages);

    $pagination.append($ul);
}

function agregarAlCarrito(id, nombre, precio, stock) {
    // Verificar si ya existe en el carrito
    const existente = carrito.find(item => item.id === id);

    if (existente) {
        if (existente.cantidad >= stock) {
            mostrarAlerta('Stock Insuficiente', `El producto <strong>${nombre}</strong> solo tiene ${stock} unidades disponibles.`, 'warning');
            return;
        }
        existente.cantidad++;
        existente.subtotal = existente.cantidad * existente.precio;
    } else {
        carrito.push({
            id: id,
            nombre: nombre,
            precio: parseFloat(precio),
            cantidad: 1,
            stock: stock,
            subtotal: parseFloat(precio)
        });
    }

    actualizarCarrito();
}

function actualizarCarrito() {
    const $listaCarrito = $('#listaCarrito');
    $listaCarrito.empty();

    if (carrito.length === 0) {
        $('#carritoVacio').show();
        $('#carritoItems').hide();
        return;
    }

    $('#carritoVacio').hide();
    $('#carritoItems').show();

    carrito.forEach((item, index) => {
        const html = `
            <div class="carrito-item">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <strong>${item.nombre}</strong><br>
                        <small class="text-muted">${formatMoney(item.precio)} c/u</small>
                    </div>
                    <button class="btn btn-sm btn-danger btn-eliminar-item" data-index="${index}">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <input type="number" class="form-control form-control-sm input-cantidad"
                           style="width: 80px;" min="1" max="${item.stock}"
                           value="${item.cantidad}" data-index="${index}">
                    <strong>${formatMoney(item.subtotal)}</strong>
                </div>
            </div>
        `;
        $listaCarrito.append(html);
    });

    actualizarTotales();
}

function actualizarTotales() {
    const aplicarIGV = $('#aplicarIGV').is(':checked');
    let subtotal = 0;

    carrito.forEach(item => {
        subtotal += item.subtotal;
    });

    let igv = 0;
    let total = subtotal;

    if (aplicarIGV) {
        igv = subtotal * (IGV_PORCENTAJE / 100);
        total = subtotal + igv;

        $('#subtotalRow').show();
        $('#igvRow').show();
        $('#subtotalAmount').text(formatMoney(subtotal));
        $('#igvAmount').text(formatMoney(igv));
    } else {
        $('#subtotalRow').hide();
        $('#igvRow').hide();
    }

    $('#totalAmount').text(formatMoney(total));

    // Actualizar monto mínimo recibido si es efectivo
    if ($('#pagoEfectivo').is(':checked')) {
        $('#montoRecibido').attr('min', total.toFixed(2));
    }
}

function calcularVuelto() {
    const total = parseFloat($('#totalAmount').text().replace('S/. ', ''));
    const recibido = parseFloat($('#montoRecibido').val()) || 0;

    if (recibido >= total) {
        const vuelto = recibido - total;
        $('#vueltoAmount').text(formatMoney(vuelto));
        $('#vueltoInfo').show();
    } else {
        $('#vueltoInfo').hide();
    }
}

function finalizarVenta() {
    if (carrito.length === 0) {
        mostrarAlerta('Carrito Vacío', 'El carrito está vacío. Agregue productos para continuar.', 'warning');
        return;
    }

    const medioPago = $('input[name="medioPago"]:checked').val();
    const aplicarIGV = $('#aplicarIGV').is(':checked');
    const total = parseFloat($('#totalAmount').text().replace('S/. ', ''));

    // Validaciones según medio de pago
    if (medioPago === 'EFECTIVO') {
        const recibido = parseFloat($('#montoRecibido').val());
        if (!recibido || recibido < total) {
            mostrarAlerta('Monto Insuficiente', 'El monto recibido debe ser mayor o igual al total de la venta.', 'warning');
            $('#montoRecibido').focus();
            return;
        }
    } else if (medioPago === 'YAPE') {
        const refOperacion = $('#refOperacion').val().trim();
        if (!refOperacion) {
            mostrarAlerta('Número de Operación Requerido', 'Debe ingresar el número de operación de Yape.', 'warning');
            $('#refOperacion').focus();
            return;
        }
    }

    // Mostrar modal de confirmación
    mostrarConfirmacionVenta(total, medioPago, aplicarIGV);
}

function mostrarConfirmacionVenta(total, medioPago, aplicarIGV) {
    $('#montoConfirmar').text(formatMoney(total));

    let detalle = `<strong>Medio de pago:</strong> ${medioPago}<br>`;
    detalle += `<strong>Productos:</strong> ${carrito.length} item(s)<br>`;
    if (aplicarIGV) {
        detalle += `<small class="text-muted">(Incluye IGV 18%)</small>`;
    }
    $('#detalleVenta').html(detalle);

    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarVenta'));
    modal.show();

    // Evento para confirmar
    $('#btnConfirmarVentaFinal').off('click').on('click', function() {
        modal.hide();
        procesarVenta();
    });
}

function procesarVenta() {
    const medioPago = $('input[name="medioPago"]:checked').val();
    const aplicarIGV = $('#aplicarIGV').is(':checked');

    // Deshabilitar botón
    $('#btnFinalizarVenta').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Procesando...');

    // Preparar datos
    const venta = {
        cliente_id: clienteSeleccionadoId,
        carrito: carrito,
        medio_pago: medioPago,
        aplicar_igv: aplicarIGV,
        monto_recibido: medioPago === 'EFECTIVO' ? $('#montoRecibido').val() : null,
        ref_operacion: medioPago === 'YAPE' ? $('#refOperacion').val() : null
    };

    // Enviar al servidor
    $.ajax({
        url: BASE_URL + '/controllers/VentaController.php?action=procesar',
        method: 'POST',
        data: { venta: JSON.stringify(venta) },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Mostrar modal con ticket
                mostrarTicketEnModal(response.venta_id, response.ticket);

                // Limpiar carrito y cliente
                carrito = [];
                actualizarCarrito();
                $('#montoRecibido').val('');
                $('#refOperacion').val('');
                $('#aplicarIGV').prop('checked', false);
                // Resetear cliente a ANÓNIMO de la tienda
                const anonimoId = typeof CLIENTE_ANONIMO_ID !== 'undefined' ? CLIENTE_ANONIMO_ID : 1;
                seleccionarCliente(anonimoId, 'ANÓNIMO', '00000000');
                $('#clienteSeleccionado').hide();
            } else {
                mostrarAlerta('Error en la Venta', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            mostrarAlerta('Error del Sistema', 'Ocurrió un error al procesar la venta. Por favor intente nuevamente.', 'error');
        },
        complete: function() {
            $('#btnFinalizarVenta').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Finalizar Venta');
        }
    });
}

function mostrarTicketEnModal(ventaId, numeroTicket) {
    // Mostrar el modal
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
            $('#ticketContent').html('<div class="alert alert-danger">Error al cargar el ticket</div>');
        }
    });

    // Botón de imprimir
    $('#btnImprimirTicket').off('click').on('click', function() {
        imprimirTicket(numeroTicket);
    });
}

// Función global para imprimir ticket
function imprimirTicket(numeroTicket) {
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
            <title>Ticket - ${numeroTicket}</title>
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
                    width: 76mm;
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
                .ticket [style*="text-align: right"] {
                    text-align: right !important;
                }

                .ticket .text-center,
                .ticket [style*="text-align: center"] {
                    text-align: center !important;
                }

                .ticket strong {
                    font-weight: bold;
                }

                /* Botones de acción */
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
            <div class="no-print">
                <button class="btn btn-primary" onclick="window.print()">
                    Imprimir / Guardar PDF
                </button>
                <button class="btn btn-success" onclick="window.close()">
                    Cerrar
                </button>
                <p style="font-size:11px; color:#666; margin-top:8px;">
                    Para PDF: Imprimir → Destino: "Guardar como PDF"
                </p>
            </div>
            ${contenido}
        </body>
        </html>
    `);
    ventana.document.close();

    ventana.onload = function() {
        setTimeout(function() {
            ventana.print();
        }, 300);
    };
}

function mostrarAlerta(titulo, mensaje, tipo = 'info') {
    const $header = $('#alertaHeader');
    const $titulo = $('#alertaTitulo');
    const $mensaje = $('#alertaMensaje');

    // Limpiar clases anteriores
    $header.removeClass('bg-success bg-danger bg-warning bg-info text-white');

    // Iconos según tipo
    let icono = '';
    switch(tipo) {
        case 'success':
            $header.addClass('bg-success text-white');
            icono = '<i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>';
            break;
        case 'error':
        case 'danger':
            $header.addClass('bg-danger text-white');
            icono = '<i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>';
            break;
        case 'warning':
            $header.addClass('bg-warning');
            icono = '<i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>';
            break;
        default:
            $header.addClass('bg-info text-white');
            icono = '<i class="bi bi-info-circle-fill text-info" style="font-size: 3rem;"></i>';
    }

    $titulo.html(`<i class="bi bi-bell"></i> ${titulo}`);
    $mensaje.html(`${icono}<p class="mt-3 mb-0">${mensaje}</p>`);

    const modal = new bootstrap.Modal(document.getElementById('modalAlerta'));
    modal.show();
}

function mostrarConfirmacion(titulo, mensaje, callback) {
    $('#confirmacionTitulo').html(`<i class="bi bi-question-circle"></i> ${titulo}`);
    $('#confirmacionMensaje').html(mensaje);

    const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
    modal.show();

    // Evento para confirmar
    $('#btnConfirmarAccion').off('click').on('click', function() {
        modal.hide();
        if (typeof callback === 'function') {
            callback();
        }
    });
}

// Función auxiliar para formatear dinero
function formatMoney(amount) {
    return 'S/. ' + parseFloat(amount).toFixed(2);
}

// Variable global BASE_URL para AJAX
const BASE_URL = window.location.origin + '/fastfood';
