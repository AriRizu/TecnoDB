// assets/js/equipo.js

// --- State Slice for Equipos ---
state.editingEquipoId = null;

// --- Equipo Rendering & Selection ---

/**
 * Renders the list of equipment.
 * @param {Array} [equiposToRender=state.equipos] - The array of equipment objects to render.
 */
function renderEquipos(equiposToRender) {
    const list = document.getElementById('equipos-list');
    const countBadge = document.getElementById('equipo-count');
    if (!list || !countBadge) return;

    const equiposData = equiposToRender !== undefined ? equiposToRender : state.equipos;
    list.innerHTML = '';
    countBadge.textContent = equiposData.length;

    if (equiposData.length === 0) {
        list.innerHTML = '<div class="list-group-item text-muted text-center">No hay equipos creados.</div>';
        return;
    }

    equiposData.forEach(equipo => {
        const isActive = state.selectedEquipo?.id == equipo.id;
        const item = document.createElement('a');
        item.href = "#";
        item.className = `list-group-item list-group-item-action d-flex align-items-center ${isActive ? 'active' : ''}`;
        item.dataset.equipoId = equipo.id;
        item.onclick = (e) => { e.preventDefault(); selectEquipo(equipo.id); };
        
        const imageHtml = equipo.has_image
            ? `<img src="api/router.php?action=get_equipo_image&id=${equipo.id}&t=${new Date().getTime()}" class="rounded-circle me-3" alt="${equipo.nombre}" style="width: 40px; height: 40px; object-fit: cover;">`
            : `<div class="rounded-circle me-3 bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;"><i class="bi bi-tools text-white"></i></div>`;

        item.innerHTML = `
            ${imageHtml}
            <div class="flex-grow-1">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${equipo.nombre}</h6>
                    <small class="text-muted">Usado en ${equipo.usage_count}</small>
                </div>
                <small class="text-muted d-block text-truncate" style="max-width: 200px;">${equipo.descripcion || 'Sin descripción'}</small>
            </div>
            <div class="action-buttons btn-group ms-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="openEditEquipoModal(event, ${equipo.id})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteEquipo(event, ${equipo.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
            </div>
        `;
        list.appendChild(item);
    });
}

/**
 * Fetches and displays the details for a selected piece of equipment.
 * @param {number} id - The ID of the equipment.
 */
async function selectEquipo(id) {
    const equipoDetails = await fetchAPI(`api/router.php?action=get_equipo_details&id=${id}`);
    if(equipoDetails) {
        renderEquipoDetails(equipoDetails);
        if (isMobileView()) {
            showDetailsView('equipos');
        }
    }
}

/**
 * Renders the details panel for a selected piece of equipment.
 * @param {object} equipo - The equipment object.
 */
function renderEquipoDetails(equipo) {
    state.selectedEquipo = equipo;
    const content = document.getElementById('equipo-details-content');
    let autosHtml = '<div class="list-group-item text-muted text-center">No asignado a vehículos.</div>';
    if (equipo.autos && equipo.autos.length > 0) {
         autosHtml = equipo.autos.map(auto => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold">${auto.marca} ${auto.modelo} (${formatYearRange(auto.anio_inicio, auto.anio_fin)})</div>
                </div>
                <button class="btn btn-sm btn-outline-danger" onclick="unassignEquipo(${auto.id}, ${equipo.id}, true)"><i class="bi bi-trash"></i></button>
            </li>
         `).join('');
    }
    
    const imgSrc = equipo.imagen ? `data:${equipo.imagen_mime};base64,${equipo.imagen}` : '';
    const imageHtml = equipo.imagen ? `<div class="mb-3 text-center"><img src="${imgSrc}" class="img-fluid rounded clickable" alt="Imagen de equipo" style="max-height: 150px;" onclick="openItemImage(this, '${equipo.nombre.replace(/'/g, "\\'")}', 'Principal')"></div>` : '';

    const manageAutosButton = `<button class="btn btn-primary" onclick="openAssignVehiclesToEquipoModal(${equipo.id}, '${equipo.nombre.replace(/'/g, "\\'")}')"><i class="bi bi-pencil-square"></i> Gestionar Vehículos</button>`;

    content.innerHTML = `
        <div class="card h-100">
            <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                <h4 class="h5 mb-0 text-truncate" title="${equipo.nombre}">${equipo.nombre}</h4>
            </div>
            <div class="card-body d-flex flex-column">
                ${imageHtml}
                <h6>Descripción</h6>
                <p>${equipo.descripcion || '<em class="text-muted">Sin descripción.</em>'}</p>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Vehículos asociados (${equipo.autos?.length || 0}):</h6>
                    ${manageAutosButton}
                </div>
                <div class="flex-grow-1" style="overflow-y: auto;">
                    <ul class="list-group list-group-flush">${autosHtml}</ul>
                </div>
            </div>
        </div>`;
    renderEquipos();
}


// --- Modals, Save, Delete ---

function openAddEquipoModal() {
    state.editingEquipoId = null;
    const form = document.getElementById('equipoForm');
    form.reset();
    document.getElementById('equipoModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Añadir Equipo';
    previewImage(document.getElementById('equipo-imagen'), 'equipo-imagen-preview');
    ModalManager.show('equipoModal');
}

async function openEditEquipoModal(event, id) {
    event.stopPropagation();
    const equipo = await fetchAPI(`api/router.php?action=get_equipo_details&id=${id}`);
    if (!equipo) {
        showToast('No se pudo cargar la información del equipo.', 'danger');
        return;
    }
    
    state.editingEquipoId = id;
    const form = document.getElementById('equipoForm');
    form.querySelector('#equipo-id').value = equipo.id;
    form.querySelector('[name="nombre"]').value = equipo.nombre;
    form.querySelector('[name="descripcion"]').value = equipo.descripcion || '';
    
    const preview = document.getElementById('equipo-imagen-preview');
    if (equipo.imagen) {
        preview.src = `data:${equipo.imagen_mime};base64,${equipo.imagen}`;
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
        preview.src = '';
    }
    document.getElementById('equipo-imagen').value = '';

    document.getElementById('equipoModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> Editar Equipo';
    ModalManager.show('equipoModal');
}

async function saveEquipo() {
    const saveBtn = document.getElementById('saveEquipoBtn');
    toggleButtonLoading(saveBtn, true);
    const form = document.getElementById('equipoForm');
    const formData = new FormData(form);
    
    if (!formData.get('nombre')) {
        showToast("El nombre es requerido.", "warning");
        toggleButtonLoading(saveBtn, false);
        return;
    }

    try {
        const action = state.editingEquipoId ? 'edit_equipo' : 'add_equipo';
        const result = await fetchAPI(`api/router.php?action=${action}`, { 
            method: 'POST', 
            body: formData 
        });

        if (result?.success) {
            ModalManager.hide('equipoModal');
            await handleDataChange('equipos', () => { 
                if(state.selectedEquipo?.id == result.id || action === 'add_equipo') {
                    selectEquipo(result.id);
                }
            });
            showToast(`Equipo ${state.editingEquipoId ? 'actualizado' : 'guardado'} con éxito.`);
        } else {
            showToast(`Error al guardar: ${result?.message || 'Error desconocido'}`, 'danger');
        }
    } finally {
        toggleButtonLoading(saveBtn, false);
    }
}

async function deleteEquipo(event, id) {
    event.stopPropagation();
    if (await ModalManager.ask({ title: 'Eliminar Equipo', message: '¿Seguro que quieres eliminar este equipo?', confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) {
        const result = await fetchAPI('api/router.php?action=delete_equipo', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
        if(result?.success) {
            if(state.selectedEquipo?.id == id) {
                document.getElementById('equipo-details-content').innerHTML = '<div class="initial-view"><i class="bi bi-tools" style="font-size: 5rem;"></i><h2 class="mt-3">Selecciona un equipo</h2><p class="lead text-muted">Haz clic en un equipo de la lista para ver sus detalles y vehículos asignados.</p></div>';
                state.selectedEquipo = null;
            }
            await handleDataChange('equipos');
            showToast('Equipo eliminado.');
        } else {
            showToast('Error al eliminar el equipo.', 'danger');
        }
    }
}

// --- Assignment & Unassignment ---

async function unassignEquipo(autoId, equipoId, fromEquipoView = false) {
    const result = await fetchAPI('api/router.php?action=unassign_equipo_from_auto', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ auto_id: autoId, equipo_id: equipoId }) });
    if (result.success) {
        if (fromEquipoView) {
             await handleDataChange('equipos', () => selectEquipo(equipoId));
        } else {
             await handleDataChange('autos', () => selectAuto(autoId));
        }
        showToast('Equipo desasignado.');
    } else {
        showToast('Error al desasignar el equipo.', 'danger');
    }
}

async function openAssignVehiclesToEquipoModal(equipoId, equipoName) {
    const modal = document.getElementById('assignVehiclesToEquipoModal');
    document.getElementById('assign-equipo-name').textContent = equipoName;
    modal.dataset.equipoId = equipoId;

    const allVehicles = await fetchAPI(`api/router.php?action=get_all_vehicles_for_equipo_assignment&equipo_id=${equipoId}`);
    if (allVehicles) {
        modal.dataset.allVehicles = JSON.stringify(allVehicles);
        renderVehiclesForEquipoAssignment();
        ModalManager.show('assignVehiclesToEquipoModal');
    } else {
        showToast('No se pudieron cargar los vehículos.', 'danger');
    }
}

function renderVehiclesForEquipoAssignment() {
    const modal = document.getElementById('assignVehiclesToEquipoModal');
    const allVehicles = JSON.parse(modal.dataset.allVehicles || '[]');
    const listContainer = document.getElementById('assign-vehicles-list-for-equipo');
    const searchTerm = document.getElementById('vehicle-search-for-equipo-modal').value.toLowerCase();
    
    const filteredVehicles = allVehicles.filter(v => {
        const searchText = `${v.marca} ${v.modelo} ${v.spec1 || ''} ${v.spec2 || ''}`.toLowerCase();
        return searchText.includes(searchTerm);
    });

    if (filteredVehicles.length === 0) {
        listContainer.innerHTML = '<p class="text-center text-muted mt-3">No hay vehículos que coincidan.</p>';
        return;
    }

    let html = '<ul class="list-group">';
    filteredVehicles.forEach(auto => {
        const isChecked = auto.is_assigned == 1;
        const yearText = formatYearRange(auto.anio_inicio, auto.anio_fin);
        const specText = [auto.spec1, auto.spec2].filter(Boolean).join(' / ');
        html += `<li class="list-group-item">
                    <label class="d-flex align-items-center w-100">
                        <input class="form-check-input me-3" type="checkbox" value="${auto.id}" ${isChecked ? 'checked' : ''}>
                        <div class="flex-grow-1">
                            <strong>${auto.marca} ${auto.modelo} - ${yearText}</strong>
                            ${specText ? `<div class="spec-details">${specText}</div>` : ''}
                        </div>
                    </label>
                </li>`;
    });
    html += '</ul>';
    listContainer.innerHTML = html;
}

async function saveVehicleToEquipoAssignments() {
    const saveBtn = document.getElementById('save-vehicle-to-equipo-assignments-btn');
    toggleButtonLoading(saveBtn, true);
    const modal = document.getElementById('assignVehiclesToEquipoModal');
    const equipoId = modal.dataset.equipoId;
    if (!equipoId) return;

    try {
        const selectedAutoIds = Array.from(modal.querySelectorAll('#assign-vehicles-list-for-equipo input:checked')).map(cb => parseInt(cb.value));
        const result = await fetchAPI('api/router.php?action=assign_equipo_to_multiple_autos', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ equipo_id: parseInt(equipoId), auto_ids: selectedAutoIds }) });
        if (result?.success) {
            ModalManager.hide('assignVehiclesToEquipoModal');
            await handleDataChange('equipos', () => selectEquipo(equipoId));
            showToast('Asignaciones de equipo guardadas.');
        } else {
            showToast(`Error al guardar: ${result?.message || 'Error desconocido'}`, 'danger');
        }
    } finally {
        toggleButtonLoading(saveBtn, false);
    }
}


// --- Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
    if(!document.getElementById('equipos-tab')) return;

    document.getElementById('equipoModal').addEventListener('hidden.bs.modal', () => {
        state.editingEquipoId = null;
        document.getElementById('equipoForm').reset();
    });

    document.getElementById('vehicle-search-for-equipo-modal').addEventListener('keyup', () => renderVehiclesForEquipoAssignment());
});
