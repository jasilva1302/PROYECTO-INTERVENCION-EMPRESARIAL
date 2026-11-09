<!-- Modal Actualización Masiva -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUpdateModalLabel">Actualización Masiva de Tarifas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('tarifas.bulkUpdate') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="archivo_actualizacion" class="form-label">Archivo Excel</label>
                        <input type="file" class="form-control" id="archivo_actualizacion" name="archivo" accept=".xlsx,.xls" required>
                        <div class="form-text">
                            El archivo debe contener las columnas: CUPS, Valor Nuevo
                        </div>
                    </div>

                    <!-- Fechas de Vigencia -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Fechas de Vigencia</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="inicio_vigencia_update" class="form-label">Inicio de Vigencia</label>
                                        <input type="date" class="form-control" id="inicio_vigencia_update" name="inicio_vigencia" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fin_vigencia_update" class="form-label">Fin de Vigencia</label>
                                        <input type="date" class="form-control" id="fin_vigencia_update" name="fin_vigencia" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i> Esta acción actualizará todas las tarifas que coincidan con los CUPS proporcionados.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Actualizar Tarifas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Establecer fechas por defecto
    const today = new Date();
    const startOfYear = new Date(today.getFullYear(), 0, 1);
    const endOfYear = new Date(today.getFullYear(), 11, 31);
    
    document.getElementById('inicio_vigencia_update').value = startOfYear.toISOString().split('T')[0];
    document.getElementById('fin_vigencia_update').value = endOfYear.toISOString().split('T')[0];

    // Validación del formulario
    document.querySelector('#bulkUpdateModal form').addEventListener('submit', function(e) {
        const inicio = new Date(document.getElementById('inicio_vigencia_update').value);
        const fin = new Date(document.getElementById('fin_vigencia_update').value);
        
        if (fin < inicio) {
            e.preventDefault();
            alert('La fecha de fin de vigencia debe ser posterior a la fecha de inicio.');
        }
    });
});
</script>
@endpush 