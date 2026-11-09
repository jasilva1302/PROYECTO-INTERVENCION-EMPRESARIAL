@extends('layouts.app')

@section('title', 'Cargar Facturación')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-file-upload"></i> Cargar Facturación</h2>
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-custom text-white">
            <h5 class="mb-0">Cargar Archivo de Facturación</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('facturacion.cargue.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label for="mes" class="form-label">Mes de Facturación</label>
                    <input type="month" 
                           class="form-control @error('mes') is-invalid @enderror" 
                           id="mes" 
                           name="mes" 
                           required>
                    @error('mes')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="archivo" class="form-label">Archivo de Facturación</label>
                    <input type="file" 
                           class="form-control @error('archivo') is-invalid @enderror" 
                           id="archivo" 
                           name="archivo" 
                           accept=".xlsx,.xls,.csv,.txt" 
                           required>
                    @error('archivo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                    <div class="form-text">
                        Formatos permitidos: .xlsx, .xls, .csv, .txt
                        <a href="{{ route('facturacion.cargue.template.download') }}" class="btn btn-sm btn-outline-custom ms-2">
                            <i class="fas fa-download"></i> Descargar Plantilla
                        </a>
                    </div>
                </div>

                <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Instrucciones</h6>
                    <p class="mb-2">El archivo Excel debe contener las siguientes columnas (el orden no importa):</p>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <ul class="mb-0 list-unstyled">
                                <li><strong>Campos Obligatorios:</strong></li>
                                <li class="text-danger">• Empresa</li>
                                <li class="text-danger">• Sede</li>
                                <li class="text-danger">• Factura</li>
                                <li class="text-danger">• Aseguradora</li>
                            </ul>
                            <ul class="mb-0 list-unstyled mt-3">
                                <li><strong>Datos Generales:</strong></li>
                                <li>• Departamento</li>
                                <li>• Tipo</li>
                                <li>• Identificación</li>
                                <li>• Paciente</li>
                                <li>• Tipo Cliente</li>
                                <li>• Documento Aseguradora</li>
                                <li>• Plan</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul class="mb-0 list-unstyled">
                                <li><strong>Datos del Servicio:</strong></li>
                                <li>• Número de Cuenta</li>
                                <li>• CUPS del Servicio</li>
                                <li>• Cargo del Servicio</li>
                                <li>• Descripción del Servicio</li>
                                <li>• Cantidad</li>
                                <li>• Autorización del Servicio</li>
                                <li>• Valor Unitario Servicio</li>
                                <li>• Valor Total Servicio</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul class="mb-0 list-unstyled">
                                <li><strong>Datos de Facturación:</strong></li>
                                <li>• Fecha Documento</li>
                                <li>• Prefijo Documento</li>
                                <li>• No. Documento</li>
                                <li>• Periodo Facturación Inicio</li>
                                <li>• Periodo Facturación Final</li>
                                <li>• Contabilización Documento</li>
                                <li>• Interface Documento</li>
                                <li>• Adquiriente</li>
                                <li>• Total Documento</li>
                                <li>• Saldo</li>
                            </ul>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <ul class="mb-0 list-unstyled">
                                <li><strong>Datos DIAN:</strong></li>
                                <li>• Estado del Documento DIAN</li>
                                <li>• Estado Transacción DIAN</li>
                                <li>• Fecha Validación DIAN</li>
                                <li>• Resultado DIAN</li>
                                <li>• CUFE</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="mb-0 list-unstyled">
                                <li><strong>Datos de Radicación:</strong></li>
                                <li>• Fecha Radicación</li>
                                <li>• Número Radicación</li>
                                <li>• Usuario</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('facturacion.cargue.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-custom">
                        <i class="fas fa-upload"></i> Cargar Archivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    .bg-custom {
        background-color: #ff6b00;
    }
    .btn-custom {
        background-color: #ff6b00;
        color: white;
    }
    .btn-custom:hover {
        background-color: #e65100;
        color: white;
    }
    .btn-outline-custom {
        color: #ff6b00;
        border-color: #ff6b00;
    }
    .btn-outline-custom:hover {
        background-color: #ff6b00;
        color: white;
    }
    .alert-info ul li {
        margin-bottom: 0.25rem;
    }
    .text-danger {
        color: #dc3545 !important;
    }
</style>
@endpush

@endsection 