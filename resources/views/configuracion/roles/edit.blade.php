@extends('layouts.app')

@section('title', 'Editar Rol')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Editar Rol: {{ $role->name }}</h5>
                    <a href="{{ route('configuracion.roles.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('configuracion.roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre del Rol</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $role->name) }}"
                                   {{ strtoupper($role->name) === 'ADMINISTRADOR' ? 'readonly' : '' }}>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(strtoupper($role->name) !== 'ADMINISTRADOR')
                            <div class="mb-4">
                                <h5>Permisos del Rol</h5>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Selecciona los permisos que deseas asignar a este rol.
                                </div>
                                
                                <div class="row">
                                    @foreach($availablePermissions as $module => $permissions)
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100">
                                                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 text-capitalize">{{ $module }}</h6>
                                                    <div class="form-check mb-0">
                                                        <input type="checkbox" 
                                                               class="form-check-input select-all" 
                                                               data-module="{{ $module }}"
                                                               id="select_all_{{ $module }}">
                                                        <label class="form-check-label text-white" for="select_all_{{ $module }}">
                                                            Todos
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    @foreach($permissions as $action => $description)
                                                        <div class="form-check mb-2">
                                                            <input type="checkbox" 
                                                                   class="form-check-input permission-checkbox" 
                                                                   id="{{ $module }}.{{ $action }}"
                                                                   name="permissions[]"
                                                                   value="{{ $module }}.{{ $action }}"
                                                                   data-module="{{ $module }}"
                                                                   {{ in_array($module.'.'.$action, $rolePermissions) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="{{ $module }}.{{ $action }}">
                                                                {{ $description }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> El rol ADMINISTRADOR tiene acceso a todos los permisos del sistema por defecto.
                            </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('configuracion.roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para actualizar el estado del checkbox "Todos"
    function updateSelectAllCheckbox(module) {
        const moduleCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
        const selectAllCheckbox = document.querySelector(`#select_all_${module}`);
        const allChecked = Array.from(moduleCheckboxes).every(checkbox => checkbox.checked);
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = !allChecked && Array.from(moduleCheckboxes).some(checkbox => checkbox.checked);
    }

    // Manejar el cambio en los checkboxes individuales
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllCheckbox(this.dataset.module);
        });
    });

    // Manejar el cambio en los checkboxes "Todos"
    document.querySelectorAll('.select-all').forEach(selectAll => {
        selectAll.addEventListener('change', function() {
            const module = this.dataset.module;
            const moduleCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
            moduleCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    });

    // Inicializar el estado de los checkboxes "Todos"
    document.querySelectorAll('.select-all').forEach(selectAll => {
        updateSelectAllCheckbox(selectAll.dataset.module);
    });
});
</script>
@endpush
@endsection 