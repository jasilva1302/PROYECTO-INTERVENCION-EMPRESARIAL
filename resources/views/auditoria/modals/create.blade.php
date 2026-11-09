<!-- Modal para crear auditoría -->
<div class="modal fade" id="createAuditModal" tabindex="-1" aria-labelledby="createAuditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createAuditModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Crear Nueva Auditoría
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="auditForm" method="POST" action="{{ route('auditoria.store') }}">
                    @csrf
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select select2" id="modal_sede_id" name="sede_id" required>
                                    <option value="">Seleccione una sede</option>
                                    @foreach($sedes as $sede)
                                        @php
                                            $sedeData = is_string($sede->sede) ? $sede->sede : json_decode($sede->sede)->sede;
                                        @endphp
                                        <option value="{{ $sede->id }}">{{ $sedeData }}</option>
                                    @endforeach
                                </select>
                                <label for="modal_sede_id">Sede *</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select" id="modal_mes" required>
                                    <option value="">Seleccione mes</option>
                                    @php
                                        $meses = [
                                            1 => 'Enero',
                                            2 => 'Febrero',
                                            3 => 'Marzo',
                                            4 => 'Abril',
                                            5 => 'Mayo',
                                            6 => 'Junio',
                                            7 => 'Julio',
                                            8 => 'Agosto',
                                            9 => 'Septiembre',
                                            10 => 'Octubre',
                                            11 => 'Noviembre',
                                            12 => 'Diciembre'
                                        ];
                                    @endphp
                                    @foreach($meses as $num => $nombre)
                                        <option value="{{ $num }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <label for="modal_mes">Mes *</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select" id="modal_anio" required>
                                    <option value="">Seleccione año</option>
                                    @php
                                        $currentYear = (int)date('Y');
                                        $startYear = $currentYear - 2;
                                    @endphp
                                    @for($year = $currentYear; $year >= $startYear; $year--)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endfor
                                </select>
                                <label for="modal_anio">Año *</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="link" name="link" required>
                                <label for="link">Ruta/Link *</label>
                            </div>
                        </div>
                        <!-- Campo oculto para el month -->
                        <input type="hidden" name="month" id="month_hidden">
                    </div>

                    <div class="row mb-4">
                        <div class="col-12 text-center">
                            <button type="button" id="btnBuscarFacturas" class="btn btn-matte btn-lg px-5">
                                <i class="fas fa-search me-2"></i>Buscar Facturas
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle modern-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </div>
                                    </th>
                                    <th>Factura</th>
                                    <th>Sede</th>
                                    <th>Aseguradora</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Valor</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="facturasTableBody">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="alert alert-info m-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Use el botón "Buscar Facturas" para cargar las facturas disponibles
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="submit" form="auditForm" class="btn btn-success" id="btnCrearAuditoria" disabled>
                    <i class="fas fa-plus-circle me-2"></i>Crear Auditoría
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
:root {
    --color-matte: #2d3748;
    --color-matte-light: #4a5568;
    --color-matte-dark: #1a202c;
    --color-gray-50: #f8fafc;
    --color-gray-100: #f1f5f9;
    --color-gray-200: #e2e8f0;
    --color-gray-300: #cbd5e1;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --transition: all 0.2s ease-in-out;
}

/* Modal styles */
.modal-content {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.modal-header {
    background-color: var(--color-matte);
    border-bottom: none;
    padding: 1.5rem;
}

.modal-title {
    color: white;
    font-weight: 600;
    font-size: 1.25rem;
}

.modal-body {
    padding: 1.5rem;
    background-color: var(--color-gray-50);
}

.modal-footer {
    border-top: 1px solid var(--color-gray-200);
    background-color: white;
    padding: 1.25rem 1.5rem;
}

/* Form styles */
.form-floating > .form-control,
.form-floating > .form-select {
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    transition: var(--transition);
}

.form-floating > .form-control:focus,
.form-floating > .form-select:focus {
    border-color: var(--color-matte);
    box-shadow: 0 0 0 0.25rem rgba(45, 55, 72, 0.25);
}

.form-floating > label {
    color: var(--color-matte);
}

.form-floating > label::after {
    content: " *";
    color: #dc2626;
}

/* Button styles */
.btn-matte {
    background-color: var(--color-matte);
    border: none;
    color: white;
    font-weight: 500;
    transition: var(--transition);
}

.btn-matte:hover {
    background-color: var(--color-matte-dark);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Table styles */
.modern-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

.modern-table thead th {
    background-color: var(--color-matte) !important;
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    padding: 1rem;
    border: none;
}

.modern-table tbody tr {
    transition: var(--transition);
    background-color: white;
}

.modern-table tbody tr:hover {
    background-color: var(--color-gray-100);
}

.modern-table td {
    padding: 1rem;
    border-top: 1px solid var(--color-gray-200);
    color: var(--color-matte);
}

/* Select2 customization */
.select2-container--bootstrap-5 {
    width: 100% !important;
}

.select2-container--bootstrap-5 .select2-selection {
    height: calc(3.5rem + 2px) !important;
    padding: 1rem 0.75rem;
    font-size: 1rem;
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    transition: var(--transition);
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    padding: 0;
    line-height: 1.5;
    color: var(--color-matte);
}

.select2-container--bootstrap-5 .select2-dropdown {
    border-color: var(--color-gray-200);
    border-radius: 8px;
    box-shadow: var(--shadow-md);
}

.select2-container--bootstrap-5 .select2-results__options {
    max-height: 200px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--color-matte) var(--color-gray-100);
}

.select2-container--bootstrap-5 .select2-results__options::-webkit-scrollbar {
    width: 6px;
}

.select2-container--bootstrap-5 .select2-results__options::-webkit-scrollbar-track {
    background: var(--color-gray-100);
    border-radius: 3px;
}

.select2-container--bootstrap-5 .select2-results__options::-webkit-scrollbar-thumb {
    background: var(--color-matte);
    border-radius: 3px;
}

.select2-container--bootstrap-5 .select2-results__option {
    padding: 0.75rem 1rem;
    transition: var(--transition);
}

.select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: var(--color-matte) !important;
    color: white;
}

.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    border-color: var(--color-matte);
    box-shadow: 0 0 0 0.25rem rgba(45, 55, 72, 0.25);
}

/* Alert customization */
.alert-info {
    background-color: #e1effe;
    border: none;
    border-radius: 8px;
    color: #1e429f;
}

/* Form check customization */
.form-check-input:checked {
    background-color: var(--color-matte);
    border-color: var(--color-matte);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .modern-table {
        font-size: 0.875rem;
    }
    
    .btn-matte {
        width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    const formAudit = $('#auditForm');
    const btnBuscar = $('#btnBuscarFacturas');
    const btnCrear = $('#btnCrearAuditoria');
    const selectSede = $('#modal_sede_id');
    const selectMes = $('#modal_mes');
    const selectAnio = $('#modal_anio');
    const monthHidden = $('#month_hidden');
    const tableBody = $('#facturasTableBody');
    
    // Inicializar Select2 si está disponible
    if($.fn.select2) {
        selectSede.select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione una sede',
            dropdownParent: $('#createAuditModal'),
            width: '100%',
            templateResult: function(data) {
                if (!data.id) return data.text;
                return $('<span class="select2-sede-option">' + data.text + '</span>');
            },
            templateSelection: function(data) {
                if (!data.id) return data.text;
                return $('<span class="select2-sede-selected">' + data.text + '</span>');
            }
        }).on('select2:select', function(e) {
            actualizarBotonCrear();
        });
    }

    // Función para actualizar el campo month oculto
    function actualizarCampoMonth() {
        const mes = selectMes.val();
        const anio = selectAnio.val();
        if (mes && anio) {
            const mesFormateado = mes.toString().padStart(2, '0');
            monthHidden.val(`${anio}-${mesFormateado}`);
        }
    }

    // Eventos para actualizar el campo month
    selectMes.on('change', actualizarCampoMonth);
    selectAnio.on('change', actualizarCampoMonth);

    // Función para actualizar estado del botón crear
    function actualizarBotonCrear() {
        const sedeSeleccionada = selectSede.val();
        const mesSeleccionado = selectMes.val();
        const anioSeleccionado = selectAnio.val();
        const rutaIngresada = $('#link').val().trim();
        const facturasSeleccionadas = $('.factura-checkbox:checked').length;

        btnCrear.prop('disabled', !(
            sedeSeleccionada && 
            mesSeleccionado && 
            anioSeleccionado && 
            rutaIngresada && 
            facturasSeleccionadas > 0
        ));
    }

    // Función para actualizar la tabla de facturas
    function actualizarTablaFacturas(response) {
        // Limpiar tabla
        tableBody.empty();

        if (response.facturas && response.facturas.length > 0) {
            // Agregar facturas a la tabla
            response.facturas.forEach(function(factura) {
                const fecha = new Date(factura.fecha_documento).toLocaleDateString('es-ES');
                const row = `
                    <tr>
                        <td class="text-center">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input factura-checkbox" 
                                       name="facturas[]" value="${factura.id}">
                            </div>
                        </td>
                        <td>${factura.factura}</td>
                        <td>${factura.sede.sede}</td>
                        <td>${factura.aseguradora}</td>
                        <td>${fecha}</td>
                        <td class="text-end">${new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP' }).format(factura.valor)}</td>
                        <td>${factura.estado}</td>
                    </tr>
                `;
                tableBody.append(row);
            });

            // Inicializar eventos de checkbox
            $('.factura-checkbox').on('change', actualizarBotonCrear);
            $('#selectAll').on('change', function() {
                $('.factura-checkbox').prop('checked', $(this).is(':checked'));
                actualizarBotonCrear();
            });
        } else {
            tableBody.html(`
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="alert alert-info m-0">
                            <i class="fas fa-info-circle me-2"></i>
                            ${response.message}
                        </div>
                    </td>
                </tr>
            `);
        }
    }

    // Evento de clic en el botón de búsqueda
    btnBuscar.on('click', function() {
        // Validar que se hayan seleccionado todos los campos
        const sedeId = selectSede.val();
        const mes = selectMes.val();
        const anio = selectAnio.val();

        if (!sedeId || !mes || !anio) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor, seleccione sede, mes y año antes de buscar facturas.'
            });
            return;
        }

        // Actualizar el campo month antes de la búsqueda
        actualizarCampoMonth();

        // Mostrar estado de carga
        btnBuscar.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>Buscando...');

        // Realizar petición AJAX
        $.ajax({
            url: '{{ route("auditoria.facturas") }}',
            type: 'GET',
            data: { 
                sede_id: sedeId,
                month: monthHidden.val()
            },
            success: function(response) {
                actualizarTablaFacturas(response);
            },
            error: function(xhr) {
                let errorMessage = 'Error al buscar facturas';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });

                tableBody.html(`
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="alert alert-danger m-0">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${errorMessage}
                            </div>
                        </td>
                    </tr>
                `);
            },
            complete: function() {
                // Restaurar estado del botón
                btnBuscar.prop('disabled', false)
                    .html('<i class="fas fa-search me-2"></i>Buscar Facturas');
            }
        });
    });

    // Eventos para actualizar estado del botón crear
    selectSede.on('change', actualizarBotonCrear);
    selectMes.on('change', actualizarBotonCrear);
    selectAnio.on('change', actualizarBotonCrear);
    $('#link').on('input', actualizarBotonCrear);

    // Manejar el envío del formulario
    formAudit.on('submit', function(e) {
        e.preventDefault();
        
        // Asegurarse de que el campo month esté actualizado
        actualizarCampoMonth();
        
        // Enviar el formulario usando AJAX
        $.ajax({
            url: formAudit.attr('action'),
            type: 'POST',
            data: formAudit.serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: response.message,
                        timer: 1500
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error al crear la auditoría';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    });

    // Resetear formulario al cerrar el modal
    $('#createAuditModal').on('hidden.bs.modal', function() {
        formAudit[0].reset();
        selectSede.val('').trigger('change');
        monthHidden.val('');
        tableBody.html(`
            <tr>
                <td colspan="7" class="text-center">
                    <div class="alert alert-info m-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Use el botón "Buscar Facturas" para cargar las facturas disponibles
                    </div>
                </td>
            </tr>
        `);
        btnCrear.prop('disabled', true);
    });
});
</script>
@endpush 