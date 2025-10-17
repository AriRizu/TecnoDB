<!-- pages/items.php -->
<?php
// This file is included in index.php and contains the HTML for the item library tab.
?>
<div class="card">
    <div class="card-header">
        <ul class="nav nav-pills card-header-pills" id="itemSubTab" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="explore-items-tab" data-bs-toggle="tab" data-bs-target="#explore-items-pane" type="button"><i class="bi bi-grid-3x3-gap-fill"></i> Explorar Ítems</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="manage-cortes-tab" data-bs-toggle="tab" data-bs-target="#manage-cortes-pane" type="button"><i class="bi bi-scissors"></i> Gestionar Cortes</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="manage-tags-tab" data-bs-toggle="tab" data-bs-target="#manage-tags-pane" type="button"><i class="bi bi-tags-fill"></i> Gestionar Tags</button></li>
        </ul>
    </div>
    <div class="card-body tab-content" id="itemSubTabContent">
        <div class="tab-pane fade show active" id="explore-items-pane" role="tabpanel">
             <div class="row mb-3 align-items-center">
                <div class="col-md-6"><div class="input-group input-group-lg"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="text" id="item-library-search" class="form-control" placeholder="Búsqueda rápida..."></div></div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0"><div class="form-check d-inline-block me-3"><input class="form-check-input" type="checkbox" id="select-all-items" onchange="toggleSelectAllItems(this.checked)"><label class="form-check-label" for="select-all-items">Todos</label></div><button class="btn btn-danger" id="mass-delete-items-btn" onclick="massDeleteItems()" disabled><i class="bi bi-trash-fill"></i> Eliminar</button></div>
            </div>
             <details class="mb-3">
                <summary class="fw-bold">Búsqueda Avanzada</summary>
                <div class="p-3 mt-2 advanced-search-card">
                    <div class="row g-2">
                        <div class="col-md-6"><input type="text" id="adv-item-nombre" class="form-control form-control-sm" placeholder="Nombre o secundario..."></div>
                        <div class="col-md-6"><input type="text" id="adv-item-corte" class="form-control form-control-sm" placeholder="Corte..."></div>
                        <div class="col-md-6"><input type="text" id="adv-item-tag" class="form-control form-control-sm" placeholder="Tag..."></div>
                        <div class="col-md-6"><input type="text" id="adv-item-ubicacion" class="form-control form-control-sm" placeholder="Ubicación..."></div>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-2">
                        <button class="btn btn-sm btn-secondary" onclick="resetItemSearch()">Limpiar</button>
                        <button class="btn btn-sm btn-primary" onclick="performItemSearch()">Buscar</button>
                    </div>
                </div>
            </details>
            
            <div class="d-flex align-items-center mb-3 justify-content-start justify-content-md-end">
                <label for="item-sort-select" class="form-label me-2 mb-0">Ordenar por:</label>
                <select class="form-select form-select-sm me-3" id="item-sort-select" style="max-width: 200px;">
                    <option value="nombre-asc">Nombre (A-Z)</option>
                    <option value="nombre-desc">Nombre (Z-A)</option>
                    <option value="stock-desc">Mayor Stock</option>
                    <option value="stock-asc">Menor Stock</option>
                    <option value="creacion-desc">Más Recientes</option>
                    <option value="creacion-asc">Más Antiguos</option>
                </select>
            </div>

            <div id="items-per-row-controls" class="d-none d-md-flex justify-content-end align-items-center mb-3">
                <label for="items-per-row-slider" class="form-label me-2 mb-0">Ítems por fila:</label>
                <span id="items-per-row-value" class="fw-bold me-3">6</span>
                <input type="range" class="form-range" style="max-width: 200px;" min="1" max="6" value="5" id="items-per-row-slider">
            </div>

            <div class="row g-3">
                <div class="col-lg-8 main-panel-container" id="items-library-grid-container" style="height: calc(100vh - 360px); overflow-y: auto;">
                    <div id="items-library-grid" class="row g-3"></div>
                </div>
                <div class="col-lg-4 details-panel-container" id="item-details-container" style="height: calc(100vh - 360px); overflow-y: auto;">
                    <button class="btn btn-outline-secondary mb-3 mobile-back-button" onclick="showListView('items')"><i class="bi bi-arrow-left"></i> Volver a la lista</button>
                    <div id="item-details-content"><div class="initial-view"><i class="bi bi-box-seam" style="font-size: 5rem;"></i><h2 class="mt-3">Selecciona un ítem</h2><p class="lead text-muted">Haz clic para ver sus relaciones.</p></div></div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="manage-cortes-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Gestión de Cortes</h4>
                <div>
                    <button class="btn btn-primary me-2" onclick="openAddCorteModal()"><i class="bi bi-plus-circle-fill"></i> Crear Nuevo Corte</button>
                    <button class="btn btn-danger" id="mass-delete-cortes-btn" onclick="massDeleteCortes()" disabled><i class="bi bi-trash-fill"></i> Eliminar</button>
                </div>
            </div>
            <div class="table-responsive" style="height: calc(100vh - 320px); overflow-y: auto;">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 5%;"><input class="form-check-input" type="checkbox" id="select-all-cortes" onchange="toggleSelectAllCortes(this.checked)"></th>
                            <th style="width: 10%;">Imagen</th>
                            <th>Nombre del Corte</th>
                            <th>Bitting</th>
                            <th>Uso</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cortes-management-table"></tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="manage-tags-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Gestión de Tags</h4><button class="btn btn-danger" id="mass-delete-tags-btn" onclick="massDeleteTags()" disabled><i class="bi bi-trash-fill"></i> Eliminar</button></div>
            <div class="card mb-4"><div class="card-body"><h5 class="card-title">Crear Nuevo Tag</h5><div class="input-group"><input type="text" id="new-tag-name-input" class="form-control" placeholder="Nombre del nuevo tag..."><button class="btn btn-primary" id="create-tag-btn" type="button" onclick="createNewTag()">Crear Tag</button></div></div></div>
            <div class="table-responsive" style="height: calc(100vh - 380px); overflow-y: auto;"><table class="table table-hover align-middle"><thead><tr><th style="width: 5%;"><input class="form-check-input" type="checkbox" id="select-all-tags" onchange="toggleSelectAllTags(this.checked)"></th><th>Nombre del Tag</th><th>Uso</th><th class="text-end">Acciones</th></tr></thead><tbody id="tags-management-table"></tbody></table></div>
        </div>
    </div>
</div>

<!-- MODAL PARA CORTES -->
<div class="modal fade" id="corteModal" tabindex="-1" aria-labelledby="corteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="corteModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="corteForm" onsubmit="event.preventDefault(); saveCorte();">
                    <input type="hidden" id="corte-id" name="id">
                    <div class="mb-3">
                        <label for="corte-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="corte-nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="corte-bitting" class="form-label">Bitting</label>
                        <textarea class="form-control" id="corte-bitting" name="bitting" rows="3" placeholder="Ej: 11221221"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="corte-imagen" class="form-label">Imagen</label>
                        <input class="form-control" type="file" id="corte-imagen" name="imagen" accept="image/*" onchange="previewImage(this, 'corte-imagen-preview')">
                    </div>
                    <img id="corte-imagen-preview" src="#" alt="Vista previa de la imagen" class="img-fluid rounded d-none" style="max-height: 200px;"/>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveCorteBtn" onclick="saveCorte()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>
