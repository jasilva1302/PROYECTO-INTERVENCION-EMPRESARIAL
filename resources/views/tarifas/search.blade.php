@extends('layouts.app')

@section('title', 'Buscar Tarifas')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h1>Buscar Tarifas</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('tarifas.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('tarifas.search') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="empresa">Empresa</label>
                            <input type="text" class="form-control" id="empresa" name="empresa" value="{{ request('empresa') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="sede">Sede</label>
                            <input type="text" class="form-control" id="sede" name="sede" value="{{ request('sede') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="aseguradora">Aseguradora</label>
                            <input type="text" class="form-control" id="aseguradora" name="aseguradora" value="{{ request('aseguradora') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="year">Año</label>
                            <select class="form-control" id="year" name="year">
                                <option value="">Todos</option>
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="regimen">Régimen</label>
                            <select class="form-control" id="regimen" name="regimen">
                                <option value="">Todos</option>
                                <option value="Contributivo" {{ request('regimen') == 'Contributivo' ? 'selected' : '' }}>Contributivo</option>
                                <option value="Subsidiado" {{ request('regimen') == 'Subsidiado' ? 'selected' : '' }}>Subsidiado</option>
                                <option value="Particular" {{ request('regimen') == 'Particular' ? 'selected' : '' }}>Particular</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cups">CUPS</label>
                            <input type="text" class="form-control" id="cups" name="cups" value="{{ request('cups') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(request()->hasAny(['empresa', 'sede', 'aseguradora', 'year', 'regimen', 'cups']))
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Empresa</th>
                            <th>Sede</th>
                            <th>Aseguradora</th>
                            <th>Régimen</th>
                            <th>Servicio</th>
                            <th>CUPS</th>
                            <th>Tarifa Actual</th>
                            <th>Historial</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tarifas as $tarifa)
                        <tr>
                            <td>{{ $tarifa->empresa }}</td>
                            <td>{{ $tarifa->sede }}</td>
                            <td>{{ $tarifa->tercero }}</td>
                            <td>{{ $tarifa->regimen }}</td>
                            <td>{{ $tarifa->descripcion_servicio }}</td>
                            <td>{{ $tarifa->cups }}</td>
                            <td>${{ number_format($tarifa->valor, 2) }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#historialModal{{ $tarifa->id }}">
                                    <i class="fas fa-history"></i> Ver
                                </button>
                            </td>
                        </tr>

                        <!-- Historial Modal -->
                        <div class="modal fade" id="historialModal{{ $tarifa->id }}" tabindex="-1" role="dialog" aria-labelledby="historialModalLabel{{ $tarifa->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="historialModalLabel{{ $tarifa->id }}">Historial de Tarifa</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Usuario</th>
                                                        <th>Tarifa Anterior</th>
                                                        <th>Tarifa Nueva</th>
                                                        <th>Año</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($tarifa->historial as $historial)
                                                    <tr>
                                                        <td>{{ $historial->created_at->format('d/m/Y H:i') }}</td>
                                                        <td>{{ $historial->user->name }}</td>
                                                        <td>${{ number_format($historial->tarifa_anterior, 2) }}</td>
                                                        <td>${{ number_format($historial->tarifa_nueva, 2) }}</td>
                                                        <td>{{ $historial->year }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $tarifas->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
    @endif
</div>
@endsection