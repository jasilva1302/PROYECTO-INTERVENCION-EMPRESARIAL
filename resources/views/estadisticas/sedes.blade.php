@extends('layouts.app')

@section('title', 'Estadísticas por Sede')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <a href="{{ route('estadisticas.asistentes') }}" class="btn btn-outline-primary">
                <i class="fas fa-chevron-left"></i> Estadísticas de Asistentes
            </a>
            <h1 class="mb-0 text-center">Estadísticas por Sede</h1>
            <a href="{{ route('estadisticas.asistentes') }}" class="btn btn-outline-primary">
                Estadísticas de Asistentes <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Errores por Sede</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height: 400px;">
                <canvas id="erroresSedeChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Errores más Comunes</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height: 400px;">
                <canvas id="erroresComunesChart"></canvas>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Errores por Sede Chart
        const erroresSedeCtx = document.getElementById('erroresSedeChart').getContext('2d');
        const erroresSedeChart = new Chart(erroresSedeCtx, {
            type: 'bar',
            data: {
                labels: @json($sedes->pluck('sede')),
                datasets: [{
                    label: 'Total auditorías',
                    data: @json($sedes->pluck('total')),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Hallazgos encontrados',
                    data: @json($sedes->pluck('hallazgos')),
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
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
                            text: 'Cantidad'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Sedes'
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

        // Errores Comunes Chart
        const erroresComunesCtx = document.getElementById('erroresComunesChart').getContext('2d');
        const erroresComunesChart = new Chart(erroresComunesCtx, {
            type: 'pie',
            data: {
                labels: @json($hallazgosComunes->pluck('observacion')),
                datasets: [{
                    data: @json($hallazgosComunes->pluck('total')),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
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