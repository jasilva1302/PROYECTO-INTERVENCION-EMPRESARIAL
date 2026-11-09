@extends('layouts.app')

@section('title', 'Detalles de Carga')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-file-alt"></i> Detalles de Carga</h2>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-custom text-white">
            <h5 class="mb-0">Información General</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Archivo Original</dt>
                        <dd class="col-sm-8">{{ $cargue->archivo_original }}</dd>

                        <dt class="col-sm-4">Fecha de Carga</dt>
                        <dd class="col-sm-8">{{ $cargue->created_at->format('d/m/Y H:i:s') }}</dd>

                        <dt class="col-sm-4">Usuario</dt>
                        <dd class="col-sm-8">{{ $cargue->user->name }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Total Registros</dt>
                        <dd class="col-sm-8">{{ $cargue->total_registros }}</dd>

                        <dt class="col-sm-4">Procesados</dt>
                        <dd class="col-sm-8">{{ $cargue->registros_procesados }}</dd>

                        <dt class="col-sm-4">Errores</dt>
                        <dd class="col-sm-8">
                            @if($cargue->registros_error > 0)
                                <span class="badge bg-danger">{{ $cargue->registros_error }}</span>
                            @else
                                <span class="badge bg-success">0</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8">
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
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if($cargue->errores && count($cargue->errores) > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Errores y Advertencias del Cargue</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Fila</th>
                                <th>Tipo</th>
                                <th>Mensaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cargue->errores as $error)
                                <tr class="{{ $error['tipo'] === 'error' ? 'table-danger' : 'table-warning' }}">
                                    <td>{{ $error['fila'] }}</td>
                                    <td>
                                        @if($error['tipo'] === 'error')
                                            <span class="badge bg-danger">Error</span>
                                        @else
                                            <span class="badge bg-warning">Advertencia</span>
                                        @endif
                                    </td>
                                    <td>{{ $error['mensaje'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('facturacion.cargue.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
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

    dt {
        font-weight: 600;
        color: #4a5568;
    }

    .table th {
        font-weight: 600;
        color: #4a5568;
    }
</style>
@endpush

@endsection 