<!-- pages/trabajo-form.php -->
<div class="container-fluid p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2 id="trabajo-form-title" class="mb-2 mb-md-0"><i class="bi bi-plus-circle-fill"></i> Añadir Nuevo Trabajo</h2>
        <a href="index.php?page=main&tab=trabajos" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver al listado</a>
    </div>

    <form id="trabajoForm" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="id" id="trabajo-id">

        <!-- Card for Main Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-info-circle-fill"></i> Información Principal</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Tipo de Trabajo</label>
                            <button type="button" class="btn btn-outline-primary btn-sm py-0" onclick="openAddTipoTrabajoModal()">
                                <i class="bi bi-plus"></i> Añadir rápido
                            </button>
                        </div>
                        <select class="form-select mb-3" name="tipo_trabajo_id" id="trabajo-tipo-select" required></select>
                    </div>
                     <div class="col-lg-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Vehículo Asociado (Opcional)</label>
                            <button type="button" class="btn btn-outline-primary btn-sm py-0" onclick="openAddAutoModal()"><i class="bi bi-plus"></i> Añadir rápido</button>
                        </div>
                        <input type="text" id="trabajo-auto-search-input" class="form-control mb-2" placeholder="Buscar vehículo...">
                        <select class="form-select" name="auto_id" id="trabajo-auto-select" size="5"></select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card for Cliente -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-person-fill"></i> Datos del Cliente</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Cliente (Opcional)</label>
                            <button type="button" class="btn btn-outline-primary btn-sm py-0" onclick="openAddClienteModal(true)">
                                <i class="bi bi-plus"></i> Añadir rápido
                            </button>
                        </div>
                        <select class="form-select mb-3" name="cliente_id" id="trabajo-cliente-select">
                            <!-- Options populated by JS -->
                        </select>
                        <div class="form-text">Selecciona un cliente recurrente o déjalo en blanco.</div>
                    </div>
                    <div class="col-lg-6 mb-3"><label class="form-label">Patente</label><input type="text" class="form-control" name="cliente_patente" placeholder="AA 123 BB"></div>
                    <div class="col-lg-4 mb-3">
                        <label class="form-label">Tipo de Corte</label>
                        <input type="text" id="trabajo-corte-search-input" class="form-control form-control-sm mb-1" placeholder="Buscar tipo de corte...">
                        <select class="form-select" name="corte_id" id="trabajo-corte-select"></select>
                    </div>
                    <div class="col-lg-4 mb-3"><label class="form-label">Número de Corte</label><input type="text" class="form-control" name="cliente_corte" placeholder="1443212431"></div>
                    <div class="col-lg-4 mb-3"><label class="form-label">Pin Code</label><input type="text" class="form-control" name="cliente_pincode" placeholder="12345"></div>
                </div>
            </div>
        </div>

        <!-- Card for Items & Equipos -->
        <div class="card mb-4">
            <div class="card-header"><h5><i class="bi bi-list-check"></i> Ítems y Equipos</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <h6>Ítems Usados</h6>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Buscar y Añadir Ítem</label>
                                <button type="button" class="btn btn-outline-primary btn-sm py-0" onclick="openAddItemModal()"><i class="bi bi-plus"></i> Añadir rápido</button>
                            </div>
                            <input type="text" id="trabajo-item-search-input" class="form-control mb-2" placeholder="Buscar ítem...">
                            <select id="trabajo-item-search" class="form-select" size="5"></select>
                        </div>
                        <div id="trabajo-items-list" class="list-group"></div>
                    </div>
                    <div class="col-lg-6">
                         <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0">Equipos Usados</h6>
                             <button type="button" class="btn btn-outline-primary btn-sm py-0" onclick="openAddEquipoModal()"><i class="bi bi-plus"></i> Añadir rápido</button>
                         </div>
                         <label class="form-label">Seleccionar Equipos</label>
                         <select id="trabajo-equipo-select" class="form-select" multiple size="8"></select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card for Details -->
         <div class="card mb-4">
            <div class="card-header"><h5><i class="bi bi-pencil-square"></i> Detalles y Notas</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">Detalle del Procedimiento</label><textarea class="form-control" name="detalle" rows="5" placeholder="Describe el trabajo realizado..."></textarea></div>
                <div class="mb-3"><label class="form-label">Notas Adicionales</label><textarea class="form-control" name="notas" rows="3" placeholder="Añade notas o comentarios..."></textarea></div>
                <div class="mb-3"><label for="trabajo-imagen" class="form-label">Imagen (Opcional)</label><input type="file" class="form-control" name="imagen" id="trabajo-imagen" accept="image/*" onchange="previewImage(this, 'trabajo-imagen-preview')"><img id="trabajo-imagen-preview" class="img-fluid img-preview d-none mt-2" alt="Vista previa" style="max-height: 200px;"></div>
            </div>
        </div>

        <!-- Card for Financials -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-cash-coin"></i> Finanzas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Beneficio ($)</label><input type="number" class="form-control" name="net_profit" placeholder="0.00" step="0.01" value="0" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Gastos ($)</label><input type="number" class="form-control" name="gastos" placeholder="0.00" step="0.01" value="0" required></div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="trabajo-is-not-paid" name="is_not_paid">
                    <label class="form-check-label" for="trabajo-is-not-paid">Marcar como NO PAGADO</label>
                    <div class="form-text">Si se marca, el trabajo aparecerá resaltado y no se contará en las estadísticas financieras.</div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end align-items-center bg-body-tertiary p-3 rounded sticky-bottom">
            <a href="index.php?page=main&tab=trabajos" class="btn btn-secondary me-2">Cancelar</a>
            <button type="button" class="btn btn-primary btn-lg" id="saveTrabajoBtn" onclick="saveTrabajo()">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                Guardar Trabajo
            </button>
        </div>
    </form>
</div>
<?php include 'partials/modals.php'; ?>
<!-- MODIFIED: Removed the script tag from here. It is now loaded globally in index.php -->

