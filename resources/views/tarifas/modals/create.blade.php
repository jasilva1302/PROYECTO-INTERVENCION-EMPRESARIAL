<!-- Modal Crear Tarifa -->
<div class="modal fade" id="createTarifaModal" tabindex="-1" aria-labelledby="createTarifaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTarifaModalLabel">Nueva Tarifa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('tarifas.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="empresa" class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="empresa" name="empresa" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sede" class="form-label">Sede</label>
                                <input type="text" class="form-control" id="sede" name="sede" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="regimen" class="form-label">Régimen</label>
                                <select class="form-select" id="regimen" name="regimen" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Contributivo">Contributivo</option>
                                    <option value="Subsidiado">Subsidiado</option>
                                    <option value="Particular">Particular</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tercero" class="form-label">Tercero</label>
                                <input type="text" class="form-control" id="tercero" name="tercero" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion_servicio" class="form-label">Descripción del Servicio</label>
                        <input type="text" class="form-control" id="descripcion_servicio" name="descripcion_servicio" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cups" class="form-label">CUPS</label>
                                <input type="text" class="form-control" id="cups" name="cups" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cups_ips_facturados" class="form-label">CUPS IPS/Facturados</label>
                                <input type="text" class="form-control" id="cups_ips_facturados" name="cups_ips_facturados">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tarifas_de_manual" class="form-label">Tarifas de Manual</label>
                                <input type="number" step="0.01" class="form-control" id="tarifas_de_manual" name="tarifas_de_manual">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="valor" class="form-label">Valor</label>
                                <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div> 