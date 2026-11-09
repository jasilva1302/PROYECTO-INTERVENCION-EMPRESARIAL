@extends('layouts.app')

@section('title', 'Realizando Auditoría')

@section('content')
@php
    // Definir las variables globales que necesitamos en toda la vista con valores por defecto seguros
    $currentPdfIndex = $currentPdfIndex ?? 0;
    $auditFactura = null;
    $hallazgos = collect();
    $isFacturaAuditada = false;
    $currentFacturaPdfFiles = [];
    $currentFacturaId = null;
    $currentFacturaNumero = null;
    $currentFacturaAseguradora = null;
    $currentFacturaSaldo = null;

    // Verificar que $currentFactura existe y tiene los datos necesarios
    if (isset($currentFactura) && !is_null($currentFactura)) {
        $currentFacturaId = $currentFactura->id ?? null;
        $currentFacturaNumero = $currentFactura->factura ?? 'N/A';
        $currentFacturaAseguradora = $currentFactura->aseguradora ?? 'N/A';
        $currentFacturaSaldo = $currentFactura->saldo ?? 0;
        $currentFacturaPdfFiles = $currentFactura->pdfFiles ?? [];
        
        // Manejar auditFacturas como array o como colección
        if (isset($currentFactura->auditFacturas)) {
            if (is_array($currentFactura->auditFacturas)) {
                $auditFactura = collect($currentFactura->auditFacturas)->first();
            } else {
                $auditFactura = $currentFactura->auditFacturas->first();
            }
            
            if ($auditFactura) {
                // Manejar hallazgos como array o como colección
                if (isset($auditFactura['hallazgos'])) {
                    $hallazgos = collect($auditFactura['hallazgos']);
                } elseif (isset($auditFactura->hallazgos)) {
                    $hallazgos = collect($auditFactura->hallazgos);
                }
                $isFacturaAuditada = $hallazgos->count() > 0;
            }
        }
    }

    // Asegurarse de que $facturas sea una colección
    $facturas = isset($facturas) ? collect($facturas) : collect();
    
    // Asegurarse de que $audit existe y tiene los datos necesarios
    $auditId = $audit->id ?? null;
    $auditSede = $audit->sede ?? 'N/A';
    $auditMonth = $audit->month ?? 'N/A';
    $auditYear = $audit->year ?? 'N/A';
    $auditLink = $audit->link ?? '#';
    $auditStartedAt = $audit->started_at ?? now();
    $sedeName = $audit->sede->sede ?? 'N/A';
@endphp

@if(!empty($debug_info))
    <div class="alert alert-info mt-4">
        <h4>Información de Depuración</h4>
        <pre>{{ json_encode($debug_info, JSON_PRETTY_PRINT) }}</pre>
    </div>
@endif

@push('styles')
<style>
    /* Estilos del Navbar */
    .navbar {
        padding: 0.25rem 1rem !important;
        min-height: 40px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .navbar-brand {
        font-size: 1rem;
        padding: 0;
    }

    .nav-link {
        padding: 0.25rem 0.75rem !important;
        font-size: 0.875rem;
    }

    /* Ajuste del breadcrumb y navegación */
    .breadcrumb {
        padding: 0.25rem 0;
        margin-bottom: 0;
        font-size: 0.875rem;
        background: transparent;
    }

    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        font-size: 1rem;
        line-height: 1;
        vertical-align: -1px;
    }

    .audit-info {
        font-size: 0.8125rem;
        color: #6c757d;
    }

    .audit-info a {
        color: #e67e22;
        text-decoration: none;
    }

    .audit-info a:hover {
        text-decoration: underline;
    }

    /* Estilos del Timer y Progreso */
    .timer-container, .progress-container {
        background: linear-gradient(145deg, #ffffff, #f5f5f5);
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 
            -8px -8px 15px rgba(255,255,255,0.7),
            8px 8px 15px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex: 1;
    }

    .timer-icon, .progress-icon {
        background: linear-gradient(145deg, #f0f0f0, #e6e6e6);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 
            inset 2px 2px 5px rgba(255,255,255,0.7),
            inset -2px -2px 5px rgba(0,0,0,0.05);
    }

    .timer-icon i, .progress-icon i {
        font-size: 1.8rem;
        background: linear-gradient(45deg, #2c3e50, #3498db);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .timer-content, .progress-content {
        flex: 1;
    }

    .timer-label, .progress-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #7f8c8d;
        margin-bottom: 0.25rem;
    }

    .timer-display {
        font-size: 2.5rem;
        font-weight: 700;
        font-family: 'Roboto Mono', monospace;
        color: #2c3e50;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        margin: 0;
        line-height: 1;
    }

    .progress-display {
        font-size: 1.8rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .progress-bar-container {
        width: 100%;
        height: 10px;
        background: #ecf0f1;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #3498db, #2ecc71);
        border-radius: 10px;
        transition: width 0.5s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Botón de terminar auditoría */
    .btn-terminar-auditoria {
        background: linear-gradient(145deg, #2ecc71, #27ae60);
        border: none;
        padding: 1rem 2rem;
        color: white;
        border-radius: 15px;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 
            -4px -4px 10px rgba(255,255,255,0.8),
            4px 4px 10px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 200px;
        justify-content: center;
    }

    .btn-terminar-auditoria:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 
            -6px -6px 15px rgba(255,255,255,0.8),
            6px 6px 15px rgba(0,0,0,0.3);
        background: linear-gradient(145deg, #27ae60, #219a52);
    }

    .btn-terminar-auditoria:disabled {
        background: linear-gradient(145deg, #bdc3c7, #95a5a6);
        cursor: not-allowed;
        transform: none;
    }

    .btn-terminar-auditoria i {
        font-size: 1.2rem;
    }

    /* Contenedor principal */
    .audit-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    @media (max-width: 1200px) {
        .audit-header {
            flex-direction: column;
        }
        
        .timer-container, .progress-container {
            width: 100%;
        }
        
        .btn-terminar-auditoria {
            width: 100%;
        }
    }

    /* Animación del timer */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .timer-icon i {
        animation: pulse 2s infinite;
    }

    /* Estilos para el botón de pausar auditoría */
    .btn-pausar-auditoria {
        background: linear-gradient(145deg, #f39c12, #e67e22);
        border: none;
        padding: 1rem 2rem;
        color: white;
        border-radius: 15px;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 
            -4px -4px 10px rgba(255,255,255,0.8),
            4px 4px 10px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 200px;
        justify-content: center;
    }

    .btn-pausar-auditoria:hover {
        transform: translateY(-2px);
        box-shadow: 
            -6px -6px 15px rgba(255,255,255,0.8),
            6px 6px 15px rgba(0,0,0,0.3);
        background: linear-gradient(145deg, #e67e22, #d35400);
    }

    /* Ajuste de los márgenes superiores */
    .container-fluid {
        padding-top: 0.5rem;
    }

    .row.mb-2 {
        margin-bottom: 0.5rem !important;
    }

    /* Reset de estilos para la tabla */
    .facturas-table {
        all: initial;
        font-family: inherit;
        font-size: 0.875rem;
    }

    /* Contenedor principal */
    .table-wrapper {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        overflow: hidden;
        background: white;
        height: calc(100vh - 180px);
        overflow-y: auto;
    }

    /* Estilos de la tabla */
    .table-wrapper table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
    }

    /* Encabezados */
    .table-wrapper th {
        background: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        padding: 0.5rem;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid #e67e22;
        font-size: 0.875rem;
    }

    /* Encabezado de aseguradora */
    .table-wrapper .aseguradora-header {
        background-color: #fff3e0;
        font-weight: 500;
        color: #e67e22;
    }

    .table-wrapper .aseguradora-header td {
        padding: 0.5rem;
        font-size: 0.8rem;
        border-bottom: 1px solid #ffddb3;
        border-top: 1px solid #ffddb3;
    }

    /* Filas de facturas */
    .table-wrapper tr:not(.aseguradora-header) {
        transition: all 0.3s ease;
    }

    /* Celdas */
    .table-wrapper td {
        padding: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    /* Hover en las filas */
    .table-wrapper tr:not(.aseguradora-header):hover {
        background-color: #fff3e0;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(230, 126, 34, 0.2);
    }

    /* Fila activa */
    .table-wrapper tr.active {
        background-color: #ffe0b2;
        border-left: 4px solid #e67e22;
    }

    /* Número de factura */
    .table-wrapper .factura-numero {
        color: #2c3e50;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    /* Hover en número de factura */
    .table-wrapper tr:hover .factura-numero {
        color: #e67e22;
        transform: scale(1.05);
    }

    /* Badges más compactos */
    .table-wrapper .badge {
        padding: 0.25rem 0.5rem;
        border-radius: 50rem;
        font-weight: 500;
        font-size: 0.75rem;
    }

    .table-wrapper .badge-pdf {
        background-color: #e67e22;
        color: white;
    }

    .table-wrapper .badge-saldo {
        background-color: #2c3e50;
        color: white;
    }

    /* Facturas auditadas */
    .table-wrapper tr.audited:not(.aseguradora-header) {
        background-color: rgba(40, 167, 69, 0.1);
    }

    .table-wrapper tr.audited:not(.aseguradora-header):hover {
        background-color: rgba(40, 167, 69, 0.15);
    }

    .table-wrapper tr.audited.active:not(.aseguradora-header) {
        background-color: rgba(40, 167, 69, 0.2);
        border-left: 4px solid #28a745;
    }

    /* Indentación para las facturas */
    .table-wrapper td.ps-4 {
        padding-left: 2rem !important;
    }

    /* Scrollbar personalizado */
    .table-container::-webkit-scrollbar {
        width: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f8f9fa;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #e67e22;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #d35400;
    }

    /* PDF Container y navegación */
    .pdf-container {
        margin-top: 0.5rem;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        overflow: hidden;
        background: white;
        height: calc(100vh - 250px);
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .pdf-container iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    /* Estilos para el modo pantalla completa */
    .fullscreen-pdf {
        flex: 1;
        height: 100%;
        background: #1a1a1a;
        position: relative;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
    }

    .pdf-wrapper {
        width: 95%;
        height: 95%;
        background: white;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }

    .pdf-wrapper iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .control-btn {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.3);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .control-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }

    .control-btn:not(:disabled):hover {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.5);
    }

    .pdf-controls {
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 0.75rem;
        background: rgba(44,62,80,0.9);
        padding: 0.75rem;
        border-radius: 8px;
        z-index: 10;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    /* Estilos para el modo pantalla completa */
    .fullscreen-viewer {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: #1a1a1a;
        z-index: 9999;
        display: none;
    }

    .fullscreen-viewer.active {
        display: flex;
        flex-direction: column;
    }

    .fullscreen-controls {
        height: 60px;
        background: #2c3e50;
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .fullscreen-content {
        display: flex;
        height: calc(100vh - 60px);
        position: relative;
    }

    /* Panel de facturas mejorado */
    .fullscreen-facturas {
        width: 300px;
        background: #2c3e50;
        height: 100%;
        overflow-y: auto;
        border-right: 1px solid rgba(255,255,255,0.1);
        transition: transform 0.3s ease;
    }

    .fullscreen-facturas.collapsed {
        transform: translateX(-300px);
    }

    .facturas-header {
        padding: 1rem;
        color: white;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .facturas-list {
        padding: 0.5rem;
    }

    .factura-item {
        padding: 0.75rem;
        background: rgba(255,255,255,0.05);
        border-radius: 4px;
        margin-bottom: 0.5rem;
        cursor: pointer;
        color: white;
        transition: all 0.2s ease;
    }

    .factura-item:hover {
        background: rgba(255,255,255,0.1);
        transform: translateX(5px);
    }

    .factura-item.active {
        background: #e67e22;
    }

    .aseguradora-header {
        color: #e67e22;
        font-weight: 500;
        padding: 0.5rem;
        margin-top: 1rem;
        border-bottom: 1px solid rgba(230,126,34,0.3);
    }

    /* Panel de observaciones mejorado */
    .fullscreen-sidebar {
        width: 400px;
        background: #2c3e50;
        height: 100%;
        overflow-y: auto;
        transition: transform 0.3s ease;
        border-left: 1px solid rgba(255,255,255,0.1);
    }

    .fullscreen-sidebar.collapsed {
        transform: translateX(400px);
    }

    .sidebar-header {
        padding: 1rem;
        color: white;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-content {
        padding: 1rem;
    }

    /* Formulario de observaciones */
    .observation-form {
        background: rgba(255,255,255,0.05);
        padding: 1rem;
        border-radius: 4px;
        margin-top: 1rem;
    }

    .observation-form textarea {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
        resize: vertical;
        min-height: 80px;
    }

    .observation-form textarea:focus {
        background: rgba(255,255,255,0.15);
        border-color: rgba(255,255,255,0.3);
        color: white;
        box-shadow: none;
    }

    .observation-form .btn-primary {
        background: #e67e22;
        border: none;
        transition: all 0.2s ease;
    }

    .observation-form .btn-primary:hover {
        background: #d35400;
        transform: translateY(-1px);
    }

    .hallazgo-item {
        background: rgba(255,255,255,0.05);
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        color: white;
    }

    .hallazgo-item small {
        display: block;
        margin-top: 0.5rem;
        color: rgba(255,255,255,0.7);
    }

    .pdf-wrapper {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #fff;
    }

    .pdf-wrapper iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .btn-outline-light {
        transition: all 0.2s ease;
    }

    .btn-outline-light:hover {
        background: rgba(255,255,255,0.2);
    }

    .pdf-counter {
        font-size: 0.9rem;
        padding: 0.375rem 0.75rem;
        background: rgba(255,255,255,0.1);
        border-radius: 4px;
        min-width: 200px;
        text-align: center;
    }

    .fullscreen-controls button:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }

    .fullscreen-controls button {
        transition: all 0.2s ease;
    }

    .fullscreen-controls button:not(:disabled):hover {
        background: rgba(255,255,255,0.2);
        transform: translateY(-1px);
    }

    /* Estilos para el visor de PDF */
    .pdf-container {
        flex: 1;
        position: relative;
        background-color: #525659;
        min-height: 0;
        height: calc(100vh - 200px);
    }
    
    .pdf-iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }

    /* Pantalla dividida */
    .split-screen-viewer {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: #1a1a1a;
        z-index: 9999;
        display: none;
        flex-direction: column;
    }

    .split-screen-viewer.active {
        display: flex;
    }

    .split-screen-controls {
        height: 60px;
        background: #2c3e50;
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .split-screen-content {
        flex: 1;
        display: flex;
        position: relative;
        overflow: hidden;
    }

    .split-facturas-panel {
        width: 300px;
        background: #2c3e50;
        height: 100%;
        overflow-y: auto;
        border-right: 1px solid rgba(255,255,255,0.1);
        transition: transform 0.3s ease;
    }

    .split-facturas-panel.collapsed {
        transform: translateX(-300px);
    }

    .split-main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .split-pdf-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
        position: relative;
        background: #2c3e50;
    }

    .split-pdf-controls {
        height: 40px;
        background: rgba(44,62,80,0.9);
        padding: 0 1rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1;
    }

    .split-pdf-wrapper {
        flex: 1;
        margin: 40px 10px 10px;
        background: white;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
        position: relative;
    }

    .split-pdf-wrapper iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .split-divider {
        height: 4px;
        background: #2c3e50;
        cursor: row-resize;
        position: relative;
    }

    .split-divider::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 50px;
        height: 4px;
        background: rgba(255,255,255,0.3);
        border-radius: 2px;
    }

    .split-sidebar {
        width: 400px;
        background: #2c3e50;
        height: 100%;
        overflow-y: auto;
        border-left: 1px solid rgba(255,255,255,0.1);
        transition: transform 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .split-sidebar.collapsed {
        transform: translateX(400px);
    }

    /* Ajustes para los controles de PDF */
    .pdf-counter {
        min-width: 150px;
        text-align: center;
        color: white;
        font-size: 0.9rem;
    }

    .btn-outline-light {
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
    }

    .btn-outline-light:hover {
        background: rgba(255,255,255,0.2);
    }

    .btn-outline-light:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    /* Estilos para los controles del visor PDF */
    .pdf-viewer-header {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        background: rgba(44,62,80,0.95);
        padding: 0.5rem 1rem;
        z-index: 100;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .pdf-viewer-title {
        color: white;
        font-size: 0.9rem;
        margin: 0;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 0 1rem;
    }

    .pdf-viewer-controls {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .pdf-navigation {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pdf-counter {
        color: rgba(255,255,255,0.8);
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
        background: rgba(255,255,255,0.1);
        border-radius: 4px;
        min-width: 100px;
        text-align: center;
    }

    /* Ajustes para el modo pantalla completa */
    .fullscreen-header {
        height: 60px;
        background: #2c3e50;
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    /* Ajustes para el modo pantalla dividida */
    .split-viewer-header {
        height: 50px;
        background: rgba(44,62,80,0.95);
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .split-pdf-container {
        position: relative;
        padding-top: 50px; /* Altura del header */
    }

    /* Ajuste del contenedor principal de PDF */
    .pdf-container {
        position: relative;
        padding-top: 50px; /* Altura del header */
    }

    .pdf-iframe {
        position: absolute;
        top: 50px; /* Altura del header */
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: calc(100% - 50px);
        border: none;
    }

    /* Estilos para los botones */
    .btn-viewer {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }

    .btn-viewer:hover {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.3);
        color: white;
    }

    .btn-viewer:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-viewer i {
        font-size: 0.875rem;
    }

    /* Ajustes específicos para cada modo */
    .normal-view .pdf-viewer-header,
    .fullscreen-view .pdf-viewer-header,
    .split-view .split-viewer-header {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Ajustes adicionales para los títulos de PDF */
    .pdf-viewer-title {
        color: white;
        font-size: 0.9rem;
        margin: 0;
        padding: 0.5rem 1rem;
        background: rgba(0,0,0,0.2);
        border-radius: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 50%;
        flex: 1;
    }

    .split-viewer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 1rem;
        background: rgba(44,62,80,0.95);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .split-pdf-container {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .split-pdf-wrapper {
        flex: 1;
        position: relative;
        margin: 0.5rem;
        background: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .pdf-iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }

    /* Estilos para el panel de hallazgos */
    .split-sidebar {
        width: 400px;
        background: #2c3e50;
        height: 100%;
        overflow-y: auto;
        border-left: 1px solid rgba(255,255,255,0.1);
        transition: transform 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        padding: 1rem;
        background: rgba(0,0,0,0.2);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-header h6 {
        margin: 0;
        color: white;
        font-size: 1rem;
    }

    .sidebar-header small {
        font-size: 0.8rem;
        opacity: 0.7;
    }

    .sidebar-content {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .hallazgo-item {
        background: rgba(255,255,255,0.05);
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        color: white;
    }

    .hallazgo-item p {
        margin: 0 0 0.5rem 0;
    }

    .hallazgo-item small {
        display: block;
        color: rgba(255,255,255,0.7);
        font-size: 0.8rem;
    }

    .observation-form {
        margin-top: 1rem;
        background: rgba(255,255,255,0.05);
        padding: 1rem;
        border-radius: 4px;
    }

    .observation-form label {
        display: block;
        margin-bottom: 0.5rem;
        color: rgba(255,255,255,0.9);
    }

    .observation-form textarea {
        width: 100%;
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
        border-radius: 4px;
        padding: 0.5rem;
        margin-bottom: 1rem;
    }

    .observation-form textarea:focus {
        background: rgba(255,255,255,0.15);
        border-color: rgba(255,255,255,0.3);
        outline: none;
    }

    .observation-form .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }

    .alert {
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="audit-header">
        <div class="timer-container">
            <div class="timer-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="timer-content">
                <div class="timer-label">Tiempo de Auditoría</div>
                <div id="timer" data-start="{{ $auditStartedAt }}" class="timer-display">00:00:00</div>
            </div>
        </div>
        
        <div class="progress-container">
            <div class="progress-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="progress-content">
                <div class="progress-label">Progreso de Auditoría</div>
                <div id="progresoAuditoria" class="progress-display">Verificando...</div>
                <div class="progress-bar-container">
                    <div id="progressBar" class="progress-bar" style="width: 0%"></div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <button id="btnPausarAuditoria" class="btn-pausar-auditoria">
                <i class="fas fa-pause"></i>
                Pausar Auditoría
            </button>
            
            <button id="btnTerminarAuditoria" class="btn-terminar-auditoria" disabled title="Verificando facturas...">
                <i class="fas fa-check-circle"></i>
                Terminar Auditoría
            </button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('auditoria.index') }}" class="text-dark text-decoration-none">Auditorías</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('auditoria.show', ['audit' => $auditId]) }}" class="text-dark text-decoration-none">
                            {{ $sedeName }} - {{ $auditMonth }} {{ $auditYear }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Realizando Auditoría</li>
                </ol>
            </nav>
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 150px;">Sede:</th>
                                    <td>{{ $sedeName }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Período:</th>
                                    <td>{{ $auditMonth }} {{ $auditYear }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Documentos:</th>
                                    <td>
                                        <a href="{{ $auditLink }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-external-link-alt"></i> Acceder al servidor
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-header py-2">
                    <h5 class="mb-0">Facturas</h5>
                </div>
                <div class="card-body p-0" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                    <div class="list-group">
                        @forelse($facturas as $factura)
                            @php
                                $facturaId = $factura->id ?? null;
                                $facturaNumero = $factura->factura ?? 'N/A';
                                $facturaAseguradora = $factura->aseguradora ?? 'N/A';
                                $facturaSaldo = $factura->saldo ?? 0;
                                $isActive = $currentFacturaId && $facturaId && $currentFacturaId === $facturaId;
                                $hasPdfs = isset($factura->pdfFiles) && count($factura->pdfFiles) > 0;
                            @endphp
                            <a href="{{ route('auditoria.start', ['audit' => $auditId, 'factura_id' => $facturaId]) }}"
                               class="list-group-item list-group-item-action {{ $isActive ? 'active' : '' }} {{ !$hasPdfs ? 'list-group-item-danger' : '' }}"
                               style="border-radius: 0;">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $facturaNumero }}</h6>
                                    @if($hasPdfs)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> PDFs OK
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-triangle"></i> Sin PDFs
                                        </span>
                                    @endif
                                </div>
                                <small>{{ $facturaAseguradora }}</small>
                                <br>
                                <small>Saldo: ${{ number_format($facturaSaldo, 2) }}</small>
                                @if(!$hasPdfs)
                                    <div class="alert alert-danger mt-2 mb-0 py-1 px-2">
                                        <small>No se encontraron PDFs para esta factura</small>
                                    </div>
                                @endif
                            </a>
                        @empty
                            <div class="alert alert-info m-3">
                                No hay facturas disponibles para auditar.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-10">
            @if($currentFacturaId)
                <div class="card h-100">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Factura: {{ $currentFacturaNumero }} - {{ $currentFacturaAseguradora }}
                        </h5>
                        @if($currentFacturaSaldo > 0)
                            <span class="badge bg-secondary">
                                ${{ number_format($currentFacturaSaldo, 2) }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="card-body p-2">
                        @if(count($currentFacturaPdfFiles) > 0)
                            <div class="pdf-controls">
                                <div class="pdf-controls-inner">
                                    <div class="pdf-navigation">
                                        <button class="btn btn-secondary" {{ $currentPdfIndex <= 0 ? 'disabled' : '' }} onclick="prevPdf()">
                                            <i class="fas fa-chevron-left"></i> Ant
                                        </button>
                                        
                                        <div class="pdf-counter">
                                            <span class="fw-bold">Documento {{ $currentPdfIndex + 1 }} de {{ count($currentFacturaPdfFiles) }}</span>
                                        </div>

                                        <button class="btn btn-secondary" {{ $currentPdfIndex >= count($currentFacturaPdfFiles) - 1 ? 'disabled' : '' }} onclick="nextPdf()">
                                        Siguiente <i class="fas fa-chevron-right"></i>
                                        </button>
                                </div>

                                    <div class="pdf-actions">
                                        <button class="btn btn-primary" onclick="toggleSplitScreen()">
                                            <i class="fas fa-columns"></i> Pantalla Dividida
                                        </button>
                                        <button class="btn btn-primary" onclick="toggleFullscreen()">
                                    <i class="fas fa-expand"></i> Pantalla Completa
                                </button>
                                    </div>
                                </div>
                            </div>

                            <div class="pdf-container">
                                <iframe 
                                    id="mainPdfViewer"
                                    class="pdf-iframe" 
                                    src="{{ $currentFacturaPdfFiles[$currentPdfIndex]['url'] }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH"
                                    allowfullscreen>
                                </iframe>
                                    </div>

                            <!-- Visor Pantalla Dividida -->
                            <div class="split-screen-viewer" id="splitScreenViewer">
                                <div class="split-screen-controls">
                                    <div class="pdf-viewer-controls">
                                        <button class="btn-viewer" onclick="toggleSplitScreen()">
                                            <i class="fas fa-compress"></i> Salir
                                        </button>
                                        <button class="btn-viewer" onclick="toggleSplitFacturasList()">
                                            <i class="fas fa-list"></i> Facturas
                                        </button>
                                        <button class="btn-viewer" onclick="toggleSplitSidebar()">
                                            <i class="fas fa-clipboard-list"></i> Hallazgos
                                        </button>
                            </div>
                            </div>

                                <div class="split-screen-content">
                                    <!-- Panel de Facturas -->
                                    <div class="split-facturas-panel" id="splitFacturasPanel">
                                        <div class="facturas-header">
                                            <h6 class="mb-0">Listado de Facturas</h6>
                                        </div>
                                        <div class="facturas-list">
                                            @foreach($facturas->groupBy('aseguradora') as $aseguradora => $facturasAseguradora)
                                                <div class="aseguradora-header">{{ $aseguradora ?? 'Sin Aseguradora' }}</div>
                                                @foreach($facturasAseguradora as $factura)
                                                    @php
                                                        $facturaId = $factura->id ?? null;
                                                        $facturaNumero = $factura->factura ?? 'N/A';
                                                        $facturaSaldo = $factura->saldo ?? 0;
                                                        $isActive = $currentFacturaId && $facturaId && $currentFacturaId === $facturaId;
                                                    @endphp
                                                    <div class="factura-item {{ $isActive ? 'active' : '' }}"
                                                         data-id="{{ $facturaId }}"
                                                         onclick="loadFacturaSplit('{{ $facturaId }}', '{{ $facturaNumero }}', '{{ $aseguradora }}')">
                                                        <div class="d-flex justify-content-between">
                                                            <span>{{ $facturaNumero }}</span>
                                                            <span class="badge bg-primary">
                                                                {{ isset($factura->pdfFiles) ? count($factura->pdfFiles) : 0 }}
                                                            </span>
                                                        </div>
                                                        @if($facturaSaldo > 0)
                                                            <div class="mt-1">
                                                                <small class="text-warning">${{ number_format($facturaSaldo, 2) }}</small>
                                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="split-main-content">
                                        <!-- Visor Superior -->
                                        <div class="split-pdf-container">
                                            <div class="split-viewer-header">
                                                <div class="pdf-viewer-controls">
                                                    <button class="btn-viewer" onclick="prevPdfTop()" id="prevTopBtn">
                                                        <i class="fas fa-chevron-left"></i> Anterior
                                                    </button>
                                                    <div class="pdf-counter" id="topPdfCounter">
                                                        1 / {{ count($currentFacturaPdfFiles) }}
                                                    </div>
                                                    <button class="btn-viewer" onclick="nextPdfTop()" id="nextTopBtn">
                                                        Siguiente <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                </div>
                                                <div class="pdf-viewer-title" id="topPdfTitle">
                                                    @if(isset($currentFacturaPdfFiles[$currentPdfIndex]))
                                                        {{ $currentFacturaPdfFiles[$currentPdfIndex]['name'] }}
                                                        @if(isset($currentFacturaPdfFiles[$currentPdfIndex]['original_name']))
                                                            ({{ $currentFacturaPdfFiles[$currentPdfIndex]['original_name'] }})
                                                        @endif
                            @else
                                                        Sin documento
                            @endif
                                                </div>
                                            </div>
                                            <div class="split-pdf-wrapper">
                                                <iframe id="splitScreenTopViewer" 
                                                        class="pdf-iframe"
                                                        src="{{ isset($currentFacturaPdfFiles[$currentPdfIndex]) ? $currentFacturaPdfFiles[$currentPdfIndex]['url'] : '' }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH">
                                                </iframe>
                                            </div>
                        </div>

                                        <div class="split-divider"></div>
                                        
                                        <!-- Visor Inferior -->
                                        <div class="split-pdf-container">
                                            <div class="split-viewer-header">
                                                <div class="pdf-viewer-controls">
                                                    <button class="btn-viewer" onclick="prevPdfBottom()" id="prevBottomBtn">
                                                        <i class="fas fa-chevron-left"></i> Anterior
                                                    </button>
                                                    <div class="pdf-counter" id="bottomPdfCounter">
                                                        1 / {{ count($currentFacturaPdfFiles) }}
                                </div>
                                                    <button class="btn-viewer" onclick="nextPdfBottom()" id="nextBottomBtn">
                                                        Siguiente <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                                                <div class="pdf-viewer-title" id="bottomPdfTitle">
                                                    @if(isset($currentFacturaPdfFiles[$currentPdfIndex]))
                                                        {{ $currentFacturaPdfFiles[$currentPdfIndex]['name'] }}
                                                        @if(isset($currentFacturaPdfFiles[$currentPdfIndex]['original_name']))
                                                            ({{ $currentFacturaPdfFiles[$currentPdfIndex]['original_name'] }})
                                                        @endif
                                                    @else
                                                        Sin documento
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="split-pdf-wrapper">
                                                <iframe id="splitScreenBottomViewer" 
                                                        class="pdf-iframe"
                                                        src="{{ isset($currentFacturaPdfFiles[$currentPdfIndex]) ? $currentFacturaPdfFiles[$currentPdfIndex]['url'] : '' }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH">
                                                </iframe>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Panel de Hallazgos -->
                                    <div class="split-sidebar" id="splitObservacionesPanel">
                                        <div class="sidebar-header">
                                            <h6 class="mb-0">Hallazgos y Observaciones</h6>
                                        </div>
                                        <div class="sidebar-content">
                                            <div id="splitHallazgosList">
                                                <div class="alert alert-info">
                                                    Seleccione una factura para ver sus hallazgos
                                                </div>
                                </div>
                                
                                            <div id="splitScreenFormContainer">
                                                <!-- El formulario se cargará dinámicamente aquí -->
                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning m-3">
                                No se encontraron documentos PDF para esta factura
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    Selecciona una factura para comenzar la auditoría.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Visor Pantalla Completa -->
<div class="fullscreen-viewer" id="fullscreenViewer">
    <div class="fullscreen-controls">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-light btn-sm" onclick="toggleFullscreen()">
                <i class="fas fa-compress"></i> Salir
            </button>
            <button class="btn btn-outline-light btn-sm" onclick="toggleFacturasList()">
                <i class="fas fa-list"></i> Facturas
            </button>
            <button class="btn btn-outline-light btn-sm" onclick="toggleSidebar()">
                <i class="fas fa-clipboard-list"></i> Hallazgos
            </button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-light btn-sm me-2" onclick="prevPdfFullscreen()">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>
            <span class="text-white pdf-counter">
                @if($currentFacturaId)
                    {{ $currentFacturaNumero }} - 
                    Documento {{ $currentPdfIndex + 1 }} de {{ count($currentFacturaPdfFiles) }}
                @endif
            </span>
            <button class="btn btn-outline-light btn-sm ms-2" onclick="nextPdfFullscreen()">
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <div class="fullscreen-content">
        <div class="fullscreen-facturas" id="facturasPanel">
            <div class="facturas-header">
                <h6 class="mb-0">Listado de Facturas</h6>
            </div>
            <div class="facturas-list">
                @foreach($facturas->groupBy('aseguradora') as $aseguradora => $facturasAseguradora)
                    <div class="aseguradora-header">{{ $aseguradora ?? 'Sin Aseguradora' }}</div>
                    @foreach($facturasAseguradora as $factura)
                        @php
                            $facturaId = $factura->id ?? null;
                            $facturaNumero = $factura->factura ?? 'N/A';
                            $facturaSaldo = $factura->saldo ?? 0;
                            $isActive = $currentFacturaId && $facturaId && $currentFacturaId === $facturaId;
                        @endphp
                        <div class="factura-item {{ $isActive ? 'active' : '' }}"
                             data-id="{{ $facturaId }}"
                             onclick="loadFacturaFullscreen('{{ $facturaId }}', '{{ $facturaNumero }}', '{{ $aseguradora }}')">
                            <div class="d-flex justify-content-between">
                                <span>{{ $facturaNumero }}</span>
                                <span class="badge bg-primary">
                                    {{ isset($factura->pdfFiles) ? count($factura->pdfFiles) : 0 }}
                                </span>
                            </div>
                            @if($facturaSaldo > 0)
                                <div class="mt-1">
                                    <small class="text-warning">${{ number_format($facturaSaldo, 2) }}</small>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="fullscreen-pdf">
            <div class="pdf-wrapper">
                @if($currentFacturaId && isset($currentFacturaPdfFiles[$currentPdfIndex]['url']))
                    <iframe id="fullscreenPdfViewer" 
                            width="100%" 
                            height="100%" 
                            frameborder="0"
                            src="{{ $currentFacturaPdfFiles[$currentPdfIndex]['url'] }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH"></iframe>
                @else
                    <div class="alert alert-warning">
                        No hay PDF disponible para mostrar.
                    </div>
                @endif
            </div>
        </div>

        <div class="fullscreen-sidebar" id="observacionesPanel">
            <div class="sidebar-header">
                <h6 class="mb-0">Hallazgos y Observaciones</h6>
            </div>
            <div class="sidebar-content">
                <div id="hallazgosList">
                    @if($hallazgos->count() > 0)
                        @foreach($hallazgos as $hallazgo)
                            <div class="hallazgo-item">
                                <p>{{ $hallazgo['observacion'] ?? $hallazgo->observacion ?? 'N/A' }}</p>
                                @if(isset($hallazgo['documento_referencia']) || isset($hallazgo->documento_referencia))
                                    <small>
                                        Documento: 
                                        {{ $hallazgo['documento_referencia'] ?? $hallazgo->documento_referencia }}
                                    </small>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info">No hay hallazgos registrados</div>
                    @endif
                </div>

                @if($currentFacturaId && !$isFacturaAuditada)
                    <form method="POST" 
                          action="/auditoria/${currentAuditId}/${currentFacturaId}/observacion"
                          id="fullscreenObservationForm" 
                          class="observation-form"
                          data-factura-id="${currentFacturaId}">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                        <input type="hidden" 
                               name="current_pdf" 
                               value="${currentPdfFiles[currentFullscreenPdfIndex]?.name || ''}" />
                        
                        <div class="form-group">
                            <label class="text-white">Nueva Observación</label>
                            <textarea class="form-control" name="observacion" rows="3" required></textarea>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-save"></i> Guardar Observación
                            </button>
                            <button type="button" class="btn btn-success flex-grow-1" onclick="submitOk(this)">
                                <i class="fas fa-check"></i> OK
                            </button>
                        </div>
                    </form>
                @elseif($currentFacturaId && $isFacturaAuditada)
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> FACTURA YA AUDITADA
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Variables globales para el timer
let timerInterval;
let startTime;
let accumulatedTime = {{ $audit->tiempo_acumulado ?? 0 }}; // Cargar tiempo acumulado de la base de datos
let isTimerRunning = false;
let lastStoredTime = 0;

// Función para formatear el tiempo en formato HH:MM:SS
function formatTime(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

// Función para iniciar el timer
function startTimer() {
    if (isTimerRunning) return;
    
    console.log('Iniciando timer...'); // Debug
    isTimerRunning = true;
    startTime = new Date();
    
    // Recuperar tiempo acumulado de localStorage o usar el de la base de datos
    const savedTime = localStorage.getItem('auditTime_{{ $audit->id }}');
    accumulatedTime = savedTime ? parseInt(savedTime, 10) : {{ $audit->tiempo_acumulado ?? 0 }};
    
    console.log('Tiempo acumulado inicial:', accumulatedTime); // Debug
    
    // Actualizar el display inicial
    const timerElement = document.getElementById('timer');
    if (timerElement) {
        timerElement.textContent = formatTime(accumulatedTime);
    }
    
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
}

// Función para detener el timer
function stopTimer() {
    if (!isTimerRunning) return;
    
    clearInterval(timerInterval);
    isTimerRunning = false;
    
    // Calcular tiempo transcurrido en esta sesión
    const currentTime = new Date();
    const sessionTime = Math.floor((currentTime - startTime) / 1000);
    
    // Actualizar tiempo acumulado
    accumulatedTime += sessionTime;
    
    // Guardar en localStorage
    localStorage.setItem('auditTime_{{ $audit->id }}', accumulatedTime.toString());
    console.log('Timer detenido. Tiempo acumulado:', accumulatedTime); // Debug
}

// Función para actualizar el display del timer
function updateTimer() {
    const timerElement = document.getElementById('timer');
    if (!timerElement || !isTimerRunning) return;
    
    const currentTime = new Date();
    const sessionTime = Math.floor((currentTime - startTime) / 1000);
    const totalTime = accumulatedTime + sessionTime;
    
    timerElement.textContent = formatTime(totalTime);
    
    // Guardar cada 10 segundos
    if (totalTime % 10 === 0 && totalTime !== lastStoredTime) {
        localStorage.setItem('auditTime_{{ $audit->id }}', totalTime.toString());
        lastStoredTime = totalTime;
        console.log('Tiempo guardado:', totalTime); // Debug
    }
}

// Iniciar el timer cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página cargada, iniciando timer...'); // Debug
    startTimer();
    
    // Verificar el progreso inicial de la auditoría
    verificarFacturasConObservaciones();
    
    // Verificar el progreso cada 30 segundos
    setInterval(verificarFacturasConObservaciones, 30000);
    
    // Event listener para el botón de pausar
    const btnPausar = document.getElementById('btnPausarAuditoria');
    if (btnPausar) {
        btnPausar.addEventListener('click', async function() {
            try {
                stopTimer();
                const response = await fetch('{{ route('auditoria.pausar', ['audit' => $audit->id]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        tiempo_acumulado: accumulatedTime
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al pausar la auditoría: ' + error.message);
            }
        });
    }

    // Event listener para el botón de terminar
    const btnTerminar = document.getElementById('btnTerminarAuditoria');
    if (btnTerminar) {
        btnTerminar.addEventListener('click', terminarAuditoria);
    }
});

// Función para verificar el progreso de las facturas
async function verificarFacturasConObservaciones() {
    try {
        console.log('Verificando progreso de la auditoría...'); // Debug
        const response = await fetch(`{{ route('auditoria.verificar-observaciones', ['audit' => $audit->id]) }}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Datos de progreso recibidos:', data); // Debug

        // Actualizar el botón de terminar
        const btnTerminar = document.getElementById('btnTerminarAuditoria');
        if (btnTerminar) {
            btnTerminar.disabled = !data.todasTienenObservaciones;
            btnTerminar.title = data.todasTienenObservaciones 
                ? 'Todas las facturas han sido revisadas' 
                : `Faltan ${data.facturasSinObservaciones} facturas por revisar: ${data.facturasPendientes.join(', ')}`;
        }

        // Actualizar el progreso
        const progresoElement = document.getElementById('progresoAuditoria');
        const progressBar = document.getElementById('progressBar');
        
        if (progresoElement && progressBar && data.progreso) {
            const porcentaje = data.progreso.porcentaje;
            progresoElement.textContent = `${data.progreso.auditadas}/${data.progreso.total} (${porcentaje}%)`;
            progressBar.style.width = `${porcentaje}%`;
            
            // Actualizar el color de la barra de progreso
            if (porcentaje < 25) {
                progressBar.style.background = 'linear-gradient(90deg, #dc3545, #dc3545)';
            } else if (porcentaje < 50) {
                progressBar.style.background = 'linear-gradient(90deg, #ffc107, #fd7e14)';
            } else if (porcentaje < 75) {
                progressBar.style.background = 'linear-gradient(90deg, #17a2b8, #0dcaf0)';
            } else {
                progressBar.style.background = 'linear-gradient(90deg, #28a745, #20c997)';
            }
        }

    } catch (error) {
        console.error('Error al verificar progreso:', error);
        const progresoElement = document.getElementById('progresoAuditoria');
        if (progresoElement) {
            progresoElement.textContent = 'Error al verificar progreso';
        }
    }
}

// Función para terminar la auditoría
async function terminarAuditoria() {
    if (!confirm('¿Está seguro de que desea terminar la auditoría? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        // Detener el timer antes de terminar
        stopTimer();
        
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const formData = new FormData();
        formData.append('_token', token);
        formData.append('tiempo_acumulado', accumulatedTime);
        
        const response = await fetch(`{{ route('auditoria.terminar', ['audit' => $auditId]) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Auditoría terminada exitosamente');
            // Redirigir al índice de auditorías después de un breve delay
            setTimeout(() => {
                window.location.href = '{{ route('auditoria.index') }}';
            }, 1500);
        } else {
            throw new Error(data.message || 'Error al terminar la auditoría');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error al terminar la auditoría: ' + error.message, 'danger');
    }
}

// Variables globales para el estado de pantalla completa
let isFullscreen = false;
let currentFullscreenPdfIndex = {{ $currentPdfIndex }};
let currentFullscreenFacturaId = '{{ $currentFacturaId }}';
let currentPdfFiles = {!! json_encode($currentFacturaPdfFiles) !!};

// Variables globales para mantener el estado
let currentAuditId = '{{ $auditId }}';
let currentFacturaData = {
    id: '{{ $currentFacturaId }}',
    factura: '{{ $currentFacturaNumero }}',
    aseguradora: '{{ $currentFacturaAseguradora }}',
    pdfFiles: {!! json_encode($currentFacturaPdfFiles) !!},
    currentPdfIndex: {{ $currentPdfIndex }}
};

// Función para cargar PDF
function loadPdf(url) {
    const mainViewer = document.getElementById('mainPdfViewer');
    const fullscreenViewer = document.getElementById('fullscreenPdfViewer');
    
    // Asegurarse de que la URL sea válida
    if (!url) {
        console.error('URL del PDF no válida');
        return;
    }

    // Agregar parámetros de visualización si no están presentes
    const pdfParams = '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
    const finalUrl = url.includes('#') ? url : url + pdfParams;
    
    if (mainViewer) {
        mainViewer.src = finalUrl;
    }
    if (fullscreenViewer) {
        fullscreenViewer.src = finalUrl;
    }
}

// Función para cargar una factura
async function loadFactura(facturaId) {
    try {
        if (!facturaId) {
            console.error('ID de factura no válido');
            showMessage('ID de factura no válido', 'danger');
            return;
        }

        // Mostrar indicador de carga
        showLoadingIndicator();

        const url = '{{ route('auditoria.get-factura-data', ['audit' => ':audit', 'factura' => ':factura']) }}'
            .replace(':audit', currentAuditId)
            .replace(':factura', facturaId);

        const response = await fetch(url);
        const data = await response.json();
        
        // Ocultar indicador de carga
        hideLoadingIndicator();
        
        if (!response.ok) {
            throw new Error(data.message || `Error HTTP: ${response.status}`);
        }
        
        if (data.success && data.factura) {
            // Actualizar el visor de PDF
            if (data.currentPdf) {
                loadPdf(data.currentPdf);
            } else {
                showMessage('No se encontraron archivos PDF para esta factura', 'warning');
            }
            
            // Actualizar la lista de hallazgos
            updateHallazgosList(data.hallazgos || []);
            
            // Guardar los datos de la factura actual
            currentFacturaData = {
                id: data.factura.id,
                factura: data.factura.factura || '',
                aseguradora: data.factura.aseguradora || '',
                pdfFiles: Array.isArray(data.pdfFiles) ? data.pdfFiles : [],
                currentPdfIndex: 0
            };

            // Actualizar UI
            updateUI();
        } else {
            throw new Error(data.message || 'No se pudieron cargar los datos de la factura');
        }
    } catch (error) {
        console.error('Error al cargar la factura:', error);
        showMessage(error.message || 'Error al cargar la factura', 'danger');
        
        // Limpiar datos actuales en caso de error
        currentFacturaData = null;
        updateUI();
    } finally {
        hideLoadingIndicator();
    }
}

// Función para mostrar el indicador de carga
function showLoadingIndicator() {
    // Crear o mostrar un elemento de carga
    let loader = document.getElementById('loadingIndicator');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'loadingIndicator';
        loader.className = 'position-fixed top-50 start-50 translate-middle bg-white p-3 rounded shadow-lg';
        loader.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="spinner-border text-primary me-2" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <span>Cargando factura...</span>
            </div>
        `;
        document.body.appendChild(loader);
    }
    loader.style.display = 'block';
}

// Función para ocultar el indicador de carga
function hideLoadingIndicator() {
    const loader = document.getElementById('loadingIndicator');
    if (loader) {
        loader.style.display = 'none';
    }
}

// Función para actualizar la UI
function updateUI() {
    // Actualizar elementos activos
    document.querySelectorAll('.factura-item, tr[data-id]').forEach(el => {
        if (currentFacturaData) {
            el.classList.toggle('active', el.getAttribute('data-id') === currentFacturaData.id);
        } else {
            el.classList.remove('active');
        }
    });

    // Actualizar contadores de PDF y navegación solo si hay datos válidos
    if (currentFacturaData && Array.isArray(currentFacturaData.pdfFiles)) {
        updatePdfCounter();
        updateNavigationButtonsState();
    } else {
        // Limpiar o deshabilitar elementos de la UI si no hay datos
        document.querySelectorAll('.pdf-counter').forEach(counter => {
            counter.textContent = 'No hay documentos disponibles';
        });
        
        document.querySelectorAll('.prev-pdf, .next-pdf').forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.3';
        });
    }
}

// Función para cargar una factura en modo pantalla completa
async function loadFacturaFullscreen(facturaId, facturaNumero, aseguradora) {
    try {
        if (!facturaId) {
            console.error('ID de factura no válido');
            return;
        }

        // Actualizar la UI primero
        document.querySelectorAll('.factura-item').forEach(item => {
            item.classList.remove('active');
        });
        const facturaItem = document.querySelector(`.factura-item[data-id="${facturaId}"]`);
        if (facturaItem) {
            facturaItem.classList.add('active');
        }

        const url = '{{ route('auditoria.get-factura-data', ['audit' => ':audit', 'factura' => ':factura']) }}'
            .replace(':audit', currentAuditId)
            .replace(':factura', facturaId);

        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.factura) {
            // Actualizar el visor de PDF
            if (data.currentPdf) {
                const fullscreenPdfViewer = document.getElementById('fullscreenPdfViewer');
                if (fullscreenPdfViewer) {
                    fullscreenPdfViewer.src = data.currentPdf + '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
                }
            }
            
            // Actualizar datos globales
            currentPdfFiles = data.pdfFiles || [];
            currentFullscreenPdfIndex = 0;
            currentFullscreenFacturaId = facturaId;
            
            // Actualizar contador y botones
            updateFullscreenCounter();
            updateNavigationButtonsState();
            
            // Actualizar lista de hallazgos y formulario
            updateFullscreenHallazgos(data.factura, data.hallazgos || []);
        } else {
            showMessage(data.message || 'Error al cargar la factura: No se encontraron datos', 'danger');
        }
    } catch (error) {
        console.error('Error al cargar la factura:', error);
        showMessage('Error al cargar la factura: ' + error.message, 'danger');
    }
}

function toggleFullscreen() {
    const viewer = document.getElementById('fullscreenViewer');
    
    isFullscreen = !isFullscreen;
    
    if (isFullscreen) {
        viewer.classList.add('active');
        document.body.style.overflow = 'hidden';
        currentFullscreenPdfIndex = currentPdfIndex;
        updateFullscreenViewer();
    } else {
        viewer.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function toggleFacturasList() {
    const panel = document.getElementById('facturasPanel');
    if (panel) {
        panel.classList.toggle('collapsed');
    }
}

function toggleSidebar() {
    const panel = document.getElementById('observacionesPanel');
    if (panel) {
        panel.classList.toggle('collapsed');
    }
}

function updateFullscreenCounter() {
    const counter = document.querySelector('.fullscreen-controls .pdf-counter');
    if (!counter || !currentPdfFiles.length) return;
    
    const total = currentPdfFiles.length;
    const current = currentFullscreenPdfIndex + 1;
    const numero = '{{ $currentFacturaNumero }}';
    counter.textContent = `${numero} - Documento ${current} de ${total}`;
}

function updateNavigationButtonsState() {
    const prevBtn = document.querySelector('.fullscreen-controls button[onclick="prevPdfFullscreen()"]');
    const nextBtn = document.querySelector('.fullscreen-controls button[onclick="nextPdfFullscreen()"]');
    
    if (prevBtn && nextBtn) {
        prevBtn.disabled = currentFullscreenPdfIndex <= 0;
        nextBtn.disabled = currentFullscreenPdfIndex >= currentPdfFiles.length - 1;
        
        prevBtn.style.opacity = prevBtn.disabled ? '0.3' : '1';
        nextBtn.style.opacity = nextBtn.disabled ? '0.3' : '1';
    }
}

function prevPdfFullscreen() {
    if (currentFullscreenPdfIndex > 0) {
        currentFullscreenPdfIndex--;
        updateFullscreenViewer();
    }
}

function nextPdfFullscreen() {
    if (currentFullscreenPdfIndex < currentPdfFiles.length - 1) {
        currentFullscreenPdfIndex++;
        updateFullscreenViewer();
    }
}

// Función para mostrar mensajes
function showMessage(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Función para enviar el formulario como OK
function submitOk(button) {
    const form = button.closest('form');
    if (!form) return;
    
    // Establecer el texto predeterminado para la observación
    const observacionTextarea = form.querySelector('textarea[name="observacion"]');
    if (observacionTextarea) {
        observacionTextarea.value = "Documentación correcta";
    }
    
    // Establecer is_ok como true
    const isOkInput = form.querySelector('input[name="is_ok"]');
    if (isOkInput) {
        isOkInput.value = '1';
    } else {
        // Si no existe el campo, crearlo
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'is_ok';
        hiddenInput.value = '1';
        form.appendChild(hiddenInput);
    }
    
    // Enviar el formulario
    form.dispatchEvent(new Event('submit'));
}

// Función para actualizar la lista de hallazgos
async function updateHallazgosList() {
    try {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Actualizar la lista de hallazgos en ambas vistas
        const mainHallazgos = doc.querySelector('.hallazgos-container');
        const fullscreenHallazgos = doc.querySelector('#hallazgosList');
        
        if (mainHallazgos) {
            document.querySelector('.hallazgos-container').innerHTML = mainHallazgos.innerHTML;
        }
        
        if (fullscreenHallazgos) {
            document.querySelector('#hallazgosList').innerHTML = fullscreenHallazgos.innerHTML;
        }
    } catch (error) {
        console.error('Error al actualizar la lista de hallazgos:', error);
    }
}

// Función para verificar y restaurar el estado de pantalla completa
function checkAndRestoreFullscreen() {
    const wasFullscreen = sessionStorage.getItem('isFullscreen') === 'true';
    if (wasFullscreen) {
        toggleFullscreen();
    }
}

// Variables globales
const currentPdfIndex = {{ $currentPdfIndex }};
const currentFacturaPdfFiles = @json($currentFacturaPdfFiles);
let isSplitScreen = false;

// Navegación
function prevPdf() {
    if (currentPdfIndex > 0) {
        window.location.href = `{{ route('auditoria.start', [
            'audit' => $auditId,
            'factura_id' => $currentFacturaId
        ]) }}?pdf_index=${currentPdfIndex - 1}`;
    }
}

function nextPdf() {
    if (currentPdfIndex < currentFacturaPdfFiles.length - 1) {
        window.location.href = `{{ route('auditoria.start', [
            'audit' => $auditId,
            'factura_id' => $currentFacturaId
        ]) }}?pdf_index=${currentPdfIndex + 1}`;
    }
}

// Pantalla dividida
function toggleSplitScreen() {
    const viewer = document.getElementById('splitScreenViewer');
    
    isSplitScreen = !isSplitScreen;
    
    if (isSplitScreen) {
        viewer.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Inicializar los archivos PDF
        splitScreenPdfFiles = [...currentPdfFiles]; // Hacer una copia del array
        topPdfIndex = currentPdfIndex;
        bottomPdfIndex = currentPdfIndex;
        
        console.log('Iniciando Split Screen:');
        console.log('PDFs:', splitScreenPdfFiles);
        console.log('Top Index:', topPdfIndex);
        console.log('Bottom Index:', bottomPdfIndex);
        
        // Actualizar los visores después de un pequeño delay para asegurar que el DOM está listo
        setTimeout(() => {
            updateSplitScreenViewers();
        }, 100);
    } else {
        viewer.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Variables para el modo pantalla dividida
let topPdfIndex = 0;
let bottomPdfIndex = 0;
let splitScreenPdfFiles = [];

// Función para cargar una factura en modo pantalla dividida
async function loadFacturaSplit(facturaId, facturaNumero, aseguradora) {
    try {
        if (!facturaId) {
            console.error('ID de factura no válido');
            return;
        }

        // Actualizar la UI primero
        document.querySelectorAll('.factura-item').forEach(item => {
            item.classList.remove('active');
        });
        const facturaItem = document.querySelector(`.factura-item[data-id="${facturaId}"]`);
        if (facturaItem) {
            facturaItem.classList.add('active');
        }

        const url = '{{ route('auditoria.get-factura-data', ['audit' => ':audit', 'factura' => ':factura']) }}'
            .replace(':audit', currentAuditId)
            .replace(':factura', facturaId);

        showLoadingIndicator();
        const response = await fetch(url);
        const data = await response.json();
        hideLoadingIndicator();
        
        if (data.success && data.factura) {
            // Guardar los archivos PDF
            splitScreenPdfFiles = data.pdfFiles || [];
            currentFacturaData = data.factura;
            
            // Actualizar los visores
            if (splitScreenPdfFiles.length > 0) {
                topPdfIndex = Math.min(topPdfIndex, splitScreenPdfFiles.length - 1);
                bottomPdfIndex = Math.min(bottomPdfIndex, splitScreenPdfFiles.length - 1);
                updateSplitScreenViewers();
            }
            
            // Actualizar el panel de hallazgos
            updateSplitScreenHallazgos(data.factura, data.hallazgos || []);
        } else {
            showMessage(data.message || 'Error al cargar la factura: No se encontraron datos', 'danger');
        }
    } catch (error) {
        console.error('Error al cargar la factura:', error);
        showMessage('Error al cargar la factura: ' + error.message, 'danger');
        hideLoadingIndicator();
    }
}

// Función para actualizar el panel de hallazgos en modo split screen
function updateSplitScreenHallazgos(factura, hallazgos) {
    const hallazgosContainer = document.getElementById('splitHallazgosList');
    const formContainer = document.getElementById('splitScreenFormContainer');
    
    if (!hallazgosContainer || !formContainer) return;
    
    // Actualizar lista de hallazgos
    if (hallazgos.length > 0) {
        const hallazgosHtml = hallazgos.map(hallazgo => `
            <div class="hallazgo-item">
                <p>${hallazgo.observacion || 'N/A'}</p>
                ${hallazgo.documento_referencia ? 
                    `<small>Documento: ${hallazgo.documento_referencia}</small>` : ''}
            </div>
        `).join('');
        hallazgosContainer.innerHTML = hallazgosHtml;
    } else {
        hallazgosContainer.innerHTML = '<div class="alert alert-info">No hay hallazgos registrados</div>';
    }
    
    // Actualizar formulario
    const isFacturaAuditada = hallazgos.length > 0;
    const formHtml = isFacturaAuditada ? `
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> FACTURA YA AUDITADA
        </div>
    ` : `
        <form method="POST" 
              action="/auditoria/${currentAuditId}/${factura.id}/observacion"
              id="splitScreenObservationForm" 
              class="observation-form"
              data-factura-id="${factura.id}">
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
            <input type="hidden" name="is_ok" value="0">
            <input type="hidden" 
                   name="current_pdf" 
                   value="${splitScreenPdfFiles[topPdfIndex]?.name || ''}" />
            
            <div class="form-group">
                <label class="text-white">Nueva Observación</label>
                <textarea class="form-control" name="observacion" rows="3" required></textarea>
            </div>
            
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-save"></i> Guardar Observación
                </button>
                <button type="button" class="btn btn-success flex-grow-1" onclick="submitOk(this)">
                    <i class="fas fa-check"></i> OK
                </button>
            </div>
        </form>
    `;
    
    formContainer.innerHTML = formHtml;

    // Agregar event listener para el formulario
    const form = document.getElementById('splitScreenObservationForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
    const jsonData = {
        observacion: formData.get('observacion'),
        current_pdf: formData.get('current_pdf'),
                    is_ok: formData.get('is_ok') === '1',
                    _token: formData.get('_token')
    };

                const response = await fetch(this.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(jsonData)
                });
                
                const data = await response.json();
                
        if (data.success) {
            showMessage(data.message);
                    // Recargar los datos de la factura para actualizar los hallazgos
                    loadFacturaSplit(factura.id);
                    // Actualizar el progreso general
                    verificarFacturasConObservaciones();
                } else {
                    throw new Error(data.message || 'Error al guardar la observación');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage(error.message || 'Error al guardar la observación', 'danger');
            }
        });
    }
}

// Función para actualizar hallazgos en pantalla completa
function updateFullscreenHallazgos(factura, hallazgos) {
    const hallazgosContainer = document.getElementById('hallazgosList');
    const sidebarContent = document.querySelector('#observacionesPanel .sidebar-content');
    
    if (!hallazgosContainer || !sidebarContent) return;
    
    // Limpiar todo el contenido anterior
    sidebarContent.innerHTML = '';
    
    // Crear y agregar el contenedor de hallazgos
    const hallazgosDiv = document.createElement('div');
    hallazgosDiv.id = 'hallazgosList';
    
    // Actualizar lista de hallazgos
    if (hallazgos.length > 0) {
        const hallazgosHtml = hallazgos.map(hallazgo => `
            <div class="hallazgo-item">
                <p>${hallazgo.observacion || 'N/A'}</p>
                ${hallazgo.documento_referencia ? 
                    `<small>Documento: ${hallazgo.documento_referencia}</small>` : ''}
            </div>
        `).join('');
        hallazgosDiv.innerHTML = hallazgosHtml;
    } else {
        hallazgosDiv.innerHTML = '<div class="alert alert-info">No hay hallazgos registrados</div>';
    }
    
    // Agregar la lista de hallazgos al contenedor principal
    sidebarContent.appendChild(hallazgosDiv);
    
    // Actualizar formulario
    const isFacturaAuditada = hallazgos.length > 0;
    if (isFacturaAuditada) {
        const auditadaDiv = document.createElement('div');
        auditadaDiv.className = 'alert alert-success mt-3';
        auditadaDiv.innerHTML = '<i class="fas fa-check-circle"></i> FACTURA YA AUDITADA';
        sidebarContent.appendChild(auditadaDiv);
    } else {
        const formHtml = `
            <form method="POST" 
                  action="/auditoria/${currentAuditId}/${factura.id}/observacion"
                  id="fullscreenObservationForm" 
                  class="observation-form mt-3"
                  data-factura-id="${factura.id}">
                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                <input type="hidden" name="is_ok" value="0">
                <input type="hidden" 
                       name="current_pdf" 
                       value="${currentPdfFiles[currentFullscreenPdfIndex]?.name || ''}" />
                
                <div class="form-group">
                    <label class="text-white">Nueva Observación</label>
                    <textarea class="form-control" name="observacion" rows="3" required></textarea>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-save"></i> Guardar Observación
                    </button>
                    <button type="button" class="btn btn-success flex-grow-1" onclick="submitOk(this)">
                        <i class="fas fa-check"></i> OK
                    </button>
                </div>
            </form>
        `;
        
        const formWrapper = document.createElement('div');
        formWrapper.innerHTML = formHtml;
        sidebarContent.appendChild(formWrapper);
        
        // Agregar event listener para el formulario
        const form = document.getElementById('fullscreenObservationForm');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    const formData = new FormData(this);
                    const jsonData = {
                        observacion: formData.get('observacion'),
                        current_pdf: formData.get('current_pdf'),
                        is_ok: formData.get('is_ok') === '1',
                        _token: formData.get('_token')
                    };
                    
                    console.log('Enviando datos:', jsonData); // Debug
                    
                    const response = await fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(jsonData)
                    });
                    
                    const data = await response.json();
                    console.log('Respuesta:', data); // Debug
                    
                    if (data.success) {
                        showMessage(data.message);
                        // Recargar los datos de la factura para actualizar los hallazgos
                        loadFacturaFullscreen(factura.id);
                        // Actualizar el progreso general
            verificarFacturasConObservaciones();
        } else {
            throw new Error(data.message || 'Error al guardar la observación');
        }
                } catch (error) {
        console.error('Error:', error);
        showMessage(error.message || 'Error al guardar la observación', 'danger');
                }
            });
        }
    }
}

// Funciones de navegación para el visor superior
function prevPdfTop() {
    if (topPdfIndex > 0) {
        topPdfIndex--;
        updateSplitScreenViewers();
    }
}

function nextPdfTop() {
    if (topPdfIndex < splitScreenPdfFiles.length - 1) {
        topPdfIndex++;
        updateSplitScreenViewers();
    }
}

// Funciones de navegación para el visor inferior
function prevPdfBottom() {
    if (bottomPdfIndex > 0) {
        bottomPdfIndex--;
        updateSplitScreenViewers();
    }
}

function nextPdfBottom() {
    if (bottomPdfIndex < splitScreenPdfFiles.length - 1) {
        bottomPdfIndex++;
        updateSplitScreenViewers();
    }
}

// Función para actualizar los visores
function updateSplitScreenViewers() {
    try {
        const topViewer = document.getElementById('splitScreenTopViewer');
        const bottomViewer = document.getElementById('splitScreenBottomViewer');
        const topCounter = document.getElementById('topPdfCounter');
        const bottomCounter = document.getElementById('bottomPdfCounter');
        const topTitle = document.getElementById('topPdfTitle');
        const bottomTitle = document.getElementById('bottomPdfTitle');
        
        console.log('Split Screen PDFs:', splitScreenPdfFiles); // Debug
        console.log('Top Index:', topPdfIndex); // Debug
        console.log('Bottom Index:', bottomPdfIndex); // Debug
        
        if (splitScreenPdfFiles.length > 0) {
            // Actualizar visor superior
            if (topViewer && splitScreenPdfFiles[topPdfIndex]) {
                topViewer.src = splitScreenPdfFiles[topPdfIndex].url + '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
            }
            if (topCounter) {
                topCounter.textContent = `${topPdfIndex + 1} / ${splitScreenPdfFiles.length}`;
            }
            if (topTitle) {
                const pdfName = splitScreenPdfFiles[topPdfIndex]?.name || 'Sin nombre';
                const originalName = splitScreenPdfFiles[topPdfIndex]?.original_name || pdfName;
                topTitle.textContent = `${pdfName} (${originalName})`;
            }
            
            // Actualizar visor inferior
            if (bottomViewer && splitScreenPdfFiles[bottomPdfIndex]) {
                bottomViewer.src = splitScreenPdfFiles[bottomPdfIndex].url + '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
            }
            if (bottomCounter) {
                bottomCounter.textContent = `${bottomPdfIndex + 1} / ${splitScreenPdfFiles.length}`;
            }
            if (bottomTitle) {
                const pdfName = splitScreenPdfFiles[bottomPdfIndex]?.name || 'Sin nombre';
                const originalName = splitScreenPdfFiles[bottomPdfIndex]?.original_name || pdfName;
                bottomTitle.textContent = `${pdfName} (${originalName})`;
            }
            
            // Actualizar estado de los botones
            updateSplitScreenNavigationState();
        }
    } catch (error) {
        console.error('Error en updateSplitScreenViewers:', error);
    }
}

// Función para actualizar el visor en pantalla completa
function updateFullscreenViewer() {
    try {
        const viewer = document.getElementById('fullscreenPdfViewer');
        const counter = document.getElementById('fullscreenPdfCounter');
        const title = document.getElementById('fullscreenPdfTitle');
        
        if (currentPdfFiles.length > 0 && currentPdfFiles[currentFullscreenPdfIndex]) {
            if (viewer) {
                viewer.src = currentPdfFiles[currentFullscreenPdfIndex].url + '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
            }
            if (counter) {
                counter.textContent = `${currentFullscreenPdfIndex + 1} / ${currentPdfFiles.length}`;
            }
            if (title) {
                const pdfName = currentPdfFiles[currentFullscreenPdfIndex].name || 'Sin nombre';
                const originalName = currentPdfFiles[currentFullscreenPdfIndex].original_name || pdfName;
                title.textContent = `${pdfName} (${originalName})`;
            }
        }
    } catch (error) {
        console.error('Error en updateFullscreenViewer:', error);
    }
}

// Función para actualizar el estado de los botones de navegación
function updateSplitScreenNavigationState() {
    // Botones del visor superior
    const prevTopBtn = document.querySelector('.split-pdf-controls button[onclick="prevPdfTop()"]');
    const nextTopBtn = document.querySelector('.split-pdf-controls button[onclick="nextPdfTop()"]');
    
    if (prevTopBtn) prevTopBtn.disabled = topPdfIndex <= 0;
    if (nextTopBtn) nextTopBtn.disabled = topPdfIndex >= splitScreenPdfFiles.length - 1;
    
    // Botones del visor inferior
    const prevBottomBtn = document.querySelector('.split-pdf-controls button[onclick="prevPdfBottom()"]');
    const nextBottomBtn = document.querySelector('.split-pdf-controls button[onclick="nextPdfBottom()"]');
    
    if (prevBottomBtn) prevBottomBtn.disabled = bottomPdfIndex <= 0;
    if (nextBottomBtn) nextBottomBtn.disabled = bottomPdfIndex >= splitScreenPdfFiles.length - 1;
}

// Funciones para mostrar/ocultar paneles
function toggleSplitFacturasList() {
    const panel = document.getElementById('splitFacturasPanel');
    if (panel) {
        panel.classList.toggle('collapsed');
    }
}

function toggleSplitSidebar() {
    const panel = document.getElementById('splitObservacionesPanel');
    if (panel) {
        panel.classList.toggle('collapsed');
    }
}

// Redimensionamiento
document.addEventListener('DOMContentLoaded', function() {
    const splitDivider = document.querySelector('.split-divider');
    if (!splitDivider) return;

    const topContainer = splitDivider.previousElementSibling;
    const bottomContainer = splitDivider.nextElementSibling;
    let isDragging = false;
    let startY;
    let startHeights;

    splitDivider.addEventListener('mousedown', function(e) {
        isDragging = true;
        startY = e.clientY;
        startHeights = {
            top: topContainer.offsetHeight,
            bottom: bottomContainer.offsetHeight
        };
        document.body.style.cursor = 'row-resize';
    });

    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;

        const delta = e.clientY - startY;
        const containerHeight = topContainer.parentElement.offsetHeight;
        const minHeight = 100;

        let newTopHeight = startHeights.top + delta;
        let newBottomHeight = startHeights.bottom - delta;

        if (newTopHeight < minHeight || newBottomHeight < minHeight) return;

        const topPercent = (newTopHeight / containerHeight) * 100;
        const bottomPercent = (newBottomHeight / containerHeight) * 100;

        topContainer.style.flex = `${topPercent}`;
        bottomContainer.style.flex = `${bottomPercent}`;
    });

    document.addEventListener('mouseup', function() {
        isDragging = false;
        document.body.style.cursor = '';
    });
});
</script>
@endpush

@endsection

