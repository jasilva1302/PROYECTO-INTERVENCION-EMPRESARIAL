<!-- Modal Historial -->
<div class="modal fade" id="historialModal{{ $tarifa->id }}" tabindex="-1" aria-labelledby="historialModalLabel{{ $tarifa->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historialModalLabel{{ $tarifa->id }}">Historial de Tarifa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Valor Anterior</th>
                                <th>Valor Nuevo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tarifa->historial as $historial)
                            <tr>
                                <td>{{ $historial->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $historial->user->name }}</td>
                                <td>{{ number_format($historial->valor_anterior, 2) }}</td>
                                <td>{{ number_format($historial->valor_nuevo, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div> 