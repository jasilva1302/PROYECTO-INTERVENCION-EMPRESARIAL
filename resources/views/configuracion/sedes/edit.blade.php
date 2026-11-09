@extends('layouts.app')

@section('title', 'Editar Sede')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-hospital"></i> Editar Sede</h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('configuracion.update-sede', $sede) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="empresa_id" class="form-label">Empresa</label>
                    <select class="form-select @error('empresa_id') is-invalid @enderror" 
                            id="empresa_id" 
                            name="empresa_id" 
                            required>
                        <option value="">Seleccione una empresa</option>
                        @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}" 
                                    {{ (old('empresa_id', $sede->empresa_id) == $empresa->id) ? 'selected' : '' }}>
                                {{ $empresa->razon_social }} ({{ $empresa->nit }})
                            </option>
                        @endforeach
                    </select>
                    @error('empresa_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="codigo_habilitacion" class="form-label">Código de Habilitación</label>
                    <input type="text" 
                           class="form-control @error('codigo_habilitacion') is-invalid @enderror" 
                           id="codigo_habilitacion" 
                           name="codigo_habilitacion" 
                           value="{{ old('codigo_habilitacion', $sede->codigo_habilitacion) }}" 
                           required>
                    @error('codigo_habilitacion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="sede" class="form-label">Nombre de la Sede</label>
                    <input type="text" 
                           class="form-control @error('sede') is-invalid @enderror" 
                           id="sede" 
                           name="sede" 
                           value="{{ old('sede', $sede->sede) }}" 
                           required>
                    @error('sede')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('configuracion.index') }}" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-custom">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    :root {
        --custom-orange: #ff6b00;
        --custom-orange-dark: #e65100;
    }

    .btn-custom {
        background-color: var(--custom-orange);
        color: white;
    }

    .btn-custom:hover {
        background-color: var(--custom-orange-dark);
        color: white;
    }
</style>
@endpush

@endsection 