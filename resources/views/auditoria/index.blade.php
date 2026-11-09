@extends('layouts.app')

@section('title', 'Auditorías')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0 text-gray-800">Auditorías</h1>
                @hasPermission('Crear Auditorías')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAuditModal">
                    <i class="fas fa-plus"></i> Nueva Auditoría
                </button>
                @endhasPermission
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert custom-alert-success alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fs-4 me-2"></i>
            <div>
                <strong>¡Éxito!</strong> {{ session('success') }}
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert custom-alert-danger alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle fs-4 me-2"></i>
            <div>
                <strong>¡Error!</strong> {{ session('error') }}
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header" style="background-color: #2d3748;">
            <h5 class="mb-0 text-white">Listado de Auditorías</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0 modern-table">
                    <thead>
                        <tr>
                            <th style="background-color: #2d3748;" class="text-white px-4 py-3">Sede</th>
                            <th style="background-color: #2d3748;" class="text-white px-4 py-3">Período</th>
                            <th style="background-color: #2d3748;" class="text-white px-4 py-3">Usuario</th>
                            <th style="background-color: #2d3748;" class="text-white px-4 py-3 text-center">Estado</th>
                            <th style="background-color: #2d3748;" class="text-white px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($audits as $audit)
                        <tr>
                            <td class="px-4">
                                @php
                                    $sedeData = is_string($audit->sede->sede) ? json_decode($audit->sede->sede) : null;
                                    $sedeName = $sedeData ? $sedeData->sede : ($audit->sede->sede ?? 'N/A');
                                @endphp
                                {{ $sedeName }}
                            </td>
                            <td class="px-4">{{ $monthNames[$audit->month - 1] }} {{ $audit->year }}</td>
                            <td class="px-4">{{ $audit->createdBy->name }}</td>
                            <td class="px-4 text-center">
                                @if($audit->completed)
                                <span class="badge" style="background-color: #059669; color: white;">Completada</span>
                                @elseif($audit->started_at)
                                <span class="badge" style="background-color: #f97316; color: white;">En progreso</span>
                                @else
                                <span class="badge" style="background-color: #dc2626; color: white;">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 text-center">
                                <div class="btn-group">
                                    @hasPermission('Ver Auditorías')
                                    <a href="{{ route('auditoria.show', $audit) }}" class="btn btn-sm btn-info" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endhasPermission

                                    @if(!$audit->completed)
                                        @hasPermission('Editar Auditorías')
                                            @if($audit->created_by == auth()->id())
                                                @if($audit->started_at)
                                                <a href="{{ route('auditoria.start', $audit) }}" class="btn btn-sm btn-warning" title="Continuar auditoría">
                                                    <i class="fas fa-play"></i> Continuar
                                                </a>
                                                @else
                                                <a href="{{ route('auditoria.start', $audit) }}" class="btn btn-sm btn-success" title="Iniciar auditoría">
                                                    <i class="fas fa-play"></i> Iniciar
                                                </a>
                                                @endif
                                            @else
                                                <button class="btn btn-sm btn-secondary" disabled title="Solo el creador puede realizar esta auditoría">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            @endif
                                        @endhasPermission
                                    @endif

                                    @if($audit->completed)
                                        @hasPermission('Ver Auditorías')
                                        <a href="{{ route('auditoria.download-report', $audit) }}" class="btn btn-sm btn-indigo" title="Descargar reporte">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        @endhasPermission
                                    @endif

                                    @if(!$audit->completed && !$audit->started_at && $audit->created_by == auth()->id())
                                        @hasPermission('Eliminar Auditorías')
                                        <form action="{{ route('auditoria.destroy', $audit) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar auditoría"
                                                    onclick="return confirm('¿Está seguro de eliminar esta auditoría?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endhasPermission
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($audits->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $audits->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@hasPermission('Crear Auditorías')
@include('auditoria.modals.create')
@endhasPermission

@endsection

@section('css')
<style>
/* Variables */
:root {
    --color-primary: #f97316;
    --color-primary-dark: #ea580c;
    --color-primary-light: #fb923c;
    --color-matte: #2d3748;
    --color-matte-light: #4a5568;
    --color-matte-dark: #1a202c;
    --color-gray-50: #f8fafc;
    --color-gray-100: #f1f5f9;
    --color-gray-200: #e2e8f0;
    --color-gray-300: #cbd5e1;
    --color-gray-400: #94a3b8;
    --color-gray-500: #64748b;
    --color-gray-600: #475569;
    --color-gray-700: #334155;
    --color-gray-800: #1e293b;
    --color-gray-900: #0f172a;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --transition: all 0.2s ease-in-out;
}

/* Estilos base */
.text-gray-800 {
    color: var(--color-gray-800);
}

.bg-primary {
    background-color: var(--color-primary) !important;
}

.bg-gradient {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%) !important;
}

.bg-light-gradient {
    background: linear-gradient(to bottom, var(--color-gray-50) 0%, var(--color-gray-100) 100%);
}

.btn-gradient {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    border: none;
    color: white;
    padding: 0.6rem 1.2rem;
    font-weight: 500;
    letter-spacing: 0.5px;
    transition: var(--transition);
}

.btn-gradient:hover {
    background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);
    transform: translateY(-1px);
    color: white;
    box-shadow: var(--shadow-md);
}

/* Inputs modernos */
.modern-input,
.modern-select {
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: var(--transition);
    background-color: white;
}

.modern-input:focus,
.modern-select:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(113, 128, 150, 0.2);
}

/* Tabla moderna */
.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.modern-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

.modern-table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    padding: 1rem;
    border: none;
    position: relative;
    background-color: var(--color-matte);
    color: white;
}

.modern-table th:not(:last-child)::after {
    content: '';
    position: absolute;
    right: 0;
    top: 25%;
    height: 50%;
    width: 1px;
    background-color: rgba(255, 255, 255, 0.1);
}

.modern-table td {
    padding: 1rem;
    border-top: 1px solid var(--color-gray-200);
    color: var(--color-gray-700);
    vertical-align: middle;
}

.modern-table tbody tr {
    transition: var(--transition);
    background-color: white;
}

.modern-table tbody tr:hover {
    background-color: var(--color-gray-50);
}

/* Badges personalizados */
.badge {
    padding: 0.65rem 1rem;
    font-weight: 500;
    letter-spacing: 0.3px;
    border-radius: 8px;
    font-size: 0.85rem;
    display: inline-block;
    text-align: center;
    min-width: 100px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.bg-success-custom {
    background-color: #00a762;
    color: white;
    box-shadow: 0 2px 4px rgba(0, 167, 98, 0.2);
}

.bg-warning-custom {
    background-color: #f59e0b;
    color: white;
    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
}

.bg-secondary-custom {
    background-color: #6b7280;
    color: white;
    box-shadow: 0 2px 4px rgba(107, 114, 128, 0.2);
}

/* Botones de acción mejorados */
.btn-icon {
    width: 38px;
    height: 38px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: none;
    transition: all 0.2s ease-in-out;
    margin: 0 3px;
    font-size: 1.2rem;
}

.btn-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.btn-icon:active {
    transform: translateY(0);
}

/* Colores específicos para cada acción */
.btn-view {
    background-color: #e3f2fd;
    color: #1976d2;
}

.btn-view:hover {
    background-color: #1976d2;
    color: white;
}

.btn-start {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.btn-start:hover {
    background-color: #2e7d32;
    color: white;
}

.btn-edit {
    background-color: #fff3e0;
    color: #f57c00;
}

.btn-edit:hover {
    background-color: #f57c00;
    color: white;
}

.btn-download {
    background-color: #e8eaf6;
    color: #3f51b5;
}

.btn-download:hover {
    background-color: #3f51b5;
    color: white;
}

.btn-icon i {
    transition: transform 0.2s ease-in-out;
    font-size: 1.25rem;
}

.btn-icon:hover i {
    transform: scale(1.1);
}

.btn-group {
    display: inline-flex;
    gap: 0.5rem;
}

/* Tooltips personalizados */
[title] {
    position: relative;
}

[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.5rem 1rem;
    background: var(--color-gray-800);
    color: white;
    border-radius: 6px;
    font-size: 0.8rem;
    white-space: nowrap;
    z-index: 1000;
    margin-bottom: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

[title]:hover::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: var(--color-gray-800);
    margin-bottom: -2px;
    z-index: 1000;
}

/* Alertas personalizadas */
.custom-alert-success {
    background-color: #def7ec;
    border: none;
    border-radius: 12px;
    color: #046c4e;
}

.custom-alert-danger {
    background-color: #fde8e8;
    border: none;
    border-radius: 12px;
    color: #9b1c1c;
}

.custom-alert-info {
    background-color: #e1effe;
    border: none;
    border-radius: 12px;
    color: #1e429f;
}

/* Cards */
.card {
    border: none;
    border-radius: 12px;
    background: white;
    overflow: hidden;
}

.card.shadow-sm {
    box-shadow: var(--shadow-sm) !important;
}

/* Form floating labels */
.form-floating > .form-control,
.form-floating > .form-select {
    height: calc(3.5rem + 2px);
    padding: 1rem 0.75rem;
}

.form-floating > label {
    padding: 1rem 0.75rem;
}

/* Responsive */
@media (max-width: 768px) {
    .btn-group {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .modern-table {
        font-size: 0.875rem;
    }
    
    .badge {
        font-size: 0.75rem;
    }
}

/* Modal personalizado */
.modal-content {
    border: none;
    border-radius: 12px;
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    color: white;
    border-bottom: none;
    padding: 1.5rem;
}

.modal-title {
    font-weight: 600;
    font-size: 1.25rem;
}

.modal-header .btn-close {
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    opacity: 0.75;
}

.modal-header .btn-close:hover {
    opacity: 1;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid var(--color-gray-200);
    padding: 1.25rem 1.5rem;
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Inicializar tooltips
    $('[title]').tooltip();
    
    // Manejar el modal de nueva auditoría
    $('#createAuditModal').on('shown.bs.modal', function() {
        $('#sede_id').focus();
    });

    // Manejar el formulario de nueva auditoría
    $('#auditForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.success) {
                    window.location.href = response.redirect;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al crear auditoría'
                    });
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                let message = 'Error al procesar la solicitud';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endsection