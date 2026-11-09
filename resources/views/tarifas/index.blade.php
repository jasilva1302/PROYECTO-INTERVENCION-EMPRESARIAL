@extends('layouts.app')

@section('title', 'Gestión de Tarifas')

@section('content')
<div class="container-fluid">
    <!-- Encabezado y Botones Principales -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0 text-gray-800">Gestión de Tarifas</h1>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-toolbar justify-content-end" role="toolbar">
                        @hasPermission(Config::get('permissions.tarifas.crear'))
                        <div class="btn-group me-2" role="group">
                            <button type="button" class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#createTarifaModal">
                                <i class="fas fa-plus-circle"></i> Nueva Tarifa
                            </button>
                        </div>
                        @endhasPermission
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-orange dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-file-import"></i> Importación
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importModal">
                                        <i class="fas fa-upload"></i> Importar Masivo
                                    </button>
                                </li>
                                <li>
                                    <a href="{{ route('tarifas.template') }}" class="dropdown-item">
                                        <i class="fas fa-download"></i> Descargar Plantilla
                                    </a>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                                        <i class="fas fa-sync"></i> Actualización Masiva
                                    </button>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="btn-group">
                            <a href="{{ route('tarifas.auditoria.index') }}" class="btn btn-primary">
                                <i class="fas fa-audit"></i> Auditoría de Tarifas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark">
            <h5 class="mb-0 text-white">Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <form id="searchForm" class="row g-3">
                @csrf
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="empresa" name="empresa" placeholder="Empresa">
                        <label for="empresa">Empresa</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="sede" name="sede" placeholder="Sede">
                        <label for="sede">Sede</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <select class="form-select" id="regimen" name="regimen">
                            <option value="">Todos</option>
                            <option value="EXCEPCION">Excepción</option>
                            <option value="CONTRIBUTIVO">Contributivo</option>
                            <option value="SUBSIDIADO">Subsidiado</option>
                            <option value="PARTICULAR">Particular</option>
                        </select>
                        <label for="regimen">Régimen</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="tercero" name="tercero" placeholder="Tercero">
                        <label for="tercero">Tercero</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="cups" name="cups" placeholder="CUPS">
                        <label for="cups">CUPS</label>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-center">
                    <button type="submit" class="btn btn-dark w-100" style="height: 58px;">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de tarifas -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-white">Listado de Tarifas</h5>
                    <small class="text-white-50">Total: <span id="totalRecords">{{ number_format($totalRecords) }}</span> registros</small>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <label class="text-white me-2 mb-0">Mostrar:</label>
                        <select id="perPage" class="form-select form-select-sm d-inline-block" style="width: auto;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">Todos</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th class="bg-dark text-white">EMPRESA</th>
                            <th class="bg-dark text-white">SEDE</th>
                            <th class="bg-dark text-white">RÉGIMEN</th>
                            <th class="bg-dark text-white">TERCERO</th>
                            <th class="bg-dark text-white">DESCRIPCIÓN DEL SERVICIO</th>
                            <th class="bg-dark text-white">CUPS</th>
                            <th class="bg-dark text-white">CUPS FACTURADOS</th>
                            <th class="bg-dark text-white text-end">TARIFAS DE MANUAL</th>
                            <th class="bg-dark text-white text-end">VALOR</th>
                            <th class="bg-dark text-white">ESTADO</th>
                            <th class="bg-dark text-white">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="tarifasTableBody">
                        @foreach($initialTarifas as $tarifa)
                        <tr>
                            <td>{{ $tarifa->empresa->razon_social ?? 'N/A' }}</td>
                            <td>{{ $tarifa->sede->sede ?? 'N/A' }}</td>
                            <td>{{ $tarifa->regimen ?? 'N/A' }}</td>
                            <td>{{ $tarifa->tercero ?? 'N/A' }}</td>
                            <td>{{ $tarifa->descripcion_del_servicio ?? 'N/A' }}</td>
                            <td>{{ $tarifa->cups ?? 'N/A' }}</td>
                            <td>{{ $tarifa->cups_facturados ?? 'N/A' }}</td>
                            <td class="text-end">{{ $tarifa->tarifas_de_manual ? number_format($tarifa->tarifas_de_manual, 0, ',', '.') : 'N/A' }}</td>
                            <td class="text-end">{{ number_format($tarifa->valor, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $tarifa->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $tarifa->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @hasPermission(Config::get('permissions.tarifas.editar'))
                                    <button class="btn btn-warning" onclick="quickUpdateTarifa({{ $tarifa->id }})" title="Actualización Rápida">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    @endhasPermission
                                    @hasPermission(Config::get('permissions.tarifas.ver'))
                                    <button class="btn btn-info" onclick="showHistorial({{ $tarifa->id }})" title="Ver Historial">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    @endhasPermission
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Mostrando <span id="fromRecord">{{ $initialTarifas->firstItem() ?? 0 }}</span> 
                    al <span id="toRecord">{{ $initialTarifas->lastItem() ?? 0 }}</span> 
                    de <span id="totalRecordsBottom">{{ number_format($totalRecords) }}</span> registros
                </div>
                <div class="pagination-container">
                    @if($initialTarifas->hasPages())
                        <ul class="pagination">
                            {{-- Botón Anterior --}}
                            <li class="page-item {{ $initialTarifas->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="#" data-page="{{ $initialTarifas->currentPage() - 1 }}" aria-label="Anterior">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>

                            {{-- Primera página --}}
                            @if($initialTarifas->currentPage() > 3)
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="1">1</a>
                                </li>
                                @if($initialTarifas->currentPage() > 4)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                            @endif

                            {{-- Páginas --}}
                            @foreach(range(max(1, $initialTarifas->currentPage() - 2), min($initialTarifas->lastPage(), $initialTarifas->currentPage() + 2)) as $page)
                                <li class="page-item {{ $page == $initialTarifas->currentPage() ? 'active' : '' }}">
                                    <a class="page-link" href="#" data-page="{{ $page }}">{{ $page }}</a>
                                </li>
                            @endforeach

                            {{-- Última página --}}
                            @if($initialTarifas->currentPage() < $initialTarifas->lastPage() - 2)
                                @if($initialTarifas->currentPage() < $initialTarifas->lastPage() - 3)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="{{ $initialTarifas->lastPage() }}">{{ $initialTarifas->lastPage() }}</a>
                                </li>
                            @endif

                            {{-- Botón Siguiente --}}
                            <li class="page-item {{ $initialTarifas->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="#" data-page="{{ $initialTarifas->currentPage() + 1 }}" aria-label="Siguiente">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('tarifas.modals.create')
@include('tarifas.modals.import')
@include('tarifas.modals.bulk-update')

@foreach($initialTarifas as $tarifa)
    @include('tarifas.modals.quick-update', ['tarifa' => $tarifa])
    @include('tarifas.modals.historial', ['tarifa' => $tarifa])
@endforeach

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        @if(session('report_file'))
        <div class="mt-2">
            <a href="{{ route('tarifas.download-report', ['filename' => session('report_file')]) }}" 
               class="btn btn-outline-danger btn-sm">
                <i class="fas fa-download"></i> Descargar Reporte de Errores
            </a>
        </div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tarifas/tarifas.css') }}">
<style>
    .table th {
        white-space: nowrap;
        background-color: #2d3748 !important;
        color: white !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem;
        vertical-align: middle;
        border: none;
    }

    .table td {
        vertical-align: middle;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }

    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .pagination {
        margin: 0;
    }

    .pagination .page-link {
        padding: 0.5rem 0.75rem;
        margin: 0 2px;
        color: #2d3748;
        background-color: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.25rem;
    }

    .pagination .page-item.active .page-link {
        background-color: #2d3748;
        border-color: #2d3748;
        color: white;
    }

    .form-floating > .form-control,
    .form-floating > .form-select {
        height: calc(3.5rem + 2px);
        line-height: 1.25;
    }

    .form-floating > label {
        padding: 1rem 0.75rem;
    }

    .btn-dark {
        background-color: #2d3748;
        border-color: #2d3748;
    }

    .btn-dark:hover {
        background-color: #1a202c;
        border-color: #1a202c;
    }

    .card {
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
    }

    .card-header {
        padding: 1rem;
        margin-bottom: 0;
        border-bottom: 1px solid rgba(0,0,0,0.125);
    }

    .badge {
        padding: 0.5em 0.75em;
        font-weight: 500;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let perPage = {{ request('per_page', 10) }};
    let currentPage = {{ request('page', 1) }};

    // Función para cargar las tarifas
    function loadTarifas(searchParams = {}, page = 1) {
        const tableBody = document.getElementById('tarifasTableBody');
        const fromRecord = document.getElementById('fromRecord');
        const toRecord = document.getElementById('toRecord');
        const totalRecords = document.getElementById('totalRecords');
        const totalRecordsBottom = document.getElementById('totalRecordsBottom');
        
        // Mostrar indicador de carga
        tableBody.innerHTML = '<tr><td colspan="11" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Cargando...</td></tr>';
        
        // Preparar parámetros
        const params = new URLSearchParams({
            ...searchParams,
            page: page,
            per_page: perPage
        });

        // Realizar la petición AJAX
        fetch(`{{ route('tarifas.index') }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la tabla
                tableBody.innerHTML = '';
                data.data.forEach(tarifa => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${tarifa.empresa?.razon_social || 'N/A'}</td>
                        <td>${tarifa.sede?.sede || 'N/A'}</td>
                        <td>${tarifa.regimen || 'N/A'}</td>
                        <td>${tarifa.tercero || 'N/A'}</td>
                        <td>${tarifa.descripcion_del_servicio || 'N/A'}</td>
                        <td>${tarifa.cups || 'N/A'}</td>
                        <td>${tarifa.cups_facturados || 'N/A'}</td>
                        <td class="text-end">${tarifa.tarifas_de_manual ? new Intl.NumberFormat('es-CO').format(tarifa.tarifas_de_manual) : 'N/A'}</td>
                        <td class="text-end">${new Intl.NumberFormat('es-CO').format(tarifa.valor)}</td>
                        <td>
                            <span class="badge ${tarifa.is_active ? 'bg-success' : 'bg-danger'}">
                                ${tarifa.is_active ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                @hasPermission(Config::get('permissions.tarifas.editar'))
                                <button class="btn btn-warning" onclick="quickUpdateTarifa(${tarifa.id})" title="Actualización Rápida">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                @endhasPermission
                                @hasPermission(Config::get('permissions.tarifas.ver'))
                                <button class="btn btn-info" onclick="showHistorial(${tarifa.id})" title="Ver Historial">
                                    <i class="fas fa-history"></i>
                                </button>
                                @endhasPermission
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(row);

                    // Crear los modales dinámicamente para cada tarifa
                    createModals(tarifa);
                });

                // Actualizar información de paginación
                fromRecord.textContent = data.pagination.from || 0;
                toRecord.textContent = data.pagination.to || 0;
                totalRecords.textContent = new Intl.NumberFormat().format(data.pagination.total);
                totalRecordsBottom.textContent = new Intl.NumberFormat().format(data.pagination.total);

                // Actualizar paginación
                updatePagination(data.pagination);
            } else {
                tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Error al cargar los datos</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Error al cargar los datos</td></tr>';
        });
    }

    // Función para actualizar la paginación
    function updatePagination(pagination) {
        const container = document.querySelector('.pagination-container');
        if (!container) return;

        let html = '<ul class="pagination">';
        
        // Botón anterior
        html += `
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}" aria-label="Anterior">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;

        // Primera página
        if (pagination.current_page > 3) {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>
                ${pagination.current_page > 4 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
            `;
        }

        // Páginas
        for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.last_page, pagination.current_page + 2); i++) {
            html += `
                <li class="page-item ${pagination.current_page === i ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        // Última página
        if (pagination.current_page < pagination.last_page - 2) {
            html += `
                ${pagination.current_page < pagination.last_page - 3 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a>
                </li>
            `;
        }

        // Botón siguiente
        html += `
            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}" aria-label="Siguiente">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;

        html += '</ul>';
        container.innerHTML = html;

        // Agregar eventos a los enlaces de paginación
        container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (page && !isNaN(page)) {
                    currentPage = parseInt(page);
                    loadTarifas(getSearchParams(), currentPage);
                }
            });
        });
    }

    // Función para obtener los parámetros de búsqueda
    function getSearchParams() {
        const form = document.getElementById('searchForm');
        const formData = new FormData(form);
        const params = {};
        for (let [key, value] of formData.entries()) {
            if (value) params[key] = value;
        }
        return params;
    }

    // Manejar cambio en registros por página
    const perPageSelect = document.getElementById('perPage');
    if (perPageSelect) {
        perPageSelect.value = perPage;
        perPageSelect.addEventListener('change', function() {
            perPage = this.value;
            currentPage = 1;
            loadTarifas(getSearchParams(), 1);
        });
    }

    // Manejar búsquedas
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            loadTarifas(getSearchParams(), 1);
        });
    }

    // Función para crear los modales dinámicamente
    function createModals(tarifa) {
        // Crear el modal de actualización rápida
        if (!document.getElementById(`quickUpdateModal${tarifa.id}`)) {
            const quickUpdateModal = `
                <div class="modal fade" id="quickUpdateModal${tarifa.id}" tabindex="-1" aria-labelledby="quickUpdateModalLabel${tarifa.id}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="quickUpdateModalLabel${tarifa.id}">Actualización Rápida de Tarifa</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="quickUpdateForm${tarifa.id}" onsubmit="submitQuickUpdate(event, ${tarifa.id})">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Tarifa Actual</label>
                                        <p class="form-control-plaintext">${new Intl.NumberFormat('es-CO').format(tarifa.valor)}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_tarifa${tarifa.id}" class="form-label">Nueva Tarifa</label>
                                        <input type="number" step="0.01" class="form-control" id="new_tarifa${tarifa.id}" name="new_tarifa" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="year${tarifa.id}" class="form-label">Año</label>
                                        <input type="number" class="form-control" id="year${tarifa.id}" name="year" value="${new Date().getFullYear()}" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Actualizar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', quickUpdateModal);
        }

        // Crear el modal de historial
        if (!document.getElementById(`historialModal${tarifa.id}`)) {
            const historialModal = `
                <div class="modal fade" id="historialModal${tarifa.id}" tabindex="-1" aria-labelledby="historialModalLabel${tarifa.id}" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="historialModalLabel${tarifa.id}">Historial de Tarifa</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive" id="historialTableContainer${tarifa.id}">
                                    <div class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando historial...
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', historialModal);
        }
    }

    // Función para mostrar el modal de actualización rápida
    window.quickUpdateTarifa = function(id) {
        const modal = new bootstrap.Modal(document.getElementById(`quickUpdateModal${id}`));
        modal.show();
    }

    // Función para mostrar el historial
    window.showHistorial = function(id) {
        const modal = new bootstrap.Modal(document.getElementById(`historialModal${id}`));
        modal.show();
        
        // Cargar el historial
        fetch(`/tarifas/${id}/historial`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById(`historialTableContainer${id}`);
                if (data.length > 0) {
                    let html = `
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Valor Anterior</th>
                                    <th>Valor Nuevo</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    data.forEach(item => {
                        html += `
                            <tr>
                                <td>${new Date(item.created_at).toLocaleString()}</td>
                                <td>${item.user.name}</td>
                                <td>${new Intl.NumberFormat('es-CO').format(item.valor_anterior)}</td>
                                <td>${new Intl.NumberFormat('es-CO').format(item.valor_nuevo)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                            </tbody>
                        </table>
                    `;
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p class="text-center">No hay registros de cambios para esta tarifa.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById(`historialTableContainer${id}`).innerHTML = 
                    '<div class="alert alert-danger">Error al cargar el historial</div>';
            });
    }

    // Función para manejar la actualización rápida
    window.submitQuickUpdate = function(event, id) {
        event.preventDefault();
        const form = document.getElementById(`quickUpdateForm${id}`);
        const formData = new FormData(form);

        fetch(`/tarifas/${id}/quick-update`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                new_tarifa: formData.get('new_tarifa'),
                year: formData.get('year')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar el modal
                bootstrap.Modal.getInstance(document.getElementById(`quickUpdateModal${id}`)).hide();
                // Recargar los datos
                loadTarifas(getSearchParams(), currentPage);
                // Mostrar mensaje de éxito
                alert('Tarifa actualizada correctamente');
            } else {
                alert('Error al actualizar la tarifa: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar la tarifa');
        });
    }
});
</script>
@endpush