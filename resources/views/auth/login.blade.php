@extends('layouts.app', ['bodyClass' => 'login-page'])

@section('title', 'Acceso al Sistema - Nefrouros')

@push('styles')
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
    <style>
        /* Asegurar que el body ocupe toda la pantalla */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
@endpush

@section('content')
<div class="login-container">
    <div class="login-header">
        <div class="login-logo">
            <img src="{{ asset('images/nefrouros-logo.png') }}" alt="Nefrouros Logo">
        </div>
        <p>Ingrese sus credenciales para acceder al sistema</p>
    </div>

    <div class="login-card">
        <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf

            <div class="form-group">
                <label for="email">{{ __('Correo Electrónico') }}</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                       name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                       placeholder="Ingrese su correo institucional">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">{{ __('Contraseña') }}</label>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                       name="password" required autocomplete="current-password"
                       placeholder="Ingrese su contraseña">
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-login">
                    {{ __('Iniciar Sesión') }}
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <div class="login-footer">
                <p>¿Problemas para acceder? <a href="#">Contacte al administrador</a></p>
                <p class="copyright">© {{ date('Y') }} Nefrouros. Todos los derechos reservados.</p>
            </div>
        </form>
    </div>
</div>
@endsection