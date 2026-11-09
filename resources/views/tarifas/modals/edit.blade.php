<!-- Modal Editar -->
<div class="modal fade" id="editModal{{ $tarifa->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $tarifa->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel{{ $tarifa->id }}">Editar Tarifa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('tarifas.update', $tarifa->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="empresa{{ $tarifa->id }}" class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="empresa{{ $tarifa->id }}" name="empresa" value="{{ $tarifa->empresa }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sede{{ $tarifa->id }}" class="form-label">Sede</label>
                                <input type="text" class="form-control" id="sede{{ $tarifa->id }}" name="sede" value="{{ $tarifa->sede }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="regimen{{ $tarifa->id }}" class="form-label">Régimen</label>
                                <select class="form-select" id="regimen{{ $tarifa->id }}" name="regimen" required>
                                    <option value="Contributivo" {{ $tarifa->regimen == 'Contributivo' ? 'selected' : '' }}>Contributivo</option>
                                    <option value="Subsidiado" {{ $tarifa->regimen == 'Subsidiado' ? 'selected' : '' }}>Subsidiado</option>
                                    <option value="Particular" {{ $tarifa->regimen == 'Particular' ? 'selected' : '' }}>Particular</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tercero{{ $tarifa->id }}" class="form-label">Tercero</label>
                                <input type="text" class="form-control" id="tercero{{ $tarifa->id }}" name="tercero" value="{{ $tarifa->tercero }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion_servicio{{ $tarifa->id }}" class="form-label">Descripción del Servicio</label>
                        <input type="text" class="form-control" id="descripcion_servicio{{ $tarifa->id }}" name="descripcion_servicio" value="{{ $tarifa->descripcion_servicio }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cups{{ $tarifa->id }}" class="form-label">CUPS</label>
                                <input type="text" class="form-control" id="cups{{ $tarifa->id }}" name="cups" value="{{ $tarifa->cups }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cups_ips_facturados{{ $tarifa->id }}" class="form-label">CUPS IPS/Facturados</label>
                                <input type="text" class="form-control" id="cups_ips_facturados{{ $tarifa->id }}" name="cups_ips_facturados" value="{{ $tarifa->cups_ips_facturados }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tarifas_de_manual{{ $tarifa->id }}" class="form-label">Tarifas de Manual</label>
                                <input type="number" step="0.01" class="form-control" id="tarifas_de_manual{{ $tarifa->id }}" name="tarifas_de_manual" value="{{ $tarifa->tarifas_de_manual }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="valor{{ $tarifa->id }}" class="form-label">Valor</label>
                                <input type="number" step="0.01" class="form-control" id="valor{{ $tarifa->id }}" name="valor" value="{{ $tarifa->valor }}" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div> 