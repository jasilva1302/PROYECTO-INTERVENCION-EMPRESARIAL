@extends('layouts.app')

@section('title', 'Estadísticas de Asistentes')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <a href="{{ route('estadisticas.sedes') }}" class="btn btn-outline-primary">
                <i class="fas fa-chevron-left"></i> Estadísticas por Sede
            </a>
            <h1 class="mb-0 text-center">Estadísticas de Asistentes</h1>
            <a href="{{ route('estadisticas.sedes') }}" class="btn btn-outline-primary">
                Estadísticas por Sede <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Productividad</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height: 400px;">
                <canvas id="productividadChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Tiempos Promedio</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height: 400px;">
                <canvas id="tiemposChart"></canvas>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Productividad Chart
        const productividadCtx = document.getElementById('productividadChart').getContext('2d');
        const productividadChart = new Chart(productividadCtx, {
            type: 'bar',
            data: {
                labels: @json($asistentes->pluck('name')),
                datasets: [
                    {
                        label: 'Auditorías completadas',
                        data: @json($asistentes->pluck('total_audits')),
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Hallazgos encontrados',
                        data: @json($asistentes->pluck('hallazgos_count')),
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Asistentes'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw;
                            }
                        }
                    }
                }
            }
        });

        // Tiempos Chart
        const tiemposCtx = document.getElementById('tiemposChart').getContext('2d');
        const tiemposChart = new Chart(tiemposCtx, {
            type: 'bar',
            data: {
                labels: @json($asistentes->pluck('name')),
                datasets: [{
                    label: 'Tiempo promedio por auditoría (minutos)',
                    data: @json($asistentes->pluck('tiempo_promedio')),
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Minutos'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Asistentes'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + ' minutos';
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
@endsection