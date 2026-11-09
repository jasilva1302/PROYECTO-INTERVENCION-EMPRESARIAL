@extends('layouts.app')

@push('styles')
<style>
    #auditoria-tarifas-container {
        padding: 20px;
    }

    #auditoria-tarifas-container .content-header {
        padding: 1rem 0;
        border-bottom: 1px solid #e5e9f2;
        margin-bottom: 2rem;
    }

    #auditoria-table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    #auditoria-filter-row {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 8px 8px 0 0;
        margin-bottom: 0;
    }

    #auditoria-estado-filter {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 4px 12px;
        font-size: 0.875rem;
        width: 200px;
        background-color: white;
    }

    #auditoria-estado-filter:focus {
        border-color: #2196F3;
        box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.25);
        outline: none;
    }

    #auditoria-table {
        margin: 0;
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
    }

    #auditoria-table thead th {
        background-color: #4a4a4a !important;
        color: white !important;
        font-weight: 500;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 0.75rem;
        vertical-align: middle;
        border: none !important;
    }

    #auditoria-table tbody td {
        padding: 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
        color: #444;
        font-size: 0.875rem;
    }

    #auditoria-table tr.estado-correcto {
        background-color: #90EE90 !important;
    }

    #auditoria-table tr.estado-incorrecto {
        background-color: #FFE5B4 !important;
    }

    #auditoria-table tr:hover {
        filter: brightness(0.95);
    }

    #auditoria-table .badge {
        font-weight: 500;
        padding: 0.5em 0.75em;
        border-radius: 4px;
    }

    #auditoria-table .badge-correcto {
        background-color: #28a745;
        color: white;
    }

    #auditoria-table .badge-sin-tarifa {
        background-color: #ffc107;
        color: #000;
    }

    #auditoria-table .badge-diferencia {
        background-color: #dc3545;
        color: white;
    }

    #auditoria-back-btn {
        color: #2196F3;
        background-color: transparent;
        border: 1px solid #2196F3;
        border-radius: 4px;
        padding: 0.375rem 1rem;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    #auditoria-back-btn:hover {
        color: white;
        background-color: #2196F3;
    }

    #auditoria-pagination {
        margin: 1rem 0 0 0;
        justify-content: flex-end;
    }

    #auditoria-pagination .page-link {
        padding: 0.375rem 0.75rem;
        color: #2196F3;
        background-color: white;
        border: 1px solid #dee2e6;
    }

    #auditoria-pagination .page-item.active .page-link {
        background-color: #2196F3;
        border-color: #2196F3;
        color: white;
    }

    #auditoria-pagination .page-link:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
        color: #0056b3;
    }

    #auditoria-info {
        color: #6c757d;
        font-size: 0.875rem;
    }

    .auditoria-money {
        text-align: right;
        font-family: monospace;
    }
</style>
@endpush

@section('content')
<div id="auditoria-tarifas-container" class="container-fluid">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Auditoría de Tarifas</h1>
            <a href="{{ route('tarifas.index') }}" id="auditoria-back-btn">
                <i class="fas fa-arrow-left me-2"></i>Volver a Tarifas
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div id="auditoria-table-container">
        <div id="auditoria-filter-row">
            <div class="d-flex align-items-center">
                <label for="auditoria-estado-filter" class="me-2">Filtrar por Estado:</label>
                <select id="auditoria-estado-filter">
                    <option value="">Todos los estados</option>
                    <option value="TARIFA CORRECTA">Tarifa Correcta</option>
                    <option value="SIN TARIFA CONFIGURADA">Sin Tarifa Configurada</option>
                    <option value="DIFERENCIA DE TARIFAS">Diferencia de Tarifas</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table id="auditoria-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Sede</th>
                        <th>Factura</th>
                        <th>Aseguradora</th>
                        <th>Régimen</th>
                        <th>Descripción Servicio</th>
                        <th>CUPS</th>
                        <th class="text-end">Valor Facturado</th>
                        <th class="text-end">Tarifa Base</th>
                        <th>Fecha Facturación</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($auditorias as $auditoria)
                        <tr class="{{ 
                            $auditoria->estado === 'TARIFA CORRECTA' ? 'estado-correcto' : 
                            ($auditoria->estado !== 'TARIFA CORRECTA' ? 'estado-incorrecto' : '')
                        }}">
                            <td>{{ $auditoria->empresa }}</td>
                            <td>{{ $auditoria->sede }}</td>
                            <td>{{ $auditoria->factura }}</td>
                            <td>{{ $auditoria->aseguradora }}</td>
                            <td>{{ $auditoria->regimen }}</td>
                            <td>{{ $auditoria->descripcion_servicio }}</td>
                            <td>{{ $auditoria->cups_servicio }}</td>
                            <td class="auditoria-money">${{ number_format($auditoria->valor_unitario_servicio, 2) }}</td>
                            <td class="auditoria-money">${{ $auditoria->tarifa_valor ? number_format($auditoria->tarifa_valor, 2) : 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($auditoria->periodo_facturacion_inicio)->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge {{ 
                                    $auditoria->estado === 'TARIFA CORRECTA' ? 'badge-correcto' : 
                                    ($auditoria->estado === 'SIN TARIFA CONFIGURADA' ? 'badge-sin-tarifa' : 'badge-diferencia')
                                }}">
                                    {{ $auditoria->estado }}
                                </span>
                            </td>
                            <td>{{ $auditoria->observaciones }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div id="auditoria-info">
                    Mostrando {{ $auditorias->firstItem() ?? 0 }} a {{ $auditorias->lastItem() ?? 0 }} de {{ $auditorias->total() ?? 0 }} registros
                </div>
                <div id="auditoria-pagination">
                    {{ $auditorias->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const estadoFilter = document.getElementById('auditoria-estado-filter');

    function filterTable() {
        const selectedEstado = estadoFilter.value.toLowerCase();
        const rows = document.querySelectorAll('#auditoria-table tbody tr');
        let visibleCount = 0;
        const totalRows = rows.length;

        rows.forEach(row => {
            const estadoCell = row.querySelector('td:nth-child(11)');
            const estado = estadoCell.textContent.trim().toLowerCase();
            
            if (!selectedEstado || estado.includes(selectedEstado)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.querySelector('#auditoria-info').textContent = 
            `Mostrando ${visibleCount} de ${totalRows} registros`;
    }

    estadoFilter.addEventListener('change', filterTable);
});
</script>
@endpush 