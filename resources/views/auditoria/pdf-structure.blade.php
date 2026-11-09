@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Estructura de Facturas PDF</h1>
        <div>
            <a href="{{ route('auditoria.show', $audit) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('auditoria.start', $audit) }}" class="btn btn-primary ms-2">
                <i class="fas fa-clipboard-check"></i> Continuar Auditoría
            </a>
        </div>
    </div>

    <div class="row">
        @foreach($structure as $aseguradora)
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ $aseguradora['nombre'] }}</h5>
                </div>
                <div class="card-body" style="height: 100vh;">
                    <div class="list-group">
                        @foreach($aseguradora['facturas'] as $factura)
                        <a href="{{ route('auditoria.pdf-viewer', [
                            'audit' => $audit->id, 
                            'aseguradora' => $aseguradora['nombre'], 
                            'factura' => $factura['nombre']
                        ]) }}" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            {{ $factura['nombre'] }}
                            <span class="badge bg-primary rounded-pill">{{ $factura['count'] }} soportes</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection