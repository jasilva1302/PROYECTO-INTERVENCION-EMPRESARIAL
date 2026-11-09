<!-- Modal de Importación -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Importar Tarifas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importForm" action="{{ route('tarifas.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div id="importAlert" class="alert" style="display: none;"></div>
                    
                    <div class="mb-3">
                        <label for="file" class="form-label">Archivo Excel</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                    </div>
                    <div class="mb-3">
                        <label for="inicio_vigencia" class="form-label">Inicio de Vigencia</label>
                        <input type="date" class="form-control" id="inicio_vigencia" name="inicio_vigencia" required>
                    </div>
                    <div class="mb-3">
                        <label for="fin_vigencia" class="form-label">Fin de Vigencia</label>
                        <input type="date" class="form-control" id="fin_vigencia" name="fin_vigencia" required>
                    </div>

                    <div id="importProgress" class="progress" style="display: none;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-orange" 
                             role="progressbar" 
                             style="width: 0%" 
                             aria-valuenow="0" 
                             aria-valuemin="0" 
                             aria-valuemax="100">0%</div>
                    </div>
                    <div id="importStatus" class="text-center mt-2"></div>
                    
                    <!-- Botón de descarga de reporte -->
                    <div id="downloadReportContainer" class="text-center mt-3" style="display: none;">
                        <a href="#" id="downloadReportBtn" class="btn btn-danger" target="_blank">
                            <i class="fas fa-download"></i> Descargar Reporte de Errores
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-orange" id="importButton">
                        <i class="fas fa-upload"></i> Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('importForm');
    const progressBar = document.querySelector('#importProgress .progress-bar');
    const progressDiv = document.getElementById('importProgress');
    const statusDiv = document.getElementById('importStatus');
    const importButton = document.getElementById('importButton');
    const alertDiv = document.getElementById('importAlert');
    const downloadReportContainer = document.getElementById('downloadReportContainer');
    const downloadReportBtn = document.getElementById('downloadReportBtn');
    let importInterval;

    function showAlert(message, type) {
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = message;
        alertDiv.style.display = 'block';
    }

    function handleImportError(error, responseData) {
        console.error('Error:', error);
        let errorMessage = error.message || 'Error en la importación';
        
        // Si tenemos datos de respuesta y hay un archivo de reporte
        if (responseData && responseData.report_file) {
            downloadReportBtn.href = `${window.location.origin}/tarifas/download-report/${responseData.report_file}`;
            downloadReportContainer.style.display = 'block';
            errorMessage = responseData.message || errorMessage;
        }
        
        showAlert(errorMessage, 'danger');
        progressDiv.style.display = 'none';
        importButton.disabled = false;
        if (importInterval) clearInterval(importInterval);
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Validar fechas
        const inicio = new Date(document.getElementById('inicio_vigencia').value);
        const fin = new Date(document.getElementById('fin_vigencia').value);
        
        if (fin <= inicio) {
            showAlert('La fecha de fin debe ser posterior a la fecha de inicio', 'danger');
            return;
        }

        // Ocultar botón de descarga si estaba visible
        downloadReportContainer.style.display = 'none';

        // Mostrar barra de progreso y deshabilitar botón
        progressDiv.style.display = 'block';
        importButton.disabled = true;
        alertDiv.style.display = 'none';

        try {
            // Enviar formulario
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error en la importación');
            }

            if (data.success) {
                showAlert('Importación iniciada correctamente', 'success');
                if (data.importId) {
                    checkProgress(data.importId);
                }
            } else {
                handleImportError(new Error(data.message), data);
            }
        } catch (error) {
            handleImportError(error, null);
        }
    });

    function checkProgress(importId) {
        importInterval = setInterval(async () => {
            try {
                const response = await fetch(`${window.location.origin}/import-progress/${importId}`);
                const data = await response.json();

                if (data.success) {
                    const percentage = data.percentage || 0;
                    progressBar.style.width = `${percentage}%`;
                    progressBar.textContent = `${percentage}%`;
                    progressBar.setAttribute('aria-valuenow', percentage);

                    if (percentage >= 100) {
                        clearInterval(importInterval);
                        showAlert('Importación completada exitosamente', 'success');
                        importButton.disabled = false;
                    }
                } else {
                    throw new Error(data.error || 'Error al verificar el progreso');
                }
            } catch (error) {
                console.error('Error:', error);
                clearInterval(importInterval);
                showAlert(error.message, 'danger');
                importButton.disabled = false;
            }
        }, 1000);
    }
});
</script>
@endpush 