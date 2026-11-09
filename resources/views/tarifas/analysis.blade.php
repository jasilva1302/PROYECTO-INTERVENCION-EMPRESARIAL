@extends('layouts.app')

@section('title', 'Análisis de Facturas')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h1>Análisis de Facturas</h1>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('tarifas.analyze') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sede">Sede</label>
                            <input type="text" class="form-control" id="sede" name="sede" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="factura">Factura</label>
                            <input type="text" class="form-control" id="factura" name="factura" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="aseguradora">Aseguradora</label>
                            <input type="text" class="form-control" id="aseguradora" name="aseguradora" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="regimen">Régimen</label>
                            <input type="text" class="form-control" id="regimen" name="regimen" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="saldo">Saldo Facturado</label>
                            <input type="number" step="0.01" class="form-control" id="saldo" name="saldo" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="excel">Archivo Excel con servicios</label>
                            <input type="file" class="form-control-file" id="excel" name="excel" accept=".xlsx,.xls" required>
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Analizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(isset($resultados))
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Resultados del Análisis</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Resumen:</strong> 
                Total facturado: ${{ number_format($saldo_facturado, 2) }} | 
                Diferencia total: ${{ number_format($diferencia_total, 2) }} | 
                Servicios correctos: {{ $correctos }} | 
                Servicios con diferencias: {{ $incorrectos }}
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Servicio</th>
                            <th>CUPS</th>
                            <th>Tarifa Esperada</th>
                            <th>Tarifa Facturada</th>
                            <th>Diferencia</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultados as $resultado)
                        <tr class="{{ $resultado['diferencia'] != 0 ? 'table-danger' : 'table-success' }}">
                            <td>{{ $resultado['servicio'] }}</td>
                            <td>{{ $resultado['cups'] }}</td>
                            <td>${{ number_format($resultado['tarifa_esperada'], 2) }}</td>
                            <td>${{ number_format($resultado['tarifa_facturada'], 2) }}</td>
                            <td>${{ number_format($resultado['diferencia'], 2) }}</td>
                            <td>
                                @if($resultado['diferencia'] == 0)
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Correcto</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Incorrecto</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3">
                <button class="btn btn-info mr-2">
                    <i class="fas fa-file-pdf"></i> Generar PDF
                </button>
                <button class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection