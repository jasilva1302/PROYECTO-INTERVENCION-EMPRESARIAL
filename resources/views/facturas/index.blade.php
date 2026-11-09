@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Botón desplegable para exportar -->
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownExportar" data-bs-toggle="dropdown" aria-expanded="false">
            Exportar
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownExportar">
            <li>
                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalFiltrosPendientes">
                    Pendientes por Radicar
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalFiltrosRadicados">
                    Radicados
                </a>
            </li>
        </ul>
    </div>

    <!-- Modal para filtros de facturas pendientes -->
    <div class="modal fade" id="modalFiltrosPendientes" tabindex="-1" aria-labelledby="modalFiltrosPendientesLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFiltrosPendientesLabel">Filtrar Facturas Pendientes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formFiltrosPendientes" action="{{ route('facturas.exportar') }}" method="GET">
                    <div class="modal-body">
                        <input type="hidden" name="estado" value="pendiente">
                        
                        <div class="mb-3">
                            <label for="empresa_id" class="form-label">Empresa</label>
                            <select class="form-select" id="empresa_id" name="empresa_id">
                                <option value="">Todas las empresas</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}">{{ $empresa->razon_social }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sede_id" class="form-label">Sede</label>
                            <select class="form-select" id="sede_id" name="sede_id">
                                <option value="">Todas las sedes</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mes" class="form-label">Mes</label>
                                    <select class="form-select" id="mes" name="mes">
                                        <option value="">Todos los meses</option>
                                        <option value="1">Enero</option>
                                        <option value="2">Febrero</option>
                                        <option value="3">Marzo</option>
                                        <option value="4">Abril</option>
                                        <option value="5">Mayo</option>
                                        <option value="6">Junio</option>
                                        <option value="7">Julio</option>
                                        <option value="8">Agosto</option>
                                        <option value="9">Septiembre</option>
                                        <option value="10">Octubre</option>
                                        <option value="11">Noviembre</option>
                                        <option value="12">Diciembre</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="anio" class="form-label">Año</label>
                                    <select class="form-select" id="anio" name="anio">
                                        <option value="">Todos los años</option>
                                        @for($i = date('Y'); $i >= 2020; $i--)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Exportar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Manejar la carga de sedes
    $('#empresa_id').on('change', function() {
        const empresaId = $(this).val();
        const sedeSelect = $('#sede_id');
        
        sedeSelect.html('<option value="">Todas las sedes</option>');
        
        if (empresaId) {
            $.get(`/api/empresas/${empresaId}/sedes`, function(sedes) {
                sedes.forEach(function(sede) {
                    sedeSelect.append(new Option(sede.sede, sede.id));
                });
            });
        }
    });
});
</script>
@endpush 