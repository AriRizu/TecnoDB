<!-- pages/equipos.php -->
<div class="row g-0">
    <!-- Left Column -->
    <!-- MODIFICATION: Added id="equipos-list-column" and class="main-panel-container" -->
    <div id="equipos-list-column" class="col-lg-4 col-xl-3 p-3 bg-body-tertiary border-end d-flex flex-column main-panel-container" style="height: calc(100vh - 120px);">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
            <h4 class="mb-0">Equipos</h4>
            <span class="badge bg-secondary rounded-pill" id="equipo-count">0</span>
        </div>
        
        <div id="equipos-xd" class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Añadir Equipo</h5>
                <button class="btn btn-primary w-100 mb-3 mt-2" id="create-equipo-btn" type="button" onclick="openAddEquipoModal()">
                    <i class="bi bi-plus-circle"></i> Añadir
                </button>
                        <!-- This div will scroll -->
        <div class="flex-grow-1" style="overflow-y: auto;">
            <div class="list-group" id="equipos-list">
                <!-- Equipment list will be rendered here by JavaScript -->
            </div>
        </div>
            </div>
            
        </div>


    </div>
    <!-- Right Column -->
    <!-- MODIFICATION: Added id="equipos-details-column" and class="details-panel-container" -->
    <div id="equipos-details-column" class="col-lg-8 col-xl-9 p-4 details-panel-container" style="height: calc(100vh - 120px); overflow-y: auto;">
         <!-- MODIFICATION: Added Back button for mobile view -->
        <button class="btn btn-outline-secondary mb-3 mobile-back-button" onclick="showListView('equipos')"><i class="bi bi-arrow-left"></i> Volver a la lista</button>
        <div id="equipo-details-content">
            <div class="initial-view">
                <i class="bi bi-tools" style="font-size: 5rem;"></i>
                <h2 class="mt-3">Selecciona un equipo</h2>
                <p class="lead text-muted">Haz clic en un equipo de la lista para ver sus detalles y vehículos asignados.</p>
            </div>
        </div>
    </div>
</div>

