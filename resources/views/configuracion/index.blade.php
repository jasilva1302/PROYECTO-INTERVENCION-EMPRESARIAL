@extends('layouts.app')

@section('title', 'Configuración del Sistema')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
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

            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Configuración del Sistema</h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        @hasPermission(Config::get('permissions.configuracion.ver'))
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Usuarios</h5>
                                    <p class="card-text">Gestión de usuarios del sistema</p>
                                    @hasPermission(Config::get('permissions.configuracion.editar'))
                                    <a href="{{ route('configuracion.create-user') }}" class="btn btn-success mb-2">
                                        <i class="fas fa-user-plus"></i> Crear Usuario
                                    </a>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Email</th>
                                                    <th>Rol</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($users as $user)
                                                <tr>
                                                    <td>{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <td>{{ $user->role->name ?? 'Sin rol' }}</td>
                                                    <td>
                                                        <a href="{{ route('configuracion.edit-user', $user) }}" 
                                                           class="btn btn-info btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('configuracion.destroy-user', $user) }}" 
                                                              method="POST" 
                                                              class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('¿Está seguro de eliminar este usuario?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endhasPermission
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Roles y Permisos</h5>
                                    <p class="card-text">Configuración de roles y permisos</p>
                                    @hasPermission(Config::get('permissions.configuracion.editar'))
                                    <a href="{{ route('configuracion.roles.create') }}" class="btn btn-success mb-2">
                                        <i class="fas fa-plus"></i> Crear Rol
                                    </a>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Rol</th>
                                                    <th>Usuarios</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($roles as $role)
                                                <tr>
                                                    <td>{{ $role->name }}</td>
                                                    <td>{{ $role->users_count }}</td>
                                                    <td>
                                                        <a href="{{ route('configuracion.edit-permissions', $role) }}" 
                                                           class="btn btn-primary btn-sm"
                                                           title="Editar permisos">
                                                            <i class="fas fa-key"></i>
                                                        </a>
                                                        <a href="{{ route('configuracion.roles.edit', $role) }}" 
                                                           class="btn btn-info btn-sm"
                                                           title="Editar rol">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($role->name !== 'ADMINISTRADOR')
                                                        <form action="{{ route('configuracion.roles.destroy', $role) }}" 
                                                              method="POST" 
                                                              class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('¿Está seguro de eliminar este rol?')"
                                                                    title="Eliminar rol">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endhasPermission
                                </div>
                            </div>
                        </div>
                        @endhasPermission
                    </div>
                </div>
            </div>

            <!-- Sección de Empresas -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Empresas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Razón Social</th>
                                    <th>Sedes</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($empresas as $empresa)
                                <tr>
                                    <td>{{ $empresa->razon_social }}</td>
                                    <td>{{ $empresa->sedes_count }}</td>
                                    <td>
                                        <a href="{{ route('configuracion.edit-empresa', $empresa) }}" 
                                           class="btn btn-info btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('configuracion.destroy-empresa', $empresa) }}" 
                                              method="POST" 
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('¿Está seguro de eliminar esta empresa?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('configuracion.create-empresa') }}" class="btn btn-success">
                        <i class="fas fa-plus"></i> Crear Nueva Empresa
                    </a>
                </div>
            </div>

            <!-- Sección de Sedes -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Sedes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sede</th>
                                    <th>Empresa</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sedes as $sede)
                                <tr>
                                    <td>{{ $sede->sede }}</td>
                                    <td>{{ $sede->empresa->razon_social ?? 'Sin empresa' }}</td>
                                    <td>
                                        <a href="{{ route('configuracion.edit-sede', $sede) }}" 
                                           class="btn btn-info btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('configuracion.destroy-sede', $sede) }}" 
                                              method="POST" 
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('¿Está seguro de eliminar esta sede?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('configuracion.create-sede') }}" class="btn btn-success">
                        <i class="fas fa-plus"></i> Crear Nueva Sede
                    </a>
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

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.table th {
    background-color: #f8f9fa;
}

.fas {
    width: 1rem;
    text-align: center;
}

.btn .fas {
    margin-right: 0.25rem;
}
</style>
@endsection
