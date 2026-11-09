@extends('layouts.app')

@section('title', 'Editar Evaluación')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0 text-gray-800">Editar Evaluación</h1>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('evaluaciones.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form id="evaluacionForm" action="{{ route('evaluaciones.update', $evaluacion) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Información General -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2">Información General</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" 
                                       class="form-control @error('titulo') is-invalid @enderror" 
                                       id="titulo" 
                                       name="titulo" 
                                       placeholder="Título de la evaluación"
                                       value="{{ old('titulo', $evaluacion->titulo) }}"
                                       required>
                                <label for="titulo">Título</label>
                                @error('titulo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="datetime-local" 
                                       class="form-control @error('fecha_inicio') is-invalid @enderror" 
                                       id="fecha_inicio" 
                                       name="fecha_inicio"
                                       value="{{ old('fecha_inicio', $evaluacion->fecha_inicio->format('Y-m-d\TH:i')) }}"
                                       required>
                                <label for="fecha_inicio">Fecha de Inicio</label>
                                @error('fecha_inicio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="datetime-local" 
                                       class="form-control @error('fecha_limite') is-invalid @enderror" 
                                       id="fecha_limite" 
                                       name="fecha_limite"
                                       value="{{ old('fecha_limite', $evaluacion->fecha_limite->format('Y-m-d\TH:i')) }}"
                                       required>
                                <label for="fecha_limite">Fecha Límite</label>
                                @error('fecha_limite')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-floating">
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                          id="descripcion" 
                                          name="descripcion" 
                                          placeholder="Descripción"
                                          style="height: 100px">{{ old('descripcion', $evaluacion->descripcion) }}</textarea>
                                <label for="descripcion">Descripción</label>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" 
                                       class="form-control @error('tiempo_limite') is-invalid @enderror" 
                                       id="tiempo_limite" 
                                       name="tiempo_limite"
                                       placeholder="Tiempo límite en minutos"
                                       value="{{ old('tiempo_limite', $evaluacion->tiempo_limite) }}"
                                       min="1">
                                <label for="tiempo_limite">Tiempo Límite (minutos)</label>
                                @error('tiempo_limite')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asignación de Usuarios -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2">Asignar a Asistentes</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <select class="form-select select2 @error('usuarios') is-invalid @enderror" 
                                    name="usuarios[]" 
                                    multiple 
                                    required>
                                @foreach($asistentes as $asistente)
                                    <option value="{{ $asistente->id }}" 
                                            {{ in_array($asistente->id, old('usuarios', $evaluacion->usuarios->pluck('id')->toArray())) ? 'selected' : '' }}>
                                        {{ $asistente->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('usuarios')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Preguntas -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h5 class="mb-0">Preguntas</h5>
                        <button type="button" class="btn btn-success btn-sm" onclick="agregarPregunta()">
                            <i class="fas fa-plus"></i> Agregar Pregunta
                        </button>
                    </div>
                    
                    <div id="preguntas-container">
                        <!-- Las preguntas existentes se cargarán aquí -->
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template para preguntas -->
<template id="pregunta-template">
    <div class="pregunta-item card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="card-title mb-0">Pregunta #<span class="numero-pregunta"></span></h6>
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarPregunta(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-12">
                    <div class="form-floating">
                        <textarea class="form-control" 
                                 name="preguntas[{index}][pregunta]" 
                                 placeholder="Texto de la pregunta"
                                 required></textarea>
                        <label>Texto de la Pregunta</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" 
                                name="preguntas[{index}][tipo]" 
                                onchange="toggleOpcionesPregunta(this)"
                                required>
                            <option value="">Seleccione un tipo</option>
                            <option value="opcion_multiple">Opción Múltiple</option>
                            <option value="texto_libre">Texto Libre</option>
                            <option value="verdadero_falso">Verdadero/Falso</option>
                        </select>
                        <label>Tipo de Pregunta</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="number" 
                               class="form-control" 
                               name="preguntas[{index}][puntaje]" 
                               placeholder="Puntaje"
                               value="1"
                               min="1"
                               required>
                        <label>Puntaje</label>
                    </div>
                </div>

                <div class="col-md-12 opciones-container" style="display: none;">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Opciones de Respuesta</h6>
                                <button type="button" class="btn btn-info btn-sm" onclick="agregarOpcion(this)">
                                    <i class="fas fa-plus"></i> Agregar Opción
                                </button>
                            </div>
                            <div class="opciones-list">
                                <!-- Las opciones se agregarán aquí -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Template para opciones -->
<template id="opcion-template">
    <div class="opcion-item mb-2">
        <div class="input-group">
            <div class="input-group-text">
                <input type="radio" 
                       name="preguntas[{preguntaIndex}][opciones][{opcionIndex}][es_correcta]" 
                       value="1"
                       onchange="updateOpcionesCorrectness(this)">
            </div>
            <input type="text" 
                   class="form-control" 
                   name="preguntas[{preguntaIndex}][opciones][{opcionIndex}][opcion]" 
                   placeholder="Texto de la opción"
                   required>
            <input type="hidden" 
                   name="preguntas[{preguntaIndex}][opciones][{opcionIndex}][es_correcta]" 
                   value="0">
            <button type="button" class="btn btn-outline-danger" onclick="eliminarOpcion(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</template>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Cargar preguntas existentes
        const preguntas = @json($evaluacion->preguntas);
        preguntas.forEach(pregunta => {
            const preguntaElement = agregarPregunta();
            
            // Llenar los campos de la pregunta
            preguntaElement.querySelector('textarea[name$="[pregunta]"]').value = pregunta.pregunta;
            preguntaElement.querySelector('select[name$="[tipo]"]').value = pregunta.tipo;
            preguntaElement.querySelector('input[name$="[puntaje]"]').value = pregunta.puntaje;
            
            // Si es opción múltiple, cargar las opciones
            if (pregunta.tipo === 'opcion_multiple') {
                const select = preguntaElement.querySelector('select[name$="[tipo]"]');
                toggleOpcionesPregunta(select);
                
                const opcionesContainer = preguntaElement.querySelector('.opciones-list');
                pregunta.opciones.forEach(opcion => {
                    const opcionElement = agregarOpcion(opcionesContainer.closest('.opciones-container').querySelector('button'));
                    opcionElement.querySelector('input[type="text"]').value = opcion.opcion;
                    if (opcion.es_correcta) {
                        const radio = opcionElement.querySelector('input[type="radio"]');
                        radio.checked = true;
                        updateOpcionesCorrectness(radio);
                    }
                });
            }
        });
    });

    // Resto del código JavaScript igual que en create.blade.php
    function agregarPregunta() {
        const template = document.getElementById('pregunta-template');
        const container = document.getElementById('preguntas-container');
        const preguntasCount = container.children.length;
        
        const clone = template.content.cloneNode(true);
        const preguntaElement = clone.querySelector('.pregunta-item');
        
        // Actualizar índices
        preguntaElement.innerHTML = preguntaElement.innerHTML.replace(/{index}/g, preguntasCount);
        preguntaElement.querySelector('.numero-pregunta').textContent = preguntasCount + 1;
        
        container.appendChild(preguntaElement);
        return preguntaElement;
    }

    function eliminarPregunta(button) {
        const pregunta = button.closest('.pregunta-item');
        pregunta.remove();
        actualizarNumerosPreguntas();
    }

    function actualizarNumerosPreguntas() {
        const preguntas = document.querySelectorAll('.pregunta-item');
        preguntas.forEach((pregunta, index) => {
            pregunta.querySelector('.numero-pregunta').textContent = index + 1;
            actualizarIndicesPregunta(pregunta, index);
        });
    }

    function actualizarIndicesPregunta(pregunta, index) {
        const inputs = pregunta.querySelectorAll('[name*="preguntas["]');
        inputs.forEach(input => {
            input.name = input.name.replace(/preguntas\[\d+\]/, `preguntas[${index}]`);
        });
    }

    function toggleOpcionesPregunta(select) {
        const opcionesContainer = select.closest('.pregunta-item').querySelector('.opciones-container');
        if (select.value === 'opcion_multiple') {
            opcionesContainer.style.display = 'block';
            if (opcionesContainer.querySelector('.opcion-item') === null) {
                agregarOpcion(opcionesContainer.querySelector('button'));
                agregarOpcion(opcionesContainer.querySelector('button'));
            }
        } else {
            opcionesContainer.style.display = 'none';
        }
    }

    function agregarOpcion(button) {
        const preguntaItem = button.closest('.pregunta-item');
        const opcionesList = preguntaItem.querySelector('.opciones-list');
        const preguntaIndex = Array.from(preguntaItem.parentNode.children).indexOf(preguntaItem);
        const opcionIndex = opcionesList.children.length;
        
        const template = document.getElementById('opcion-template');
        const clone = template.content.cloneNode(true);
        const opcionElement = clone.querySelector('.opcion-item');
        
        opcionElement.innerHTML = opcionElement.innerHTML
            .replace(/{preguntaIndex}/g, preguntaIndex)
            .replace(/{opcionIndex}/g, opcionIndex);
        
        opcionesList.appendChild(opcionElement);
        return opcionElement;
    }

    function eliminarOpcion(button) {
        const opcion = button.closest('.opcion-item');
        const opcionesList = opcion.closest('.opciones-list');
        opcion.remove();
        
        if (opcionesList.children.length < 2) {
            agregarOpcion(opcionesList.closest('.opciones-container').querySelector('button'));
            agregarOpcion(opcionesList.closest('.opciones-container').querySelector('button'));
        }
    }

    function updateOpcionesCorrectness(radio) {
        const opcionesList = radio.closest('.opciones-list');
        const radios = opcionesList.querySelectorAll('input[type="radio"]');
        const hiddenInputs = opcionesList.querySelectorAll('input[type="hidden"]');
        
        radios.forEach((r, index) => {
            hiddenInputs[index].value = r.checked ? "1" : "0";
        });
    }
</script>
@endpush 