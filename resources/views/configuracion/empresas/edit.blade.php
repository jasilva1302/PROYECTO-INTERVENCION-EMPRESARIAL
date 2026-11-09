@extends('layouts.app')

@section('title', 'Editar Empresa')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-building"></i> Editar Empresa</h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('configuracion.update-empresa', $empresa) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="nit" class="form-label">NIT</label>
                    <input type="text" 
                           class="form-control @error('nit') is-invalid @enderror" 
                           id="nit" 
                           name="nit" 
                           value="{{ old('nit', $empresa->nit) }}" 
                           required>
                    @error('nit')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="razon_social" class="form-label">Razón Social</label>
                    <input type="text" 
                           class="form-control @error('razon_social') is-invalid @enderror" 
                           id="razon_social" 
                           name="razon_social" 
                           value="{{ old('razon_social', $empresa->razon_social) }}" 
                           required>
                    @error('razon_social')
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