@extends('layouts.app')

@section('title', 'Detalle Auditoría')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h1>Auditoría: {{ $audit->sede }} - {{ $audit->month }} {{ $audit->year }}</h1>
        </div>
        <div class="col-md-6 text-end">
            @if($audit->completed)
                <a href="{{ route('auditoria.generate-report', $audit) }}" class="btn btn-info">
                    <i class="fas fa-file-pdf"></i> Generar Reporte
                </a>
            @endif
            <a href="{{ route('auditoria.pdf-structure', $audit) }}" class="btn btn-primary">
                <i class="fas fa-folder-open"></i> Ver Documentos
            </a>
            <a href="{{ route('auditoria.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-orange">
            <h5 class="mb-0 text-black">Información General</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Usuario:</strong> {{ $audit->user->name }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Estado:</strong> 
                        @if($audit->completed)
                            <span class="badge bg-success">Completada</span>
                        @elseif($audit->started_at)
                            <span class="badge bg-warning">En progreso</span>
                        @else
                            <span class="badge bg-secondary">Pendiente</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-4">
                    <p><strong>Link servidor:</strong> <a href="{{ $audit->link }}" target="_blank">{{ $audit->link }}</a></p>
                </div>
            </div>
            
            @if($audit->started_at)
            <div class="row mt-3">
                <div class="col-md-4">
                    <p><strong>Iniciada:</strong> {{ $audit->started_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($audit->finished_at)
                <div class="col-md-4">
                    <p><strong>Finalizada:</strong> {{ $audit->finished_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Tiempo total:</strong> 
                        {{ $audit->started_at->diff($audit->finished_at)->format('%H:%I:%S') }}
                    </p>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-orange">
            <h5 class="mb-0 text-black">Facturas Auditadas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr class="bg-orange">
                            <th class="text-black">Factura</th>
                            <th class="text-black">Aseguradora</th>
                            <th class="text-black">Saldo</th>
                            <th class="text-black">Hallazgos</th>
                            <th class="text-black">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($audit->facturas as $factura)
                        @php
                            $auditFactura = $factura->auditFacturas->firstWhere('audit_id', $audit->id);
                            $hallazgos = $auditFactura ? $auditFactura->hallazgos : collect();
                        @endphp
                        <tr>
                            <td>{{ $factura->factura }}</td>
                            <td>{{ $factura->aseguradora }}</td>
                            <td>${{ number_format($factura->saldo, 2) }}</td>
                            <td>{{ $factura->hallazgos_count }} hallazgos</td>
                            <td>
                                <div class="btn-group">
                                    @if($factura->hallazgos_count > 0)
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#hallazgosModal{{ $factura->id }}">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                    @endif
                                    <a href="{{ route('auditoria.pdf-viewer', [
                                        'audit' => $audit->id,
                                        'aseguradora' => urlencode($factura->aseguradora),
                                        'factura' => urlencode($factura->factura)
                                    ]) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-file-pdf"></i> PDFs
                                    </a>
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

@foreach($audit->facturas as $factura)
@php
    $auditFactura = $factura->auditFacturas->firstWhere('audit_id', $audit->id);
    $hallazgos = $auditFactura ? $auditFactura->hallazgos : collect();
@endphp
@if($hallazgos->count() > 0)
<div class="modal fade" id="hallazgosModal{{ $factura->id }}" tabindex="-1" aria-labelledby="hallazgosModalLabel{{ $factura->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-orange">
                <h5 class="modal-title text-black" id="hallazgosModalLabel{{ $factura->id }}">Hallazgos para Factura {{ $factura->factura }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @foreach($hallazgos as $hallazgo)
                <div class="card mb-2">
                    <div class="card-body">
                        <p>{{ $hallazgo->observacion }}</p>
                        @if($hallazgo->soporte)
                        <p>Soporte: <a href="{{ Storage::url($hallazgo->soporte) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-file-download"></i> Descargar
                        </a></p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endif
@endforeach

@section('styles')
<style>
    .bg-orange {
        background-color: #FF6F00 !important;
    }
    .text-black {
        color: #000 !important;
    }
    .btn-group .btn {
        margin-right: 5px;
    }
</style>
@endsection

@endsection