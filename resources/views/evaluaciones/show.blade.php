@extends('layouts.app')

@section('title', 'Detalles de Evaluación')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0 text-gray-800">{{ $evaluacion->titulo }}</h1>
                    <p class="text-muted mb-0">
                        Creada por {{ $evaluacion->creador->name }} el {{ $evaluacion->created_at->format('d/m/Y') }}
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('evaluaciones.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    
                    @if(auth()->user()->hasRole('ASISTENTE'))
                        @if($evaluacion->isDisponibleParaUsuario(auth()->user()))
                            <a href="{{ route('evaluaciones.take', $evaluacion) }}" class="btn btn-primary">
                                <i class="fas fa-pencil-alt"></i> Realizar Evaluación
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información de la Evaluación -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Información de la Evaluación</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h6 class="text-muted mb-2">Descripción</h6>
                            <p>{{ $evaluacion->descripcion ?: 'Sin descripción' }}</p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted mb-2">Fecha de Inicio</h6>
                            <p>{{ $evaluacion->fecha_inicio->format('d/m/Y H:i') }}</p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted mb-2">Fecha Límite</h6>
                            <p>{{ $evaluacion->fecha_limite->format('d/m/Y H:i') }}</p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted mb-2">Tiempo Límite</h6>
                            <p>{{ $evaluacion->tiempo_limite ? $evaluacion->tiempo_limite . ' minutos' : 'Sin límite' }}</p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted mb-2">Estado</h6>
                            <p>
                                @switch($evaluacion->estado)
                                    @case('inactiva')
                                    <span class="badge bg-danger">Inactiva</span>
                                        @break
                                    @case('proxima')
                                    <span class="badge bg-info">Próxima</span>
                                        @break
                                    @case('finalizada')
                                    <span class="badge bg-secondary">Finalizada</span>
                                        @break
                                    @case('en_progreso')
                                        <span class="badge bg-warning">En Progreso</span>
                                        @break
                                    @case('completada')
                                        <span class="badge bg-primary">Completada</span>
                                        @break
                                    @case('calificada')
                                        <span class="badge bg-success">Calificada</span>
                                        @break
                                    @default
                                    <span class="badge bg-success">En Curso</span>
                                @endswitch
                            </p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted mb-2">Total de Preguntas</h6>
                            <p>{{ $evaluacion->preguntas->count() }}</p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted mb-2">Puntaje Total</h6>
                            <p>{{ $evaluacion->getPuntajeTotal() }} puntos</p>
                        </div>
                    </div>

                    @if(!auth()->user()->hasRole('ASISTENTE'))
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2">Preguntas</h6>
                        @foreach($evaluacion->preguntas as $pregunta)
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    Pregunta {{ $loop->iteration }} 
                                    <span class="text-muted">({{ $pregunta->puntaje }} puntos)</span>
                                </h6>
                                <p>{{ $pregunta->pregunta }}</p>

                                @if($pregunta->tipo === 'opcion_multiple')
                                    <div class="ms-4">
                                        @foreach($pregunta->opciones as $opcion)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" disabled
                                                   {{ $opcion->es_correcta ? 'checked' : '' }}>
                                            <label class="form-check-label {{ $opcion->es_correcta ? 'text-success fw-bold' : '' }}">
                                                {{ $opcion->opcion }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                @elseif($pregunta->tipo === 'verdadero_falso')
                                    <div class="ms-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" disabled>
                                            <label class="form-check-label">Verdadero</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" disabled>
                                            <label class="form-check-label">Falso</label>
                                        </div>
                                    </div>
                                @else
                                    <div class="ms-4">
                                        <em class="text-muted">Pregunta de respuesta libre</em>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Resultados y Participantes -->
        <div class="col-md-4">
            @if(auth()->user()->hasRole('ASISTENTE'))
                @if($evaluacionUsuario)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Tu Resultado</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            @if($evaluacionUsuario->estado === 'calificada')
                                <h1 class="display-4">{{ number_format($evaluacionUsuario->calificacion, 1) }}</h1>
                                <p class="text-muted">Calificación Final</p>
                            @elseif($evaluacionUsuario->estado === 'completada')
                                <div class="alert alert-info">
                                    <i class="fas fa-clock"></i> En espera de calificación
                                </div>
                            @elseif($evaluacionUsuario->estado === 'en_progreso')
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Evaluación en progreso
                                </div>
                            @endif
                        </div>

                        <div class="row text-center">
                            <div class="col-6">
                                <p class="mb-1"><strong>Inicio</strong></p>
                                <p class="text-muted">
                                    {{ $evaluacionUsuario->fecha_inicio ? $evaluacionUsuario->fecha_inicio->format('d/m/Y H:i') : 'No iniciada' }}
                                </p>
                            </div>
                            <div class="col-6">
                                <p class="mb-1"><strong>Fin</strong></p>
                                <p class="text-muted">
                                    {{ $evaluacionUsuario->fecha_fin ? $evaluacionUsuario->fecha_fin->format('d/m/Y H:i') : 'No finalizada' }}
                                </p>
                            </div>
                        </div>

                        @if($evaluacionUsuario->retroalimentacion)
                        <div class="mt-3">
                            <h6>Retroalimentación</h6>
                            <p class="mb-0">{{ $evaluacionUsuario->retroalimentacion }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            @else
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Participantes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Asistente</th>
                                        <th>Estado</th>
                                        <th>Nota</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($evaluacion->evaluacionesUsuarios as $evaluacionUsuario)
                                    <tr>
                                        <td>{{ $evaluacionUsuario->usuario->name }}</td>
                                        <td>
                                            @switch($evaluacionUsuario->estado)
                                                @case('pendiente')
                                                    <span class="badge bg-warning">Pendiente</span>
                                                    @break
                                                @case('en_progreso')
                                                    <span class="badge bg-info">En Progreso</span>
                                                    @break
                                                @case('completada')
                                                    <span class="badge bg-primary">Completada</span>
                                                    @break
                                                @case('calificada')
                                                    <span class="badge bg-success">Calificada</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            {{ $evaluacionUsuario->calificacion ? number_format($evaluacionUsuario->calificacion, 1) : '-' }}
                                        </td>
                                        <td>
                                            @if($evaluacionUsuario->estado === 'completada' && auth()->user()->hasPermission('Calificar evaluaciones'))
                                                <a href="{{ route('evaluaciones.grade', $evaluacionUsuario) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-check"></i> Calificar
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .badge {
        font-size: 0.875em;
    }

    .form-check-input:checked[disabled] {
        background-color: #198754;
        border-color: #198754;
    }
</style>
@endpush