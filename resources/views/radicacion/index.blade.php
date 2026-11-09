@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Radicación de Facturas</h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modalCargaMasiva">
                            <i class="fas fa-file-excel"></i> Actualizar Radicados Masivo
                        </button>
                        <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#modalObservacionesMasivas">
                            <i class="fas fa-comments"></i> Actualizar Observaciones Masivo
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownExportar" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download"></i> Exportar a Excel
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownExportar">
                                <li>
                                    <a class="dropdown-item" href="{{ route('radicacion.exportar', ['estado' => 'pendiente']) }}">
                                        <i class="fas fa-clock text-warning"></i> Exportar Pendientes
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalExportarRadicados">
                                        <i class="fas fa-check text-success"></i> Exportar Radicados
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <form method="GET" action="{{ route('radicacion.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="mes">Mes de Prestación:</label>
                                    <select name="mes" id="mes" class="form-control">
                                        @php
                                            $meses = [
                                                1 => 'Enero',
                                                2 => 'Febrero',
                                                3 => 'Marzo',
                                                4 => 'Abril',
                                                5 => 'Mayo',
                                                6 => 'Junio',
                                                7 => 'Julio',
                                                8 => 'Agosto',
                                                9 => 'Septiembre',
                                                10 => 'Octubre',
                                                11 => 'Noviembre',
                                                12 => 'Diciembre'
                                            ];
                                        @endphp
                                        @foreach($meses as $num => $nombre)
                                            <option value="{{ $num }}" {{ $mesSeleccionado == $num ? 'selected' : '' }}>
                                                {{ $nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="anio">Año:</label>
                                    <select name="anio" id="anio" class="form-control">
                                        @foreach(range(date('Y')-5, date('Y')) as $anio)
                                            <option value="{{ $anio }}" {{ $anioSeleccionado == $anio ? 'selected' : '' }}>
                                                {{ $anio }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_radicacion">Estado de Radicación:</label>
                                    <select name="estado_radicacion" id="estado_radicacion" class="form-control">
                                        <option value="todos" {{ $estadoRadicacion == 'todos' ? 'selected' : '' }}>Todos</option>
                                        <option value="radicado" {{ $estadoRadicacion == 'radicado' ? 'selected' : '' }}>Radicado</option>
                                        <option value="pendiente" {{ $estadoRadicacion == 'pendiente' ? 'selected' : '' }}>Pendiente por Radicar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Tabla de Facturas -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Departamento/Sede</th>
                                    <th>Aseguradora</th>
                                    <th>Factura</th>
                                    <th>Periodo Inicio</th>
                                    <th>Periodo Final</th>
                                    <th>Fecha Radicación</th>
                                    <th>Número Radicación</th>
                                    <th>Total</th>
                                    <th>Observaciones</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($facturas as $factura)
                                    <tr>
                                        <td>{{ $factura->departamento }}</td>
                                        <td>{{ $factura->aseguradora }}</td>
                                        <td>{{ $factura->factura }}</td>
                                        <td>{{ \Carbon\Carbon::parse($factura->periodo_facturacion_inicio)->format('d/m/Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($factura->periodo_facturacion_final)->format('d/m/Y') }}</td>
                                        <td>{{ $factura->fecha_radicacion ? \Carbon\Carbon::parse($factura->fecha_radicacion)->format('d/m/Y') : 'No radicada' }}</td>
                                        <td>{{ $factura->numero_radicacion ?? 'N/A' }}</td>
                                        <td>{{ number_format($factura->total_documento, 2, ',', '.') }}</td>
                                        <td>
                                            @if($factura->observacionesRadicacion->count() > 0)
                                                <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#modalVerObservaciones{{ $factura->id }}">
                                                    Ver ({{ $factura->observacionesRadicacion->count() }})
                                                </button>
                                                
                                                <!-- Modal para ver observaciones -->
                                                <div class="modal fade" id="modalVerObservaciones{{ $factura->id }}" tabindex="-1" aria-labelledby="modalVerObservacionesLabel{{ $factura->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="modalVerObservacionesLabel{{ $factura->id }}">Observaciones de la Factura {{ $factura->factura }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="list-group">
                                                                    @foreach($factura->observacionesRadicacion as $observacion)
                                                                        <div class="list-group-item">
                                                                            <div class="d-flex w-100 justify-content-between">
                                                                                <h6 class="mb-1">{{ $observacion->user->name }}</h6>
                                                                                <small>{{ $observacion->created_at->format('d/m/Y H:i') }}</small>
                                                                            </div>
                                                                            <p class="mb-1">{{ $observacion->observacion }}</p>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">Sin observaciones</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($factura->isPendienteRadicar())
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarObservacion{{ $factura->id }}">
                                                    <i class="fas fa-plus"></i> Observación
                                                </button>

                                                <!-- Modal para agregar observación -->
                                                <div class="modal fade" id="modalAgregarObservacion{{ $factura->id }}" tabindex="-1" aria-labelledby="modalAgregarObservacionLabel{{ $factura->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="modalAgregarObservacionLabel{{ $factura->id }}">Agregar Observación</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="{{ route('radicacion.agregar-observacion') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="factura_id" value="{{ $factura->id }}">
                                                                <div class="modal-body">
                                                                    <div class="form-group">
                                                                        <label for="observacion{{ $factura->id }}" class="form-label">Observación:</label>
                                                                        <textarea class="form-control" id="observacion{{ $factura->id }}" name="observacion" rows="3" required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" class="btn btn-primary">Guardar</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No se encontraron facturas para el período seleccionado</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para carga masiva -->
<div class="modal fade" id="modalCargaMasiva" tabindex="-1" role="dialog" aria-labelledby="modalCargaMasivaLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCargaMasivaLabel">Actualización Masiva de Radicados</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('radicacion.cargar-masivo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="archivo_excel">Seleccione el archivo Excel</label>
                        <input type="file" class="form-control-file" id="archivo_excel" name="archivo_excel" accept=".xlsx,.xls" required>
                    </div>
                    <div class="alert alert-info">
                        <h6>Formato del archivo Excel:</h6>
                        <p>El archivo debe contener las siguientes columnas:</p>
                        <ul>
                            <li>Factura</li>
                            <li>Número de Radicación</li>
                            <li>Fecha de Radicación (formato: DD/MM/YYYY)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Procesar Archivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para observaciones masivas -->
<div class="modal fade" id="modalObservacionesMasivas" tabindex="-1" aria-labelledby="modalObservacionesMasivasLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalObservacionesMasivasLabel">Actualización Masiva de Observaciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('radicacion.cargar-observaciones-masivo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <div class="d-grid gap-2 mb-3">
                            <a href="{{ route('radicacion.descargar-plantilla-observaciones') }}" class="btn btn-outline-primary">
                                <i class="fas fa-download"></i> Descargar Plantilla
                            </a>
                        </div>
                        <label for="archivo_excel_observaciones" class="form-label">Seleccione el archivo Excel completado:</label>
                        <input type="file" class="form-control" id="archivo_excel_observaciones" name="archivo_excel" accept=".xlsx,.xls" required>
                        <div class="form-text text-muted">
                            Formatos permitidos: .xlsx, .xls (máximo 2MB)
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Procesar Archivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para exportar radicados -->
<div class="modal fade" id="modalExportarRadicados" tabindex="-1" aria-labelledby="modalExportarRadicadosLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExportarRadicadosLabel">Exportar Facturas Radicadas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('radicacion.exportar', ['estado' => 'radicado']) }}" method="GET">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Seleccione el rango de fechas de radicación para exportar.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fecha_inicio" class="form-label">Fecha Inicial</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_fin" class="form-label">Fecha Final</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        margin-top: 20px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .card-header {
        background-color: #f8f9fa;
    }
    .table thead th {
        background-color: #343a40;
        color: white;
    }
    .btn-primary {
        padding: 8px 20px;
    }
    .modal-body .alert {
        margin-top: 15px;
    }
</style>
@endpush 