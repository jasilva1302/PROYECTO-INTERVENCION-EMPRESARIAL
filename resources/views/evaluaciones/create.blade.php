@extends('layouts.app')

@section('title', 'Crear Nueva Evaluación')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0 text-gray-800">Crear Nueva Evaluación</h1>
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
            <form id="evaluacionForm" action="{{ route('evaluaciones.store') }}" method="POST">
                @csrf

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
                                       value="{{ old('titulo') }}"
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
                                       value="{{ old('fecha_inicio') }}"
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
                                       value="{{ old('fecha_limite') }}"
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
                                          style="height: 100px">{{ old('descripcion') }}</textarea>
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
                                       value="{{ old('tiempo_limite') }}"
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
                                            {{ in_array($asistente->id, old('usuarios', [])) ? 'selected' : '' }}>
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
                        <!-- Las preguntas se agregarán aquí dinámicamente -->
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Evaluación
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

                <div class="col-12 opciones-container" style="display: none;">
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
                       required>
            </div>
            <input type="text" 
                   class="form-control" 
                   name="preguntas[{preguntaIndex}][opciones][{opcionIndex}][opcion]" 
                   placeholder="Texto de la opción"
                   required>
            <button type="button" class="btn btn-outline-danger" onclick="eliminarOpcion(this)">
                <i class="fas fa-times"></i>
            </button>
            <input type="hidden" 
                   name="preguntas[{preguntaIndex}][opciones][{opcionIndex}][es_correcta]" 
                   value="0">
        </div>
    </div>
</template>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--multiple {
        min-height: 38px;
    }
    
    .form-floating > .select2-container {
        margin-top: 24px;
    }

    .pregunta-item {
        position: relative;
    }

    .opcion-item .input-group-text {
        background-color: transparent;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: 'Seleccione los asistentes',
        width: '100%'
    });

    // Agregar primera pregunta automáticamente
    agregarPregunta();

    // Validar formulario antes de enviar
    $('#evaluacionForm').on('submit', function(e) {
        let valid = true;
        
        // Validar que haya al menos una pregunta
        if ($('.pregunta-item').length === 0) {
            alert('Debe agregar al menos una pregunta');
            valid = false;
        }

        // Validar que las preguntas de opción múltiple tengan al menos una opción correcta
        $('.pregunta-item').each(function() {
            const tipo = $(this).find('select[name*="[tipo]"]').val();
            if (tipo === 'opcion_multiple') {
                const opciones = $(this).find('.opcion-item');
                if (opciones.length < 2) {
                    alert('Las preguntas de opción múltiple deben tener al menos 2 opciones');
                    valid = false;
                }
                
                const tieneCorrecta = $(this).find('input[type="radio"]:checked').length > 0;
                if (!tieneCorrecta) {
                    alert('Debe seleccionar una opción correcta para cada pregunta de opción múltiple');
                    valid = false;
                }
            }
        });

        if (!valid) {
            e.preventDefault();
        }
    });
});

let preguntaCounter = 0;

function agregarPregunta() {
    const container = document.getElementById('preguntas-container');
    const template = document.getElementById('pregunta-template');
    const preguntaNode = template.content.cloneNode(true);
    
    preguntaCounter++;
    
    // Actualizar índices y números
    preguntaNode.querySelector('.numero-pregunta').textContent = preguntaCounter;
    preguntaNode.querySelectorAll('[name*="{index}"]').forEach(el => {
        el.name = el.name.replace('{index}', preguntaCounter - 1);
    });

    container.appendChild(preguntaNode);
}

function eliminarPregunta(btn) {
    if (confirm('¿Estás seguro de que deseas eliminar esta pregunta?')) {
        btn.closest('.pregunta-item').remove();
        actualizarNumeracionPreguntas();
    }
}

function actualizarNumeracionPreguntas() {
    document.querySelectorAll('.numero-pregunta').forEach((el, index) => {
        el.textContent = index + 1;
    });
}

function toggleOpcionesPregunta(select) {
    const opcionesContainer = select.closest('.pregunta-item').querySelector('.opciones-container');
    const opcionesList = opcionesContainer.querySelector('.opciones-list');
    
    if (select.value === 'opcion_multiple') {
        opcionesContainer.style.display = 'block';
        if (opcionesList.children.length === 0) {
            // Agregar dos opciones por defecto
            agregarOpcion(opcionesContainer.querySelector('.btn-info'));
            agregarOpcion(opcionesContainer.querySelector('.btn-info'));
        }
    } else {
        opcionesContainer.style.display = 'none';
        opcionesList.innerHTML = ''; // Limpiar opciones
    }
}

function agregarOpcion(btn) {
    const preguntaItem = btn.closest('.pregunta-item');
    const opcionesList = preguntaItem.querySelector('.opciones-list');
    const template = document.getElementById('opcion-template');
    const opcionNode = template.content.cloneNode(true);
    
    const preguntaIndex = Array.from(preguntaItem.parentNode.children).indexOf(preguntaItem);
    const opcionIndex = opcionesList.children.length;
    
    // Actualizar índices
    opcionNode.querySelectorAll('[name*="{preguntaIndex}"]').forEach(el => {
        el.name = el.name.replace('{preguntaIndex}', preguntaIndex);
    });
    opcionNode.querySelectorAll('[name*="{opcionIndex}"]').forEach(el => {
        el.name = el.name.replace('{opcionIndex}', opcionIndex);
    });

    // Asegurar que los radio buttons estén en el mismo grupo
    opcionNode.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.name = `pregunta_${preguntaIndex}_opcion_correcta`;
    });

    opcionesList.appendChild(opcionNode);
}

function eliminarOpcion(btn) {
    const opcionItem = btn.closest('.opcion-item');
    const opcionesList = opcionItem.parentNode;
    
    if (opcionesList.children.length > 2) {
        opcionItem.remove();
    } else {
        alert('Debe haber al menos dos opciones');
    }
}
</script>
@endpush