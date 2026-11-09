@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">Crear Nuevo Rol</h2>
                    <a href="{{ route('configuracion.roles.index') }}" class="btn btn-light">Volver</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('configuracion.roles.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="name" class="form-label">Nombre del Rol</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <h4>Permisos</h4>
                            <div class="row">
                                @foreach($availablePermissions as $module => $permissions)
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-header bg-secondary text-white">
                                                <h5 class="mb-0 text-capitalize">{{ $module }}</h5>
                                            </div>
                                            <div class="card-body">
                                                @foreach($permissions as $permission => $description)
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" 
                                                               class="form-check-input" 
                                                               id="{{ $module }}_{{ $permission }}"
                                                               name="permissions[]"
                                                               value="{{ $module }}_{{ $permission }}"
                                                               {{ in_array($module.'_'.$permission, old('permissions', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="{{ $module }}_{{ $permission }}">
                                                            {{ $description }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Crear Rol</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-header h5 {
        margin-bottom: 0;
    }
    .form-check-label {
        cursor: pointer;
    }
</style>
@endpush 