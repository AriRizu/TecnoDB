<!-- pages/trabajos.php -->
<style>
/* Basic Timeline CSS */
.timeline {
    list-style-type: none;
    position: relative;
    padding-left: 1.5rem;
}
.timeline:before {
    content: ' ';
    background: #d4d9df;
    display: inline-block;
    position: absolute;
    left: 0;
    width: 2px;
    height: 100%;
    z-index: 400;
}
.timeline > li {
    margin: 20px 0;
    padding-left: 20px;
}
.timeline > li .timeline-date {
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
}
.timeline > li:before {
    content: ' ';
    background: white;
    display: inline-block;
    position: absolute;
    border-radius: 50%;
    border: 3px solid #0d6efd;
    left: -7px;
    width: 15px;
    height: 15px;
    z-index: 400;
}
[data-bs-theme="dark"] .timeline:before {
    background: #495057;
}
[data-bs-theme="dark"] .timeline > li:before {
    background: #343a40;
    border-color: #0d6efd;
}
</style>
<div class="card">
    <div class="card-header">
        <ul class="nav nav-pills card-header-pills" id="trabajoSubTab" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="explore-trabajos-tab" data-bs-toggle="tab" data-bs-target="#explore-trabajos-pane" type="button"><i class="bi bi-journal-text"></i> Historial de Trabajos</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="stats-trabajos-tab" data-bs-toggle="tab" data-bs-target="#stats-trabajos-pane" type="button"><i class="bi bi-graph-up"></i> Estadísticas</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="manage-tipos-trabajo-tab" data-bs-toggle="tab" data-bs-target="#manage-tipos-trabajo-pane" type="button"><i class="bi bi-tags-fill"></i> Gestionar Tipos de Trabajo</button></li>
        </ul>
    </div>
    <div class="card-body tab-content" id="trabajoSubTabContent">
        <div class="tab-pane fade show active" id="explore-trabajos-pane" role="tabpanel">
            <!-- Financial Stats -->
            <div class="row mb-4 text-center">
                <div class="col-md-3 col-6 mb-3 mb-md-0">
                    <div class="card text-bg-success h-100"><div class="card-body"><h5 class="card-title" id="stat-profit-mes"></h5><p class="card-text">Beneficio (Mes)</p></div></div>
                </div>
                <div class="col-md-3 col-6 mb-3 mb-md-0">
                    <div class="card text-bg-danger h-100"><div class="card-body"><h5 class="card-title" id="stat-gastos-mes"></h5><p class="card-text">Gastos (Mes)</p></div></div>
                </div>
                 <div class="col-md-3 col-6">
                    <div class="card text-bg-primary h-100"><div class="card-body"><h5 class="card-title" id="stat-profit-total"></h5><p class="card-text">Beneficio(Total)</p></div></div>
                </div>
                 <div class="col-md-3 col-6">
                    <div class="card text-bg-warning text-dark h-100"><div class="card-body"><h5 class="card-title" id="stat-gastos-total"></h5><p class="card-text">Gastos (Total)</p></div></div>
                </div>
            </div>
            
            <div class="row mb-3 align-items-center">
                <div class="col-md-8"><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="text" id="trabajo-search" class="form-control" placeholder="Buscar por cliente, patente, tipo de trabajo, vehículo, corte..."></div></div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                    <!-- MODIFIED: Changed button to a link to the new page -->
                    <a href="index.php?page=trabajo-form" class="btn btn-primary"><i class="bi bi-plus-circle-fill"></i> Añadir Nuevo Trabajo</a>
                </div>
            </div>

            <div class="table-responsive" style="height: calc(100vh - 420px); overflow-y: auto;">
                <table class="table table-hover align-middle ">
                    <thead><tr><th>Tipo de Trabajo</th><th>Cliente</th><th>Vehículo</th><th>Patente</th><th>Corte (Tipo y Número)</th><th>Beneficio</th><th>Gastos</th><th>Fecha</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody id="trabajos-table-body"></tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="stats-trabajos-pane" role="tabpanel">
             <div id="stats-content">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                                <h6 class="mb-0 me-2" id="revenue-chart-title">Actividad Reciente</h6>
                                <div class="btn-group btn-group-sm" role="group" id="chart-time-range">
                                    <button type="button" class="btn btn-outline-primary active" onclick="setChartTimeRange('7days')">Últimos 7 Días</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="setChartTimeRange('month')">Último Mes</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="setChartTimeRange('all')">Todo</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div style="height: 300px; position: relative;">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4 text-center">
                    <div class="col-md-3">
                        <div class="card"><div class="card-body"><h5 class="card-title" id="stats-total-net"></h5><p class="card-text">Balance Total</p></div></div>
                    </div>
                     <div class="col-md-3">
                        <div class="card"><div class="card-body"><h5 class="card-title" id="stats-avg-profit"></h5><p class="card-text">Beneficio Prom./Trabajo</p></div></div>
                    </div>
                     <div class="col-md-3">
                        <div class="card"><div class="card-body"><h5 class="card-title" id="stats-most-frequent"></h5><p class="card-text">Tipo Más Frecuente</p></div></div>
                    </div>
                     <div class="col-md-3">
                        <div class="card"><div class="card-body"><h5 class="card-title" id="stats-most-profitable"></h5><p class="card-text">Tipo Más Rentable</p></div></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                         <div class="card">
                             <div class="card-header">Línea de Tiempo de Trabajos</div>
                             <div class="card-body" style="max-height: calc(100vh - 650px); overflow-y: auto;" id="timeline-container">
                                 <!-- Timeline will be rendered here by stats.js -->
                             </div>
                         </div>
                    </div>
                </div>
             </div>
        </div>
        <div class="tab-pane fade" id="manage-tipos-trabajo-pane" role="tabpanel">
             <h4 class="mb-3">Gestión de Tipos de Trabajo</h4>
            <div class="card mb-4"><div class="card-body"><h5 class="card-title">Crear Nuevo Tipo</h5><div class="input-group"><input type="text" id="new-tipo-trabajo-name-input" class="form-control" placeholder="Nombre del nuevo tipo..."><button class="btn btn-primary" id="create-tipo-trabajo-btn" type="button" onclick="createNewTipoTrabajo()">Crear</button></div></div></div>
            <div class="table-responsive" style="height: calc(100vh - 380px); overflow-y: auto;">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Nombre del Tipo</th><th>Uso</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody id="tipos-trabajo-management-table"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/stats.js"></script>
