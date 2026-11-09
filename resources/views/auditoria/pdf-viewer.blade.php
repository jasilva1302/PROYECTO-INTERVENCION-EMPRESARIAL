@extends('layouts.app', ['fullPage' => true])

@section('title', 'Visualizador PDF')

@section('styles')
<style>
    /* Estructura principal */
    .pdf-viewer-app {
        display: flex;
        flex-direction: column;
        height: 100vh;
        width: 100vw;
        background-color: #fff;
    }
    
    /* Cabecera superior */
    .pdf-header {
        padding: 10px 15px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    /* Controles de navegación */
    .pdf-controls {
        padding: 1rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        z-index: 100;
    }

    .pdf-controls-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    /* Contenedor principal del PDF */
    .pdf-container {
        flex: 1;
        position: relative;
        background-color: #525659;
        min-height: 0;
    }
    
    /* Iframe del PDF */
    .pdf-iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }
    
    /* Navegación y controles */
    .pdf-navigation {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .pdf-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .pdf-counter {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pdf-type-badge {
        background-color: #6c757d;
        color: white;
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
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
        flex-direction: column;
        min-height: 0;
    }

    .split-pdf-container {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
        min-height: 0;
    }

    .split-pdf-wrapper {
        width: 95%;
        height: 95%;
        background: white;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
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
    }

    /* Estilos responsivos */
    @media (max-width: 768px) {
        .pdf-controls-inner {
            flex-direction: column;
            align-items: stretch;
        }

        .pdf-navigation, .pdf-actions {
            justify-content: center;
        }

        .pdf-counter {
            justify-content: center;
            text-align: center;
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="pdf-viewer-app">
    <!-- Cabecera superior -->
    <div class="pdf-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('auditoria.index') }}">Auditorías</a></li>
                <li class="breadcrumb-item"><a href="{{ route('auditoria.show', $audit) }}">{{ $audit->sede }} - {{ $audit->month }} {{ $audit->year }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('auditoria.pdf-structure', $audit) }}">Documentos</a></li>
                <li class="breadcrumb-item active">{{ $aseguradora }} - {{ $factura }}</li>
            </ol>
        </nav>
    </div>
    
    <!-- Controles de navegación -->
    <div class="pdf-controls">
        <div class="pdf-controls-inner">
            <div class="pdf-navigation">
                <button class="btn btn-secondary" {{ $current <= 0 ? 'disabled' : '' }} onclick="prevPdf()">
                    <i class="fas fa-chevron-left"></i> Ant
                </button>
                
                <div class="pdf-counter">
                    <span class="fw-bold">Documento {{ $current + 1 }} de {{ count($soportes) }}</span>
                    @if(isset($soportes[$current]['type']))
                        <span class="badge rounded-pill pdf-type-badge">
                            {{ $soportes[$current]['type'] }}
                        </span>
                    @endif
                </div>

                <button class="btn btn-secondary" {{ $current >= count($soportes) - 1 ? 'disabled' : '' }} onclick="nextPdf()">
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
    
    <!-- Contenedor principal del PDF -->
    <div class="pdf-container">
        @if(count($soportes) > 0)
            <iframe 
                id="mainPdfViewer"
                class="pdf-iframe" 
                src="{{ $soportes[$current]['url'] }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH"
                allowfullscreen>
            </iframe>
        @else
            <div class="alert alert-warning m-3">
                No se encontraron documentos PDF para esta factura
            </div>
        @endif
    </div>
</div>

<!-- Visor Pantalla Dividida -->
<div class="split-screen-viewer" id="splitScreenViewer">
    <div class="split-screen-controls">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-light btn-sm" onclick="toggleSplitScreen()">
                <i class="fas fa-compress"></i> Salir
            </button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-light btn-sm" onclick="prevPdfSplitScreen()">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>
            <span class="text-white">
                Documento {{ $current + 1 }} de {{ count($soportes) }}
            </span>
            <button class="btn btn-outline-light btn-sm" onclick="nextPdfSplitScreen()">
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
    <div class="split-screen-content">
        <div class="split-pdf-container">
            <div class="split-pdf-wrapper">
                @if(count($soportes) > 0)
                    <iframe id="splitScreenTopViewer" 
                            src="{{ $soportes[$current]['url'] }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH">
                    </iframe>
                @else
                    <div class="alert alert-warning">
                        No hay PDF disponible para mostrar.
                    </div>
                @endif
            </div>
        </div>
        
        <div class="split-divider"></div>
        
        <div class="split-pdf-container">
            <div class="split-pdf-wrapper">
                @if(count($soportes) > 0)
                    <iframe id="splitScreenBottomViewer" 
                            src="{{ $soportes[$current]['url'] }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH">
                    </iframe>
                @else
                    <div class="alert alert-warning">
                        No hay PDF disponible para mostrar.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Variables globales
    const currentPdfIndex = {{ $current }};
    const soportes = @json($soportes);
    let isSplitScreen = false;

    // Navegación
    function prevPdf() {
        if (currentPdfIndex > 0) {
            window.location.href = `{{ route('auditoria.pdf-viewer', [
                'audit' => $audit->id,
                'aseguradora' => urlencode($aseguradora),
                'factura' => urlencode($factura)
            ]) }}?current=${currentPdfIndex - 1}`;
        }
    }

    function nextPdf() {
        if (currentPdfIndex < soportes.length - 1) {
            window.location.href = `{{ route('auditoria.pdf-viewer', [
                'audit' => $audit->id,
                'aseguradora' => urlencode($aseguradora),
                'factura' => urlencode($factura)
            ]) }}?current=${currentPdfIndex + 1}`;
        }
    }

    // Pantalla dividida
    function toggleSplitScreen() {
        const viewer = document.getElementById('splitScreenViewer');
        const splitScreenTopViewer = document.getElementById('splitScreenTopViewer');
        const splitScreenBottomViewer = document.getElementById('splitScreenBottomViewer');
        const mainPdfViewer = document.getElementById('mainPdfViewer');
        
        isSplitScreen = !isSplitScreen;
        
        if (isSplitScreen) {
            viewer.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (mainPdfViewer?.src) {
                splitScreenTopViewer.src = mainPdfViewer.src;
                splitScreenBottomViewer.src = mainPdfViewer.src;
            }
        } else {
            viewer.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }

    function prevPdfSplitScreen() {
        if (currentPdfIndex > 0) {
            const newUrl = soportes[currentPdfIndex - 1].url + '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
            document.getElementById('splitScreenTopViewer').src = newUrl;
            document.getElementById('splitScreenBottomViewer').src = newUrl;
        }
    }

    function nextPdfSplitScreen() {
        if (currentPdfIndex < soportes.length - 1) {
            const newUrl = soportes[currentPdfIndex + 1].url + '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
            document.getElementById('splitScreenTopViewer').src = newUrl;
            document.getElementById('splitScreenBottomViewer').src = newUrl;
        }
    }

    // Pantalla completa
    function toggleFullscreen() {
        const mainPdfViewer = document.getElementById('mainPdfViewer');
        if (!document.fullscreenElement) {
            mainPdfViewer.requestFullscreen().catch(err => {
                console.log(`Error attempting to enable full-screen mode: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
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
@endsection