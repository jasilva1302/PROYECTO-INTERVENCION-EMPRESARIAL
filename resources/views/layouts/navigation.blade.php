<nav class="navbar navbar-expand-md navbar-dark bg-orange shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'Nefrotrack') }}" height="40">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav me-auto">
                @auth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    
                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('Ver Auditorías'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('auditoria.*') ? 'active' : '' }}" href="{{ route('auditoria.index') }}">
                            <i class="fas fa-clipboard-check"></i> Auditorías
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('Ver facturación'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('facturacion.*') ? 'active' : '' }}" href="{{ route('facturacion.cargue.index') }}">
                            <i class="fas fa-file-invoice"></i> Facturación
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('Ver estadísticas'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('estadisticas.*') ? 'active' : '' }}" href="{{ route('estadisticas.index') }}">
                            <i class="fas fa-chart-bar"></i> Estadísticas
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('Ver tarifas'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tarifas.*') ? 'active' : '' }}" href="{{ route('tarifas.index') }}">
                            <i class="fas fa-dollar-sign"></i> Tarifas
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('Ver evaluaciones'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('evaluaciones.*') ? 'active' : '' }}" 
                           href="{{ route('evaluaciones.index') }}">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Evaluaciones</span>
                        </a>
                    </li>
                    @endif

                    <!-- Módulo de Radicación -->
                    @if(auth()->user()->hasRole('Administrador'))
                        <li class="nav-item">
                            <a href="{{ route('radicacion.index') }}" class="nav-link {{ request()->routeIs('radicacion.*') ? 'active' : '' }}">
                                <i class="fas fa-file-invoice"></i> Radicación
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- Authentication Links -->
                @guest
                    @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                    @endif

                    @if (Route::has('register'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                        </li>
                    @endif
                @else
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            <i class="fas fa-user"></i> {{ Auth::user()->name }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('ver Configuración'))
                            <a class="dropdown-item" href="{{ route('configuracion.index') }}">
                                <i class="fas fa-cog"></i> {{ __('Configuración') }}
                            </a>
                            <div class="dropdown-divider"></div>
                            @endif
                            
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                             document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i> {{ __('Logout') }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

<style>
.bg-orange {
    background: linear-gradient(135deg, #f97316 0%, #c2410c 100%) !important;
}

.navbar {
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.navbar-brand {
    font-weight: 500;
}

.navbar-brand img {
    max-height: 40px;
    width: auto;
}

.nav-link {
    padding: 0.5rem 1rem;
    color: rgba(255, 255, 255, 0.9) !important;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out;
}

.nav-link:hover {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.15);
    border-radius: 0.25rem;
}

.nav-link.active {
    color: #fff !important;
    font-weight: 500;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 0.25rem;
}

.nav-link i {
    margin-right: 0.5rem;
    width: 1.25rem;
    text-align: center;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    color: #212529;
}

.dropdown-item:hover {
    background-color: rgba(249, 115, 22, 0.1);
}

.dropdown-item i {
    margin-right: 0.5rem;
    width: 1rem;
    text-align: center;
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-top-color: #dee2e6;
}

.navbar-toggler {
    border-color: rgba(255, 255, 255, 0.5);
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

@media (max-width: 767.98px) {
    .navbar-nav {
        padding: 0.5rem 0;
    }
    
    .dropdown-menu {
        border: none;
        padding: 0;
        margin: 0;
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .dropdown-item {
        padding: 0.5rem 1.5rem;
        color: rgba(255, 255, 255, 0.9);
    }

    .dropdown-item:hover {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.2);
    }

    .nav-link:hover, .nav-link.active {
        background-color: rgba(255, 255, 255, 0.1);
    }
}
</style> 