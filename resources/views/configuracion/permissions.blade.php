@extends('layouts.app')

@section('title', 'Editar Permisos')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Gestión de Permisos - {{ $role->name }}</h4>
                        <small class="text-muted">Configure los permisos para este rol seleccionando las casillas correspondientes</small>
                    </div>
                    <div>
                        <a href="{{ route('configuracion.roles.permissions.view') }}" class="btn btn-info me-2">
                            <i class="fas fa-table"></i> Ver Tabla de Permisos
                        </a>
                        <a href="{{ route('configuracion.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Resumen de permisos actuales -->
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Permisos Actuales</h5>
                        <p class="mb-0">Este rol tiene acceso a:</p>
                        <ul class="mb-0">
                            @php
                                $hasPermissions = false;
                                $permissionsByModule = [];
                                foreach ($currentPermissions as $permission) {
                                    $parts = explode('.', $permission);
                                    if (count($parts) === 2) {
                                        $module = $parts[0];
                                        if (!isset($permissionsByModule[$module])) {
                                            $permissionsByModule[$module] = [];
                                        }
                                        $permissionsByModule[$module][] = $permission;
                                        $hasPermissions = true;
                                    }
                                }
                            @endphp
                            @if($hasPermissions)
                                @foreach($permissionsByModule as $module => $modulePermissions)
                                    <li>
                                        <strong>{{ ucfirst($module) }}:</strong>
                                        @foreach($modulePermissions as $permission)
                                            @php
                                                $parts = explode('.', $permission);
                                                $action = $parts[1];
                                                $description = $availablePermissions[$module][$action] ?? $action;
                                            @endphp
                                            <span class="badge bg-success">{{ $description }}</span>
                                        @endforeach
                                    </li>
                                @endforeach
                            @else
                                <li>Este rol no tiene permisos asignados actualmente.</li>
                            @endif
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('configuracion.update-permissions', $role) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            @foreach($availablePermissions as $modulo => $acciones)
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">{{ ucfirst($modulo) }}</h5>
                                            <div class="form-check">
                                                <input type="checkbox" 
                                                       class="form-check-input module-checkbox" 
                                                       id="{{ $modulo }}_module"
                                                       data-module="{{ $modulo }}">
                                                <label class="form-check-label" for="{{ $modulo }}_module">
                                                    Seleccionar todos
                                                </label>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            @foreach($acciones as $accion => $descripcion)
                                                <div class="form-check mb-2 permission-item">
                                                    <input type="checkbox" 
                                                           class="form-check-input permission-checkbox" 
                                                           name="permissions[{{ $modulo }}.{{ $accion }}]"
                                                           id="{{ $modulo }}_{{ $accion }}"
                                                           value="1"
                                                           data-module="{{ $modulo }}"
                                                           {{ in_array("$modulo.$accion", array_map('trim', $currentPermissions)) ? 'checked' : '' }}>
                                                    <label class="form-check-label d-flex justify-content-between align-items-center" for="{{ $modulo }}_{{ $accion }}">
                                                        <span>{{ $descripcion }}</span>
                                                        @if(in_array("$modulo.$accion", array_map('trim', $currentPermissions)))
                                                            <span class="badge bg-success">Activo</span>
                                                        @endif
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Permisos
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header.bg-primary {
    background-color: #0d6efd !important;
}

.permission-item {
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.permission-item:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75rem;
    padding: 0.25em 0.6em;
}

.alert-info {
    background-color: #cff4fc;
    border-color: #b6effb;
}

.alert-info .badge {
    margin: 0.25rem;
}

.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

.form-check-input:indeterminate {
    background-color: #6c757d;
    border-color: #6c757d;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para actualizar el estado del checkbox del módulo
    function updateModuleCheckbox(moduleId) {
        const moduleCheckbox = document.getElementById(moduleId);
        if (!moduleCheckbox) return;

        const module = moduleCheckbox.dataset.module;
        const permissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
        
        const checkedCount = Array.from(permissionCheckboxes).filter(cb => cb.checked).length;
        const totalCount = permissionCheckboxes.length;
        
        moduleCheckbox.checked = checkedCount === totalCount;
        moduleCheckbox.indeterminate = checkedCount > 0 && checkedCount < totalCount;
    }

    // Función para manejar el cambio en los checkboxes de permisos
    function handlePermissionCheckboxChange(e) {
        const module = e.target.dataset.module;
        updateModuleCheckbox(`${module}_module`);
    }

    // Función para manejar el cambio en los checkboxes de módulos
    function handleModuleCheckboxChange(e) {
        const module = e.target.dataset.module;
        const permissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
        permissionCheckboxes.forEach(cb => {
            cb.checked = e.target.checked;
        });
    }

    // Agregar event listeners
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', handlePermissionCheckboxChange);
    });

    document.querySelectorAll('.module-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', handleModuleCheckboxChange);
        // Inicializar estado
        updateModuleCheckbox(checkbox.id);
    });
});
</script>
@endpush
@endsection
