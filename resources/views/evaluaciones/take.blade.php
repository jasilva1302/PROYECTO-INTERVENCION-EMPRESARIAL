@extends('layouts.app')

@section('title', 'Realizar Evaluación')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h3 mb-0 text-gray-800">{{ $evaluacion->titulo }}</h1>
                </div>
                <div class="col-md-4 text-end">
                    @if($evaluacion->tiempo_limite)
                    <div class="timer-container">
                        <h4 class="mb-0">
                            Tiempo restante: 
                            <span id="timer" 
                                  data-tiempo-limite="{{ $evaluacion->tiempo_limite }}"
                                  data-fecha-inicio="{{ $evaluacionUsuario->fecha_inicio }}"
                                  data-tiempo-transcurrido="{{ $tiempoTranscurrido ?? 0 }}">
                                --:--
                            </span>
                        </h4>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form id="evaluacionForm" action="{{ route('evaluaciones.submit', $evaluacion) }}" method="POST">
        @csrf
        <div class="card shadow-sm">
            <div class="card-body">
                @foreach($evaluacion->preguntas as $pregunta)
                <div class="pregunta-container mb-4">
                    <h5 class="mb-3">
                        {{ $loop->iteration }}. {{ $pregunta->pregunta }}
                        <small class="text-muted">({{ $pregunta->puntaje }} puntos)</small>
                    </h5>

                    @if($pregunta->tipo === 'opcion_multiple')
                                    @foreach($pregunta->opciones as $opcion)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="respuestas[{{ $pregunta->id }}]" 
                                   value="{{ $opcion->id }}"
                                               id="opcion_{{ $opcion->id }}"
                                               required>
                                        <label class="form-check-label" for="opcion_{{ $opcion->id }}">
                                            {{ $opcion->opcion }}
                                        </label>
                                    </div>
                                    @endforeach
                    @elseif($pregunta->tipo === 'verdadero_falso')
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="respuestas[{{ $pregunta->id }}]" 
                                   value="verdadero"
                                               id="verdadero_{{ $pregunta->id }}"
                                               required>
                                        <label class="form-check-label" for="verdadero_{{ $pregunta->id }}">
                                            Verdadero
                                        </label>
                                    </div>
                        <div class="form-check mb-2">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="respuestas[{{ $pregunta->id }}]" 
                                   value="falso"
                                               id="falso_{{ $pregunta->id }}"
                                               required>
                                        <label class="form-check-label" for="falso_{{ $pregunta->id }}">
                                            Falso
                                        </label>
                                    </div>
                    @else
                        <div class="form-group">
                            <textarea class="form-control" 
                                      name="respuestas[{{ $pregunta->id }}]" 
                                      rows="3" 
                                      required></textarea>
                                </div>
                    @endif
                </div>
                @endforeach

                <div class="text-end">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                        Enviar Evaluación
                            </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal de Confirmación de Salida -->
<div class="modal fade" id="exitModal" tabindex="-1" aria-labelledby="exitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exitModalLabel">¡Advertencia!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Si sales de esta página, no podrás volver a realizar la evaluación y se considerará como finalizada.</p>
                <p>¿Estás seguro de que deseas salir?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="{{ route('evaluaciones.index') }}" class="btn btn-danger" id="confirmExit">Sí, salir</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.timer-container {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
    display: inline-block;
    }

    #timer {
    font-weight: bold;
    color: #dc3545;
    }

.pregunta-container {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const timerElement = document.getElementById('timer');
    if (!timerElement) return;

    const tiempoLimite = parseInt(timerElement.dataset.tiempoLimite);
    const fechaInicio = new Date(timerElement.dataset.fechaInicio);
    const tiempoTranscurrido = parseInt(timerElement.dataset.tiempoTranscurrido);
    
    let tiempoRestante = (tiempoLimite * 60) - tiempoTranscurrido;
    
    function actualizarTimer() {
        const minutos = Math.floor(tiempoRestante / 60);
        const segundos = tiempoRestante % 60;
        
        timerElement.textContent = `${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')}`;
        
        if (tiempoRestante <= 0) {
            document.getElementById('evaluacionForm').submit();
            return;
        }
        
        tiempoRestante--;
    }
    
    // Iniciar el contador
    const timer = setInterval(actualizarTimer, 1000);
    actualizarTimer();

    // Prevenir salida accidental
    window.onbeforeunload = function(e) {
        if (!document.getElementById('evaluacionForm').submitted) {
        e.preventDefault();
            e.returnValue = '';
        }
    };

    // Manejar el envío del formulario
    document.getElementById('evaluacionForm').addEventListener('submit', function(e) {
        this.submitted = true;
    });

    // Mostrar modal al intentar salir
    document.getElementById('confirmExit').addEventListener('click', function(e) {
        window.onbeforeunload = null;
        });
});
</script>
@endpush