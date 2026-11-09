@extends('layouts.app')

@section('title', 'Calificar Evaluación')

@section('content')
<div class="container-fluid">
    <!-- Modal de Errores -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel">Error al Guardar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="errorMessages"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0 text-gray-800">Calificar Evaluación</h1>
                    <p class="text-muted mb-0">
                        {{ $evaluacionUsuario->evaluacion->titulo }} - {{ $evaluacionUsuario->usuario->name }}
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('evaluaciones.show', $evaluacionUsuario->evaluacion) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('evaluaciones.saveGrade', $evaluacionUsuario) }}" method="POST" id="gradeForm">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <!-- Preguntas y Respuestas -->
                @foreach($evaluacionUsuario->respuestas as $respuesta)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pregunta {{ $loop->iteration }}</h5>
                        <span class="badge bg-light text-dark">{{ $respuesta->pregunta->puntaje }} puntos</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6 class="text-muted">Pregunta:</h6>
                            <p>{{ $respuesta->pregunta->pregunta }}</p>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-muted">Respuesta del Estudiante:</h6>
                            @if($respuesta->pregunta->tipo === 'opcion_multiple')
                                <div class="ms-4">
                                    @foreach($respuesta->pregunta->opciones as $opcion)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" disabled
                                               {{ $respuesta->opcion_seleccionada_id === $opcion->id ? 'checked' : '' }}>
                                        <label class="form-check-label {{ $opcion->es_correcta ? 'text-success fw-bold' : '' }}">
                                            {{ $opcion->opcion }}
                                            @if($opcion->es_correcta)
                                                <i class="fas fa-check text-success"></i>
                                            @endif
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            @elseif($respuesta->pregunta->tipo === 'verdadero_falso')
                                <p class="ms-4">{{ ucfirst($respuesta->respuesta_texto) }}</p>
                            @else
                                <div class="card">
                                    <div class="card-body bg-light">
                                        {{ $respuesta->respuesta_texto }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" 
                                           class="form-control puntaje-input" 
                                           name="calificaciones[{{ $respuesta->id }}]" 
                                           placeholder="Puntaje"
                                           min="0" 
                                           max="{{ $respuesta->pregunta->puntaje }}" 
                                           step="0.1"
                                           value="{{ old('calificaciones.' . $respuesta->id, $respuesta->puntaje_obtenido) }}"
                                           required>
                                    <label>Puntaje (máx. {{ $respuesta->pregunta->puntaje }})</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" 
                                              name="retroalimentaciones[{{ $respuesta->id }}]" 
                                              placeholder="Retroalimentación"
                                              style="height: 100px">{{ old('retroalimentaciones.' . $respuesta->id, $respuesta->retroalimentacion) }}</textarea>
                                    <label>Retroalimentación</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="form-floating mb-3">
                            <textarea class="form-control" 
                                      name="retroalimentacion_general" 
                                      placeholder="Retroalimentación general"
                                      style="height: 100px">{{ old('retroalimentacion_general', $evaluacionUsuario->retroalimentacion) }}</textarea>
                            <label>Retroalimentación General</label>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('evaluaciones.show', $evaluacionUsuario->evaluacion) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Calificación
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Resumen y Estadísticas -->
                <div class="card shadow-sm mb-4 sticky-top" style="top: 1rem;">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Resumen de Calificación</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h1 class="display-4" id="calificacion-final">0.0</h1>
                            <p class="text-muted">Calificación Final</p>
                        </div>

                        <div class="progress mb-3" style="height: 20px;">
                            <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 0%;">0%</div>
                        </div>

                        <div class="d-flex justify-content-between text-muted mb-3">
                            <span>Puntaje Obtenido:</span>
                            <span id="puntaje-total">0 de {{ $evaluacionUsuario->evaluacion->getPuntajeTotal() }}</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Pregunta</th>
                                        <th class="text-end">Puntaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($evaluacionUsuario->respuestas as $respuesta)
                                    <tr>
                                        <td>Pregunta {{ $loop->iteration }}</td>
                                        <td class="text-end puntaje-cell">0 / {{ $respuesta->pregunta->puntaje }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .sticky-top {
        z-index: 1020;
    }

    .progress-bar {
        transition: width 0.3s ease;
    }

    .form-check-input:checked[disabled] {
        background-color: #198754;
        border-color: #198754;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('gradeForm');
    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    const errorMessages = document.getElementById('errorMessages');
    const puntajeTotal = {{ $evaluacionUsuario->evaluacion->getPuntajeTotal() }};
    const puntajeInputs = document.querySelectorAll('.puntaje-input');
    const puntajeCells = document.querySelectorAll('.puntaje-cell');
    const progressBar = document.getElementById('progress-bar');
    const calificacionFinal = document.getElementById('calificacion-final');
    const puntajeTotalElement = document.getElementById('puntaje-total');

    function actualizarResumen() {
        let puntajeObtenido = 0;
        
        puntajeInputs.forEach((input, index) => {
            const puntaje = parseFloat(input.value) || 0;
            const maximo = parseFloat(input.max);
            puntajeObtenido += puntaje;
            
            // Actualizar celda en la tabla
            puntajeCells[index].textContent = `${puntaje.toFixed(1)} / ${maximo.toFixed(1)}`;
        });

        // Actualizar barra de progreso
        const porcentaje = (puntajeObtenido / puntajeTotal) * 100;
        progressBar.style.width = `${porcentaje}%`;
        progressBar.textContent = `${porcentaje.toFixed(1)}%`;

        // Actualizar calificación final (sobre 5.0)
        const calificacion = (puntajeObtenido / puntajeTotal) * 5;
        calificacionFinal.textContent = calificacion.toFixed(1);

        // Actualizar puntaje total
        puntajeTotalElement.textContent = `${puntajeObtenido.toFixed(1)} de ${puntajeTotal.toFixed(1)}`;
    }

    // Event listeners para los inputs de puntaje
    puntajeInputs.forEach(input => {
        input.addEventListener('input', actualizarResumen);
    });

    // Manejar el envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Mostrar loading en el botón de submit
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        // Crear FormData
        const formData = new FormData(form);

        // Enviar usando fetch
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 422) {
                // Errores de validación
                let errorHtml = '<ul class="list-unstyled mb-0">';
                Object.values(body.errors || {}).forEach(errors => {
                    errors.forEach(error => {
                        errorHtml += `<li><i class="fas fa-exclamation-circle text-danger"></i> ${error}</li>`;
                    });
                });
                errorHtml += '</ul>';
                errorMessages.innerHTML = errorHtml;
                errorModal.show();
            } else if (status !== 200) {
                // Otros errores
                errorMessages.innerHTML = `<div class="alert alert-danger mb-0">
                    ${body.error || 'Ha ocurrido un error al guardar la calificación.'}
                </div>`;
                errorModal.show();
            } else {
                // Éxito
                window.location.href = body.redirect || form.getAttribute('data-redirect');
            }
        })
        .catch(error => {
            errorMessages.innerHTML = `<div class="alert alert-danger mb-0">
                Error de conexión: ${error.message}
            </div>`;
            errorModal.show();
        })
        .finally(() => {
            // Restaurar el botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });

    // Inicializar resumen
    actualizarResumen();
});
</script>
@endpush 