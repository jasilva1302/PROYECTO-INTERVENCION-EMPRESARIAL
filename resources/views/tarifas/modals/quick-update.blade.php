<!-- Modal Actualización Rápida -->
<div class="modal fade" id="quickUpdateModal{{ $tarifa->id }}" tabindex="-1" aria-labelledby="quickUpdateModalLabel{{ $tarifa->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickUpdateModalLabel{{ $tarifa->id }}">Actualización Rápida de Tarifa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('tarifas.quickUpdate', $tarifa->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tarifa Actual</label>
                        <p class="form-control-plaintext">{{ $tarifa->valor_formateado }}</p>
                    </div>
                    <div class="mb-3">
                        <label for="new_tarifa{{ $tarifa->id }}" class="form-label">Nueva Tarifa</label>
                        <input type="number" step="0.01" class="form-control" id="new_tarifa{{ $tarifa->id }}" name="new_tarifa" required>
                    </div>
                    <div class="mb-3">
                        <label for="year{{ $tarifa->id }}" class="form-label">Año</label>
                        <input type="number" class="form-control" id="year{{ $tarifa->id }}" name="year" value="{{ date('Y') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div> 