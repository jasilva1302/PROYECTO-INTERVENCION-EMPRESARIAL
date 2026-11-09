@extends('layouts.app')

@section('title', 'Evaluaciones')

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0 text-gray-800">Evaluaciones</h1>
                </div>
                <div class="col-md-6 text-end">
                    @if($canCreateEvaluaciones)
                    <a href="{{ route('evaluaciones.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Nueva Evaluación
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Evaluaciones -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Listado de Evaluaciones</h5>
        </div>
        <div class="card-body">
            @if($evaluaciones->isEmpty())
                <div class="alert alert-info">
                    No hay evaluaciones disponibles.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Creador</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Límite</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($evaluaciones as $evaluacion)
                            <tr>
                                <td>{{ $evaluacion->titulo }}</td>
                                <td>{{ $evaluacion->creador->name }}</td>
                                <td>{{ $evaluacion->fecha_inicio->format('d/m/Y H:i') }}</td>
                                <td>{{ $evaluacion->fecha_limite->format('d/m/Y H:i') }}</td>
                                <td>
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
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('evaluaciones.show', $evaluacion) }}" 
                                           class="btn btn-info" 
                                           title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @if($canCreateEvaluaciones && $evaluacion->estado !== 'calificada' && !auth()->user()->hasRole('ASISTENTE'))
                                        <a href="{{ route('evaluaciones.edit', $evaluacion) }}" 
                                           class="btn btn-warning" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <button type="button" 
                                                class="btn btn-danger" 
                                                title="Eliminar"
                                                onclick="confirmarEliminacion('{{ $evaluacion->id }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <form id="delete-form-{{ $evaluacion->id }}" 
                                              action="{{ route('evaluaciones.destroy', $evaluacion) }}" 
                                              method="POST" 
                                              style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        @endif

                                        @if(auth()->user()->role && strtoupper(auth()->user()->role->name) === 'ASISTENTE')
                                            @if($evaluacion->isDisponibleParaUsuario(auth()->user()))
                                            <a href="{{ route('evaluaciones.take', $evaluacion) }}" 
                                               class="btn btn-success" 
                                               title="Realizar Evaluación">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $evaluaciones->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table th {
        background-color: #f8f9fa;
    }
    
    .badge {
        font-size: 0.875em;
    }

    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script>
function confirmarEliminacion(evaluacionId) {
    if (confirm('¿Estás seguro de que deseas eliminar esta evaluación?')) {
        document.getElementById('delete-form-' + evaluacionId).submit();
    }
}
</script>
@endpush