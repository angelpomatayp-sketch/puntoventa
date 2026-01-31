/**
 * Sistema POS Fast Food
 * JavaScript Principal
 */

// CSRF token helper
function getCsrfToken() {
    if (window.CSRF_TOKEN) {
        return window.CSRF_TOKEN;
    }
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// Inyectar CSRF en formularios POST y en AJAX si existe jQuery
document.addEventListener('DOMContentLoaded', function() {
    const token = getCsrfToken();
    if (!token) return;

    const forms = document.querySelectorAll('form[method="POST"], form[method="post"]');
    forms.forEach(form => {
        if (!form.querySelector('input[name="csrf_token"]')) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = token;
            form.appendChild(input);
        }
    });

    if (window.jQuery && window.jQuery.ajaxSetup) {
        window.jQuery.ajaxSetup({
            headers: { 'X-CSRF-Token': token }
        });
    }
});

// Función para formatear moneda
function formatMoney(amount) {
    return 'S/. ' + parseFloat(amount).toFixed(2);
}

// Función para mostrar alertas
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    const container = document.querySelector('.container-fluid') || document.querySelector('.container');
    if (container) {
        const alertDiv = document.createElement('div');
        alertDiv.innerHTML = alertHtml;
        container.insertBefore(alertDiv.firstElementChild, container.firstChild);

        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
}

// Función para confirmar eliminación
function confirmDelete(message = '¿Está seguro de eliminar este registro?') {
    return confirm(message);
}

// Función para validar campos numéricos
function validateNumber(input, min = 0, max = null) {
    let value = parseFloat(input.value);

    if (isNaN(value) || value < min) {
        input.value = min;
        return false;
    }

    if (max !== null && value > max) {
        input.value = max;
        return false;
    }

    return true;
}

// Función para imprimir
function printTicket() {
    window.print();
}

// Auto-focus en campos de búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('input[type="search"], input.search');
    if (searchInputs.length > 0) {
        searchInputs[0].focus();
    }
});

// Validación de formularios
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Función para actualizar reloj en tiempo real
function updateClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('es-PE');
    const dateString = now.toLocaleDateString('es-PE');

    const clockElements = document.querySelectorAll('.live-clock');
    clockElements.forEach(el => {
        el.textContent = `${dateString} ${timeString}`;
    });
}

// Actualizar reloj cada segundo
setInterval(updateClock, 1000);

// Función para calcular IGV
function calcularIGV(monto, incluido = false, porcentaje = 18) {
    if (incluido) {
        // El monto ya incluye IGV
        const base = monto / (1 + porcentaje / 100);
        const igv = monto - base;
        return {
            base: base.toFixed(2),
            igv: igv.toFixed(2),
            total: monto.toFixed(2)
        };
    } else {
        // El monto no incluye IGV
        const igv = monto * (porcentaje / 100);
        const total = monto + igv;
        return {
            base: monto.toFixed(2),
            igv: igv.toFixed(2),
            total: total.toFixed(2)
        };
    }
}

// Prevenir envío doble de formularios
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                setTimeout(() => {
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
});

// Función para buscar en tablas
// Paginacion y busqueda en tablas
function initTablePagination(inputId, tableId, perPage = 10) {
    const table = document.getElementById(tableId);
    if (!table) return;
    if (table.dataset.paginated === 'true') return;
    table.dataset.paginated = 'true';

    const tbody = table.tBodies[0];
    if (!tbody) return;

    const allRows = Array.from(tbody.rows);
    let currentPage = 1;

    const input = inputId ? document.getElementById(inputId) : null;
    const wrapper = table.closest('.table-responsive') || table.parentElement;
    let pagination = wrapper ? wrapper.nextElementSibling : null;

    if (!pagination || !pagination.classList.contains('table-pagination')) {
        pagination = document.createElement('div');
        pagination.className = 'table-pagination d-flex justify-content-center mt-2';
        if (wrapper && wrapper.parentNode) {
            wrapper.parentNode.insertBefore(pagination, wrapper.nextSibling);
        }
    }

    function getFilteredRows() {
        if (!input || !input.value.trim()) {
            return allRows;
        }
        const filter = input.value.toLowerCase();
        return allRows.filter(row => {
            const cells = row.getElementsByTagName('td');
            for (let i = 0; i < cells.length; i++) {
                const textValue = cells[i].textContent || cells[i].innerText;
                if (textValue.toLowerCase().indexOf(filter) > -1) {
                    return true;
                }
            }
            return false;
        });
    }

    function renderPagination(totalPages) {
        pagination.innerHTML = '';
        if (totalPages <= 1) {
            pagination.style.display = 'none';
            return;
        }
        pagination.style.display = 'flex';

        const ul = document.createElement('ul');
        ul.className = 'pagination pagination-sm mb-0';

        const createItem = (label, page, disabled = false, active = false) => {
            const li = document.createElement('li');
            li.className = 'page-item';
            if (disabled) li.classList.add('disabled');
            if (active) li.classList.add('active');

            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled || page === currentPage) return;
                renderPage(page);
            });
            li.appendChild(a);
            ul.appendChild(li);
        };

        createItem('«', currentPage - 1, currentPage === 1);

        const maxPagesToShow = 5;
        let start = Math.max(1, currentPage - 2);
        let end = Math.min(totalPages, start + maxPagesToShow - 1);
        if (end - start < maxPagesToShow - 1) {
            start = Math.max(1, end - maxPagesToShow + 1);
        }

        for (let i = start; i <= end; i++) {
            createItem(String(i), i, false, i === currentPage);
        }

        createItem('»', currentPage + 1, currentPage === totalPages);

        pagination.appendChild(ul);
    }

    function renderPage(page = 1) {
        const filtered = getFilteredRows();
        const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        currentPage = Math.min(Math.max(1, page), totalPages);

        allRows.forEach(row => {
            row.style.display = 'none';
        });

        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        filtered.slice(start, end).forEach(row => {
            row.style.display = '';
        });

        renderPagination(totalPages);
    }

    if (input) {
        input.addEventListener('input', function() {
            renderPage(1);
        });
    }

    renderPage(1);
}

// Funcion para buscar en tablas (mantener compatibilidad)
function filterTable(inputId, tableId) {
    initTablePagination(inputId, tableId, 10);
}

// Auto inicializar tablas con data-paginate
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('table[data-paginate="true"]').forEach(table => {
        const inputId = table.getAttribute('data-filter-input');
        const perPage = parseInt(table.getAttribute('data-per-page'), 10) || 10;
        if (table.id) {
            initTablePagination(inputId, table.id, perPage);
        }
    });
});
