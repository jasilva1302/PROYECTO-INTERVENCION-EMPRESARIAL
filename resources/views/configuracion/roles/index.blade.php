@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">Gestión de Roles</h2>
                    <div>
                        <a href="{{ route('configuracion.roles.create') }}" class="btn btn-light me-2">Nuevo Rol</a>
                        <a href="{{ route('configuracion.index') }}" class="btn btn-light">Volver</a>
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

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rol</th>
                                    <th>Usuarios</th>
                                    <th>Permisos Actuales</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                <tr>
                                    <td>{{ $role->name }}</td>
                                    <td>{{ $role->users_count }}</td>
                                    <td>
                                        <div class="permissions-container" style="max-height: 100px; overflow-y: auto;">
                                            @if($role->permissions && is_iterable($role->permissions))
                                                @foreach($role->permissions as $permission)
                                                    @if($permission && isset($permission->name))
                                                        <span class="badge bg-info me-1 mb-1" title="{{ $permission->description ?? '' }}">
                                                            {{ $permission->name }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            @else
                                                <span class="text-muted">Sin permisos asignados</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('configuracion.roles.edit', $role) }}" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Editar Rol y Permisos
                                            </a>
                                            @if(strtoupper($role->name) !== 'ADMINISTRADOR')
                                                <form action="{{ route('configuracion.roles.destroy', $role) }}" 
                                                      method="POST" 
                                                      class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('¿Está seguro de eliminar este rol? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
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
        </div>
    </div>
</div>

@push('styles')
<style>
    .permissions-container {
        max-height: 100px;
        overflow-y: auto;
        padding: 5px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }
    .badge {
        font-size: 0.85em;
    }
    .badge:hover {
        cursor: help;
    }
</style>
@endpush
@endsection 