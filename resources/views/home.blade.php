@extends('layouts.app')

@section('title', 'Inicio - Nefrotrack')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- Módulos del Sistema -->
            <h2 class="mb-4 text-dark"><i class="fas fa-th-large"></i> Módulos del Sistema</h2>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                @foreach($modules as $module)
                    <div class="col">
                        <a href="{{ route($module['route']) }}" class="text-decoration-none module-link">
                            <div class="card h-100 module-card" style="background: {{ $module['gradient'] }}">
                                <div class="card-body text-center text-white">
                                    <div class="icon-container mb-3">
                                        <i class="{{ $module['icon'] }} module-icon"></i>
                                    </div>
                                    <h3 class="card-title">{{ $module['name'] }}</h3>
                                    <p class="card-text">{{ $module['description'] }}</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.module-card {
    border: none;
    border-radius: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    position: relative;
    min-height: 250px;
}

.module-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(-100%);
    transition: transform 0.5s ease;
}

.module-link:hover .module-card::before {
    transform: translateX(0);
}

.module-link:hover .module-card {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.icon-container {
    background: rgba(255, 255, 255, 0.2);
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    transition: all 0.3s ease;
}

.module-link:hover .icon-container {
    transform: scale(1.1) rotate(5deg);
    background: rgba(255, 255, 255, 0.3);
}

.module-icon {
    font-size: 2.5rem;
    transition: all 0.3s ease;
}

.module-link:hover .module-icon {
    transform: scale(1.1);
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
}

.card-text {
    font-size: 1rem;
    opacity: 0.9;
    line-height: 1.5;
}

.module-card .card-body {
    padding: 2rem;
    z-index: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100%;
}

/* Animación de entrada */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.col {
    animation: fadeInUp 0.5s ease forwards;
}

.col:nth-child(1) { animation-delay: 0.1s; }
.col:nth-child(2) { animation-delay: 0.2s; }
.col:nth-child(3) { animation-delay: 0.3s; }
.col:nth-child(4) { animation-delay: 0.4s; }
.col:nth-child(5) { animation-delay: 0.5s; }
.col:nth-child(6) { animation-delay: 0.6s; }
</style>
@endpush