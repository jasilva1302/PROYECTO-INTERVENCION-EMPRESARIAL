@extends('layouts.app')

@section('title', 'Permisos por Rol')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Permisos por Rol</h4>
                    <div>
                        <button class="btn btn-info me-2" onclick="togglePermissionDetails()">
                            <i class="fas fa-info-circle"></i> Mostrar Detalles
                        </button>
                        <a href="{{ route('configuracion.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Debug info -->
                    <div id="permissionDetails" class="alert alert-info mb-3" style="display: none;">
                        <h5>Detalles de Permisos por Rol</h5>
                        @foreach($roles as $role)
                            <div class="mb-2">
                                <strong>{{ $role->name }}:</strong>
                                <pre class="mb-0">{{ json_encode($role->permissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Módulo/Permiso</th>
                                    @foreach($roles as $role)
                                        <th class="text-center">{{ $role->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Auditorías -->
                                <tr class="table-primary">
                                    <th colspan="{{ count($roles) + 1 }}">Auditorías</th>
                                </tr>
                                @foreach(['ver', 'crear', 'editar', 'eliminar', 'asignar', 'cerrar'] as $accion)
                                    <tr>
                                        <td>{{ ucfirst($accion) }} auditorías</td>
                                        @foreach($roles as $role)
                                            <td class="text-center">
                                                @if(in_array(ucfirst($accion) . " Auditorías", $role->permissions ?? []))
                                                    <i class="fas fa-check text-success"></i>
                                                @else
                                                    <i class="fas fa-times text-danger"></i>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach

                                <!-- Estadísticas -->
                                <tr class="table-primary">
                                    <th colspan="{{ count($roles) + 1 }}">Estadísticas</th>
                                </tr>
                                <tr>
                                    <td>Ver estadísticas</td>
                                    @foreach($roles as $role)
                                        <td class="text-center">
                                            @if(in_array("Ver estadísticas", $role->permissions ?? []))
                                                <i class="fas fa-check text-success"></i>
                                            @else
                                                <i class="fas fa-times text-danger"></i>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Tarifas -->
                                <tr class="table-primary">
                                    <th colspan="{{ count($roles) + 1 }}">Tarifas</th>
                                </tr>
                                @foreach(['ver', 'crear', 'editar', 'eliminar'] as $accion)
                                    <tr>
                                        <td>{{ ucfirst($accion) }} tarifas</td>
                                        @foreach($roles as $role)
                                            <td class="text-center">
                                                @if(in_array(ucfirst($accion) . " tarifas", $role->permissions ?? []))
                                                    <i class="fas fa-check text-success"></i>
                                                @else
                                                    <i class="fas fa-times text-danger"></i>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach

                                <!-- Evaluaciones -->
                                <tr class="table-primary">
                                    <th colspan="{{ count($roles) + 1 }}">Evaluaciones</th>
                                </tr>
                                @foreach(['ver', 'crear', 'editar', 'eliminar', 'realizar', 'calificar'] as $accion)
                                    <tr>
                                        <td>{{ ucfirst($accion) }} evaluaciones</td>
                                        @foreach($roles as $role)
                                            <td class="text-center">
                                                @if(in_array(ucfirst($accion) . " evaluaciones", $role->permissions ?? []))
                                                    <i class="fas fa-check text-success"></i>
                                                @else
                                                    <i class="fas fa-times text-danger"></i>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach

                                <!-- Facturación -->
                                <tr class="table-primary">
                                    <th colspan="{{ count($roles) + 1 }}">Facturación</th>
                                </tr>
                                @foreach(['ver', 'crear', 'editar', 'eliminar', 'cargar', 'exportar'] as $accion)
                                    <tr>
                                        <td>{{ ucfirst($accion) }} facturación</td>
                                        @foreach($roles as $role)
                                            <td class="text-center">
                                                @if(in_array(ucfirst($accion) . " facturación", $role->permissions ?? []))
                                                    <i class="fas fa-check text-success"></i>
                                                @else
                                                    <i class="fas fa-times text-danger"></i>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    background-color: #f8f9fa;
}
.table-primary th {
    background-color: #cfe2ff;
    color: #0d6efd;
}
.fas.fa-check {
    color: #198754;
}
.fas.fa-times {
    color: #dc3545;
}
.table td, .table th {
    vertical-align: middle;
}
.table th {
    font-weight: 600;
}
pre {
    background-color: #f8f9fa;
    padding: 0.5rem;
    border-radius: 0.25rem;
    margin-top: 0.5rem;
}
</style>

<script>
function togglePermissionDetails() {
    const details = document.getElementById('permissionDetails');
    if (details.style.display === 'none') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}
</script>
@endsection 