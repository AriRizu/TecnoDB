<!-- partials/modals.php -->
<!-- Add/Edit Auto Modal -->
<div class="modal fade" id="addAutoModal" tabindex="-1" style="z-index: 1101;" aria-labelledby="autoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="autoModalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="addAutoForm" autocomplete="off">
                    <input type="hidden" name="id" id="auto-id">
                    <div class="mb-3 position-relative"><input type="text" class="form-control" name="marca" id="marca-input" placeholder="Marca" required><div id="marcas-suggestions" class="autocomplete-suggestions list-group"></div></div>
                    <div class="mb-3"><input type="text" class="form-control" name="modelo" placeholder="Modelo" required></div>
                    <div class="mb-3" id="year-ranges-field"><label for="year-ranges" class="form-label">Rangos de Años</label><textarea class="form-control" id="year-ranges" name="year_ranges" rows="3" placeholder="Ej: 2000-2002, 1996-1999"></textarea><div class="form-text">Para añadir múltiples, separe con comas. Para editar, use un solo rango.</div></div>
                    <div id="global-specs-container"><div class="mt-3" id="spec1-field"><input type="text" class="form-control" name="spec1" placeholder="Especificación 1 Global" list="spec1-list"></div><div class="mt-3" id="spec2-field"><input type="text" class="form-control" name="spec2" placeholder="Especificación 2 Global" list="spec2-list"></div></div>
                    <div id="dynamic-specs-container" class="mt-2"></div>
                    <hr>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="remember-brand-toggle" checked><label class="form-check-label" for="remember-brand-toggle">Recordar marca</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="remember-model-toggle" checked><label class="form-check-label" for="remember-model-toggle">Recordar modelo</label></div>
                </form>
                <datalist id="modelos-list"></datalist><datalist id="spec1-list"></datalist><datalist id="spec2-list"></datalist>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="saveAutoBtn" onclick="saveAuto()">Guardar</button></div>
        </div>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" style="z-index: 1101;" aria-labelledby="itemModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="itemModalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="addItemForm" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="item-id">
                    <div class="mb-3"><input type="text" class="form-control" name="nombre" placeholder="Nombre del Ítem" required></div>
                    <div class="mb-3 position-relative"><input type="text" class="form-control" name="corte" id="corte-input" placeholder="Corte (ej: HU83, VA2T)"><div id="corte-suggestions" class="autocomplete-suggestions list-group"></div></div>
                    <div class="mb-3"><textarea class="form-control" name="nombres_secundarios" placeholder="Nombres secundarios (separados por comas)"></textarea></div>
                    <div class="mb-3"><input type="text" class="form-control" name="ubicacion" placeholder="Ubicación"></div>
                    <div class="mb-3"><label for="item-stock" class="form-label">Cantidad en Stock</label><input type="number" class="form-control" name="stock" id="item-stock" placeholder="Cantidad" value="1" min="0" required></div>
                    <div class="mb-3"><label for="item-stock-threshold" class="form-label">Umbral de Stock Bajo</label><input type="number" class="form-control" name="stock_threshold" id="item-stock-threshold" placeholder="Umbral para alerta" value="0" min="0" required><div class="form-text">Recibirás una alerta visual cuando el stock sea igual o inferior a este valor.</div></div>
                    <div class="row g-3 mb-3"><div class="col-md-6"><label for="item-imagen" class="form-label">Imagen Principal</label><input type="file" class="form-control" name="imagen" id="item-imagen" accept="image/*" onchange="previewImage(this, 'imagen-preview')"><img id="imagen-preview" class="img-fluid img-preview d-none" alt="Vista previa"></div><div class="col-md-6"><label for="item-imagen-detalle" class="form-label">Imagen de Detalle</label><input type="file" class="form-control" name="imagen_detalle" id="item-imagen-detalle" accept="image/*" onchange="previewImage(this, 'imagen-detalle-preview')"><img id="imagen-detalle-preview" class="img-fluid img-preview d-none" alt="Vista previa"></div></div>
                    <div class="mb-3"><textarea class="form-control" name="descripcion" placeholder="Descripción"></textarea></div>
                    <div class="mb-3 position-relative"><label class="form-label">Tags (separados por comas)</label><input type="text" class="form-control" name="tags" id="tags-input" placeholder="ej: tag1, tag2"><div id="tags-suggestions" class="autocomplete-suggestions list-group"></div></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="saveItemBtn" onclick="saveItem()">Guardar</button></div>
        </div>
    </div>
</div>

<!-- REMOVED Add/Edit Trabajo Modal -->
<!-- The form is now on its own page: trabajo-form.php -->

<!-- ADDED: Quick Add Tipo de Trabajo Modal -->
<div class="modal fade" id="addTipoTrabajoModal" tabindex="-1" style="z-index: 1101;" aria-labelledby="tipoTrabajoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tipoTrabajoModalTitle"><i class="bi bi-tags-fill"></i> Añadir Tipo de Trabajo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="new-tipo-trabajo-name-input-modal" class="form-label">Nombre del Tipo</label>
                    <input type="text" id="new-tipo-trabajo-name-input-modal" class="form-control" placeholder="Nombre del nuevo tipo...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="create-tipo-trabajo-btn-modal" onclick="createNewTipoTrabajoInForm()">Crear</button>
            </div>
        </div>
    </div>
</div>


<!-- Add/Edit Cliente Modal -->
<div class="modal fade" id="addClienteModal" tabindex="-1" style="z-index: 1101;" aria-labelledby="clienteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clienteModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addClienteForm" autocomplete="off">
                    <input type="hidden" name="id" id="cliente-id">
                    <div class="mb-3">
                        <label for="cliente-nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" name="nombre" id="cliente-nombre" placeholder="Nombre del Cliente" required>
                    </div>
                    <div class="mb-3">
                        <label for="cliente-telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="telefono" id="cliente-telefono" placeholder="Ej: 381 123 4567">
                    </div>
                    <div class="mb-3">
                        <label for="cliente-cvu" class="form-label">CVU/Alias</label>
                        <input type="text" class="form-control" name="cvu" id="cliente-cvu" placeholder="CVU o Alias para transferencias">
                    </div>
                     <div class="mb-3">
                        <label for="cliente-notas" class="form-label">Notas</label>
                        <textarea class="form-control" name="notas" id="cliente-notas" rows="3" placeholder="Información adicional sobre el cliente..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveClienteBtn" onclick="saveCliente()">Guardar Cliente</button>
            </div>
        </div>
    </div>
</div>


<!-- Assign Item to Multiple Autos Modal -->
<div class="modal fade" id="assignItemToMultipleAutosModal" style="z-index: 1101;" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-link-45deg"></i> Asignar Ítem a Vehículos</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><p>Asignar un ítem a <strong id="assign-to-multiple-autos-count">0</strong> vehículo(s) seleccionado(s).</p><input type="text" id="item-search-for-mass-assign-modal" class="form-control mb-3" placeholder="Buscar ítem por nombre o tag..."><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nombre</th><th>Descripción</th><th>Tags</th><th class="text-end">Acción</th></tr></thead><tbody id="massAssignItemsList"></tbody></table></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<!-- Assign Vehicles to Item Modal -->
<div class="modal fade" id="assignVehiclesToItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-car-front-fill"></i> Asignar Vehículos a Ítem</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><p>Asignar vehículos al ítem: <strong id="assign-item-name"></strong>.</p><input type="text" id="vehicle-search-for-item-modal" class="form-control mb-3" placeholder="Buscar vehículo..."><div id="assign-vehicles-list" style="max-height: 60vh; overflow-y: auto;"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="save-vehicle-assignments-btn" onclick="saveVehicleAssignments()">Guardar</button></div>
        </div>
    </div>
</div>

<!-- Add/Edit Equipo Modal -->
<div class="modal fade" id="equipoModal" tabindex="-1" style="z-index: 1101;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="equipoModalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="equipoForm" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="equipo-id">
                    <div class="mb-3"><input type="text" class="form-control" name="nombre" placeholder="Nombre del Equipo" required></div>
                    <div class="mb-3"><textarea class="form-control" name="descripcion" placeholder="Descripción"></textarea></div>
                    <div class="mb-3"><label for="equipo-imagen" class="form-label">Imagen</label><input type="file" class="form-control" name="imagen" id="equipo-imagen" accept="image/*" onchange="previewImage(this, 'equipo-imagen-preview')"><img id="equipo-imagen-preview" class="img-fluid img-preview d-none" alt="Vista previa"></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="saveEquipoBtn" onclick="saveEquipo()">Guardar</button></div>
        </div>
    </div>
</div>

<!-- Assign Vehicles to Equipo Modal -->
<div class="modal fade" id="assignVehiclesToEquipoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-tools"></i> Asignar Vehículos a Equipo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><p>Asignar vehículos al equipo: <strong id="assign-equipo-name"></strong>.</p><input type="text" id="vehicle-search-for-equipo-modal" class="form-control mb-3" placeholder="Buscar vehículo..."><div id="assign-vehicles-list-for-equipo" style="max-height: 60vh; overflow-y: auto;"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="save-vehicle-to-equipo-assignments-btn" onclick="saveVehicleToEquipoAssignments()">Guardar</button></div>
        </div>
    </div>
</div>

<!-- Image Viewer Modal -->
<div class="modal fade" id="imageViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="imageViewerModalLabel">Visualizador</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center p-2 bg-body-tertiary"><img src="" id="imageViewerContent" class="img-fluid rounded" style="max-height: 80vh;" alt="Vista ampliada"></div>
            <div class="modal-footer"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyImageUrl()" title="Copiar URL"><i class="bi bi-clipboard"></i> Copiar URL</button><a href="#" class="btn btn-sm btn-outline-primary" id="downloadImageBtn" download="image.jpg" title="Descargar"><i class="bi bi-download"></i> Descargar</a><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="confirmActionModalLabel">Confirmar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="confirmActionModalBody">¿Estás seguro?</div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="confirmActionConfirmBtn">Confirmar</button></div>
        </div>
    </div>
</div>
