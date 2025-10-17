<!-- pages/vehicles.php -->
<?php
// This file is included in index.php and contains the HTML for the vehicle management tab.
?>
<div class="row g-0">
    <!-- MODIFICATION: Added id="autos-list-column" and class="main-panel-container" -->
    <div id="autos-list-column" class="col-lg-4 col-xl-3 p-3 bg-body-tertiary border-end main-panel-container" style="height: calc(100vh - 120px); overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Vehículos</h4><span class="badge bg-secondary rounded-pill" id="auto-count">0</span></div>
        <div class="input-group mb-3"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="text" id="auto-search" class="form-control" placeholder="Búsqueda rápida..."></div>
        
        <details class="mb-3">
            <summary class="fw-bold">Búsqueda Avanzada</summary>
            <div class="p-3 mt-2 advanced-search-card">
                <div class="mb-2"><input type="text" id="adv-auto-marca" class="form-control form-control-sm" placeholder="Marca..."></div>
                <div class="mb-2"><input type="text" id="adv-auto-modelo" class="form-control form-control-sm" placeholder="Modelo..."></div>
                <div class="mb-2"><input type="number" id="adv-auto-anio" class="form-control form-control-sm" placeholder="Año..."></div>
                <div class="mb-2"><input type="text" id="adv-auto-spec" class="form-control form-control-sm" placeholder="Especificación..."></div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button class="btn btn-sm btn-secondary" onclick="resetAutoSearch()">Limpiar</button>
                    <button class="btn btn-sm btn-primary" onclick="performAutoSearch()">Buscar</button>
                </div>
            </div>
        </details>

        <div class="input-group input-group-sm mb-2">
            <label class="input-group-text" for="auto-sort-select"><i class="bi bi-sort-alpha-down"></i></label>
            <select class="form-select" id="auto-sort-select">
                <option value="marca-asc">Marca (A-Z)</option>
                <option value="marca-desc">Marca (Z-A)</option>
                <option value="modelo-asc">Modelo (A-Z)</option>
                <option value="modelo-desc">Modelo (Z-A)</option>
                <option value="creacion-desc">Más Recientes</option>
                <option value="creacion-asc">Más Antiguos</option>
            </select>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="form-check"><input class="form-check-input" type="checkbox" id="select-all-autos" onchange="toggleSelectAllAutos(this.checked)"><label class="form-check-label" for="select-all-autos">Todos</label></div>
            <div class="btn-group">
                <button class="btn btn-sm btn-primary" id="mass-assign-item-btn" onclick="openAssignItemToMultipleAutosModal()" disabled><i class="bi bi-link-45deg"></i> Asignar Ítem</button>
                <button class="btn btn-sm btn-danger" id="mass-delete-autos-btn" onclick="massDeleteAutos()" disabled><i class="bi bi-trash-fill"></i> Eliminar</button>
            </div>
        </div>
        <div id="autos-list" class="accordion"></div>
    </div>
    <!-- MODIFICATION: Added id="autos-details-column" and class="details-panel-container" -->
    <div id="autos-details-column" class="col-lg-8 col-xl-9 p-4 details-panel-container" style="height: calc(100vh - 120px); overflow-y: auto;">
        <!-- MODIFICATION: Added Back button for mobile view -->
        <button class="btn btn-outline-secondary mb-3 mobile-back-button" onclick="showListView('autos')"><i class="bi bi-arrow-left"></i> Volver a la lista</button>
        <div id="auto-details-content"><div class="initial-view"><i class="bi bi-arrow-left-circle" style="font-size: 5rem;"></i><h2 class="mt-3">Selecciona un vehículo</h2><p class="lead text-muted">Haz clic en una versión de vehículo para ver sus detalles.</p></div></div>
    </div>
</div>
