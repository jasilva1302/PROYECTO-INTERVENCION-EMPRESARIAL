@extends('layouts.app')

@section('title', 'Cargue de Facturación')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-file-upload"></i> Cargue de Facturación</h2>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-custom text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history"></i> Historial de Cargas</h5>
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('crear Facturación'))
            <a href="{{ route('facturacion.cargue.create') }}" class="btn btn-light btn-sm">
                <i class="fas fa-plus"></i> Nueva Carga
            </a>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Archivo</th>
                            <th>Total Registros</th>
                            <th>Procesados</th>
                            <th>Errores</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cargues as $cargue)
                        <tr>
                            <td>{{ $cargue->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $cargue->user->name }}</td>
                            <td>{{ $cargue->archivo_original }}</td>
                            <td>{{ $cargue->total_registros }}</td>
                            <td>{{ $cargue->registros_procesados }}</td>
                            <td>
                                @if($cargue->registros_error > 0)
                                    <span class="badge bg-danger">{{ $cargue->registros_error }}</span>
                                @else
                                    <span class="badge bg-success">0</span>
                                @endif
                            </td>
                            <td>
                                @switch($cargue->estado)
                                    @case('en_proceso')
                                        <span class="badge bg-warning">En Proceso</span>
                                        @break
                                    @case('completado')
                                        <span class="badge bg-success">Completado</span>
                                        @break
                                    @case('error')
                                        <span class="badge bg-danger">Error</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="text-center">
                                <a href="{{ route('facturacion.cargue.show', $cargue) }}" 
                                   class="btn btn-sm btn-custom" 
                                   title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('eliminar Facturación'))
                                <form action="{{ route('facturacion.cargue.destroy', $cargue) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('¿Estás seguro de que deseas eliminar este cargue? Las facturas que no estén en auditorías serán eliminadas.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-sm btn-danger" 
                                            title="Eliminar cargue">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No hay registros de carga</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $cargues->links() }}
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    :root {
        --custom-orange: #ff6b00;
        --custom-orange-dark: #e65100;
    }

    .bg-custom {
        background-color: var(--custom-orange) !important;
    }

    .btn-custom {
        background-color: var(--custom-orange);
        color: white;
    }

    .btn-custom:hover {
        background-color: var(--custom-orange-dark);
        color: white;
    }
</style>
@endpush

@endsection 