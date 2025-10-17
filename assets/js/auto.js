// assets/js/auto.js

// --- State Slice for Autos ---
state.selectedAutos = new Set();
state.editingAutoId = null;
state.editingGroup = { marca: null, modelo: null };
state.lastUsedBrand = '';
state.rememberBrand = true;
state.lastUsedModel = '';
state.rememberModel = true;
state.expandedGroups = new Set();
state.manuallyExpandedBrands = new Set();
state.sortOrderAutos = 'marca-asc';

// --- Auto Rendering ---

/**
 * Renders the list of vehicles, grouped by brand and model.
 * @param {Array} brandsToRender - The filtered and sorted array of brand objects.
 */
function renderAutos(brandsToRender) {
    const list = document.getElementById('autos-list');
    list.innerHTML = '';
    const totalAutos = state.autos.reduce((acc, brand) => acc + brand.modelos.reduce((mAcc, model) => mAcc + model.versions.length, 0), 0);
    document.getElementById('auto-count').textContent = totalAutos;
    
    const isSearching = document.getElementById('auto-search').value || document.getElementById('adv-auto-marca').value || document.getElementById('adv-auto-modelo').value || document.getElementById('adv-auto-anio').value || document.getElementById('adv-auto-spec').value;

    if (brandsToRender.length === 0) {
        list.innerHTML = getEmptyStateHTML('bi bi-car-front', 'No se encontraron vehículos', 'Intenta ajustar tus criterios de búsqueda o añade uno nuevo.');
        return;
    }

    brandsToRender.forEach((brand, brandIndex) => {
        const brandId = `brand-collapse-${brandIndex}`;
        const brandContainer = document.createElement('div');
        brandContainer.className = 'accordion-item mb-2';
        const isExpanded = !!isSearching || state.manuallyExpandedBrands.has(brand.marca);

        let brandModelsHtml = '';
        brand.modelos.forEach((group, modelIndex) => {
            const groupId = `group-${brand.marca}-${group.modelo}`.replace(/[\s\W]/g, '_');
            const groupVersionIds = group.versions.map(v => parseInt(v.id));

            if (group.versions.length === 1) {
                const auto = group.versions[0];
                const isActive = state.selectedAuto?.id == auto.id;
                const yearText = formatYearRange(auto.anio_inicio, auto.anio_fin);
                const specText = [auto.spec1, auto.spec2].filter(Boolean).join(' / ');
                brandModelsHtml += `
                    <div class="list-group-item single-version-item d-flex align-items-center gap-3 ${isActive ? 'active' : ''}" data-auto-id="${auto.id}" onclick="selectAuto(${auto.id})">
                        <div class="form-check"><input class="form-check-input" type="checkbox" value="${auto.id}" onclick="event.stopPropagation()" onchange="handleSelectionChange('autos', parseInt(this.value), this.checked)" ${state.selectedAutos.has(parseInt(auto.id)) ? 'checked' : ''}></div>
                        <div class="flex-grow-1"><div><strong class="text-primary">${group.modelo}</strong></div><div class="fw-bold">${yearText}</div>${specText ? `<div class="spec-details">${specText}</div>` : ''}</div>
                        <div class="action-buttons btn-group">
                            <button class="btn btn-sm btn-outline-secondary" onclick="openEditAutoModal(event, ${auto.id}, '${brand.marca}', '${group.modelo}')" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAuto(event, ${auto.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>`;
            } else {
                const collapseId = `collapse-group-${brandIndex}-${modelIndex}`;
                const isGroupExpanded = state.expandedGroups.has(groupId);
                const areAllSelected = groupVersionIds.every(id => state.selectedAutos.has(id));
                const isIndeterminate = !areAllSelected && groupVersionIds.some(id => state.selectedAutos.has(id));
                
                let childItemsHtml = group.versions.map(auto => {
                    const isActive = state.selectedAuto?.id == auto.id;
                    const yearText = formatYearRange(auto.anio_inicio, auto.anio_fin);
                    const specText = [auto.spec1, auto.spec2].filter(Boolean).join(' / ');
                    return `
                        <div class="list-group-item child-item d-flex align-items-center gap-3 ${isActive ? 'active' : ''}" data-auto-id="${auto.id}" onclick="selectAuto(${auto.id})">
                            <div class="form-check"><input class="form-check-input" type="checkbox" value="${auto.id}" onclick="event.stopPropagation()" onchange="handleSelectionChange('autos', parseInt(this.value), this.checked, '${groupId}')" ${state.selectedAutos.has(parseInt(auto.id)) ? 'checked' : ''}></div>
                            <div class="flex-grow-1"><div class="fw-bold">${yearText}</div>${specText ? `<div class="spec-details">${specText}</div>` : ''}</div>
                            <div class="action-buttons btn-group">
                                <button class="btn btn-sm btn-outline-secondary" onclick="openEditAutoModal(event, ${auto.id}, '${brand.marca}', '${group.modelo}')" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteAuto(event, ${auto.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>`;
                }).join('');

                brandModelsHtml += `
                    <div class="list-group-item-container mb-2">
                        <div class="list-group-item parent-item d-flex align-items-center gap-3">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="${groupId}" onchange="handleGroupSelectionChange(this.checked, [${groupVersionIds.join(',')}])" ${areAllSelected ? 'checked' : ''} ${isIndeterminate ? 'class="form-check-input indeterminate"' : ''} onclick="event.stopPropagation()"><label class="form-check-label" for="${groupId}" onclick="event.stopPropagation()"></label></div>
                            <div class="d-flex align-items-center flex-grow-1" data-bs-toggle="collapse" href="#${collapseId}" aria-expanded="${isGroupExpanded}">
                                <div class="flex-grow-1"><strong class="text-primary">${group.modelo}</strong></div>
                                <span class="badge bg-secondary rounded-pill mx-3">${group.versions.length} versiones</span><i class="bi bi-chevron-down"></i>
                            </div>
                            <div class="action-buttons btn-group">
                                <button class="btn btn-sm btn-outline-secondary" onclick="openEditGroupModal(event, '${brand.marca}', '${group.modelo}')" title="Editar Grupo"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteAutoGroup(event, '${brand.marca}', '${group.modelo}')" title="Eliminar Grupo"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <div class="collapse child-item-container ${isGroupExpanded ? 'show' : ''}" id="${collapseId}" data-group-id="${groupId}">
                            <div class="list-group">${childItemsHtml}</div>
                        </div>
                    </div>`;
            }
        });

        brandContainer.innerHTML = `
            <h2 class="accordion-header">
                <button class="accordion-button ${isExpanded ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#${brandId}" aria-expanded="${isExpanded}">
                    ${brand.marca}
                </button>
            </h2>
            <div id="${brandId}" class="accordion-collapse collapse ${isExpanded ? 'show' : ''}" data-brand-name="${brand.marca}">
                <div class="accordion-body p-2" style="display: flex; flex-direction: column; gap: 0.5rem;">
                    ${brandModelsHtml}
                </div>
            </div>
        `;
        list.appendChild(brandContainer);
    });
    
    document.querySelectorAll('.child-item-container.collapse').forEach(el => {
        el.addEventListener('shown.bs.collapse', () => state.expandedGroups.add(el.dataset.groupId));
        el.addEventListener('hidden.bs.collapse', () => state.expandedGroups.delete(el.dataset.groupId));
    });
    
    document.querySelectorAll('.indeterminate').forEach(el => el.indeterminate = true);
    updateMassActionButtons('autos');
}

/**
 * Renders the details of a selected vehicle.
 * @param {object} auto - The vehicle object with its details.
 */
function renderAutoDetails(auto) {
    state.selectedAuto = auto;
    const content = document.getElementById('auto-details-content');
    
    let itemsHtml = '<div class="list-group-item text-muted text-center">No hay ítems asignados.</div>';
    if (auto.items && auto.items.length > 0) {
        itemsHtml = auto.items.map(item => `<li class="list-group-item d-flex justify-content-between align-items-center"><div><div class="fw-bold">${item.nombre}</div>${item.ubicacion ? `<small class="text-muted me-2"><i class="bi bi-geo-alt-fill"></i> ${item.ubicacion}</small>`: ''}<small class="text-muted">${item.nombres_secundarios || ''}</small></div><button class="btn btn-sm btn-outline-danger" onclick="unassignItem(${auto.id}, ${item.id})"><i class="bi bi-trash"></i></button></li>`).join('');
    }
    
    let equiposHtml = '<div class="list-group-item text-muted text-center">No hay equipos asignados.</div>';
    if (auto.equipos && auto.equipos.length > 0) {
        equiposHtml = auto.equipos.map(equipo => `<li class="list-group-item d-flex justify-content-between align-items-center"><div><div class="fw-bold">${equipo.nombre}</div><small class="text-muted">${equipo.notas || ''}</small></div><button class="btn btn-sm btn-outline-danger" onclick="unassignEquipo(${auto.id}, ${equipo.id})"><i class="bi bi-trash"></i></button></li>`).join('');
    }

    const yearText = formatYearRange(auto.anio_inicio, auto.anio_fin);
    content.innerHTML = `
        <div class="card">
            <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="me-auto">
                    <h2 class="h4 mb-0">${auto.marca} ${auto.modelo}</h2>
                    <span class="badge bg-secondary">${yearText}</span>
                </div>
                <div>
                    <button class="btn btn-sm btn-success" onclick="startJobFromVehicle(${auto.id})">
                        <i class="bi bi-journal-plus"></i> Crear Trabajo
                    </button>
                </div>
            </div>
            <div class="card-body" style="overflow-y: auto;">
                <h5 class="card-title">Especificaciones</h5>
                <p><strong>Spec 1:</strong> ${auto.spec1 || 'N/A'}<br><strong>Spec 2:</strong> ${auto.spec2 || 'N/A'}</p>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Ítems Asociados</h5>
                    <button class="btn btn-primary btn-sm" onclick="openAssignItemToMultipleAutosModal(true)"><i class="bi bi-plus-lg"></i> Asignar Ítem</button>
                </div>
                <ul class="list-group mb-4">${itemsHtml}</ul>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Equipos Asociados</h5>
                </div>
                <ul class="list-group">${equiposHtml}</ul>
            </div>
        </div>`;
    document.querySelector('#autos-list .active')?.classList.remove('active');
    document.querySelector(`[data-auto-id='${auto.id}']`)?.classList.add('active');
}

/**
 * Renders brand suggestions for autocomplete fields.
 * @param {string[]} suggestions - An array of brand name strings.
 */
function renderBrandSuggestions(suggestions) {
    const container = document.getElementById('marcas-suggestions');
    if (!container) return;
    container.innerHTML = '';
    if (suggestions.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = 'block';
    suggestions.forEach(brand => {
        const div = document.createElement('div');
        div.className = 'list-group-item list-group-item-action autocomplete-suggestion';
        div.innerHTML = `<span>${brand}</span>`;
        div.onclick = () => selectBrandSuggestion(brand);
        container.appendChild(div);
    });
}

// --- Filtering, Sorting, and Searching ---

/**
 * Filters the list of autos based on search criteria and re-renders the list.
 */
function updateAndRenderAutos() {
    // --- MODIFICATION START ---
    // This is the key fix. We check if the main 'autos-list' container exists.
    // If it doesn't, we know we are not on the Vehículos page, so we exit the function.
    const autosList = document.getElementById('autos-list');
    if (!autosList) {
        return;
    }
    // --- MODIFICATION END ---

    const filtered = filterAutos();
    const sorted = sortAutos(filtered);
    renderAutos(sorted);
}


/**
 * Filters the main auto list based on criteria from search inputs.
 * @returns {Array} The filtered array of brand objects.
 */
function filterAutos() {
    const criteria = {
        quickSearch: document.getElementById('auto-search').value.toLowerCase(),
        marca: document.getElementById('adv-auto-marca').value.toLowerCase(),
        modelo: document.getElementById('adv-auto-modelo').value.toLowerCase(),
        anio: document.getElementById('adv-auto-anio').value,
        spec: document.getElementById('adv-auto-spec').value.toLowerCase()
    };
    return state.autos.map(brand => {
        if (criteria.marca && !brand.marca.toLowerCase().includes(criteria.marca)) return null;
        const filteredModelos = brand.modelos.map(model => {
            if (criteria.modelo && !model.modelo.toLowerCase().includes(criteria.modelo)) return null;
            const filteredVersions = model.versions.filter(v => {
                const quickSearchText = `${brand.marca} ${model.modelo} ${v.spec1 || ''} ${v.spec2 || ''}`.toLowerCase();
                if (criteria.quickSearch && !quickSearchText.includes(criteria.quickSearch)) return false;
                const year = parseInt(criteria.anio, 10);
                if (year) {
                    const start = parseInt(v.anio_inicio, 10) || year;
                    const end = parseInt(v.anio_fin, 10) || start;
                    if (!(year >= start && year <= end)) return false;
                }
                if (criteria.spec) {
                    const spec1Match = v.spec1 && v.spec1.toLowerCase().includes(criteria.spec);
                    const spec2Match = v.spec2 && v.spec2.toLowerCase().includes(criteria.spec);
                    if (!spec1Match && !spec2Match) return false;
                }
                return true;
            });
            return { ...model, versions: filteredVersions };
        }).filter(m => m && m.versions.length > 0);
        return { ...brand, modelos: filteredModelos };
    }).filter(b => b && b.modelos.length > 0);
}

/**
 * Sorts an array of brand objects based on the current sort order.
 * @param {Array} brandsToSort - The array of brands to sort.
 * @returns {Array} The sorted array.
 */
function sortAutos(brandsToSort) {
    const sortedBrands = [...brandsToSort];
    switch (state.sortOrderAutos) {
        case 'marca-asc':
            sortedBrands.sort((a, b) => a.marca.localeCompare(b.marca));
            break;
        case 'marca-desc':
            sortedBrands.sort((a, b) => b.marca.localeCompare(a.marca));
            break;
        case 'modelo-asc':
        case 'modelo-desc':
            sortedBrands.sort((a, b) => a.marca.localeCompare(b.marca));
            sortedBrands.forEach(brand => {
                brand.modelos.sort((a, b) => {
                    return state.sortOrderAutos === 'modelo-asc'
                        ? a.modelo.localeCompare(b.modelo)
                        : b.modelo.localeCompare(a.modelo);
                });
            });
            break;
        case 'creacion-asc':
        case 'creacion-desc':
            sortedBrands.sort((a, b) => a.marca.localeCompare(b.marca));
            sortedBrands.forEach(brand => {
                brand.modelos.sort((a, b) => a.modelo.localeCompare(b.modelo));
                brand.modelos.forEach(model => {
                    model.versions.sort((a, b) => {
                        return state.sortOrderAutos === 'creacion-desc'
                            ? parseInt(b.id) - parseInt(a.id)
                            : parseInt(a.id) - parseInt(b.id);
                    });
                });
            });
            break;
    }
    return sortedBrands;
}

function performAutoSearch() { updateAndRenderAutos(); }
function resetAutoSearch() { document.getElementById('auto-search').value = ''; document.getElementById('adv-auto-marca').value = ''; document.getElementById('adv-auto-modelo').value = ''; document.getElementById('adv-auto-anio').value = ''; document.getElementById('adv-auto-spec').value = ''; performAutoSearch(); }


// --- Selection ---

/**
 * Fetches and displays the details for a selected vehicle.
 * @param {number} id - The ID of the vehicle to select.
 */
async function selectAuto(id) {
    const autoDetails = await fetchAPI(`api/router.php?action=get_auto_details&id=${id}`);
    if (autoDetails) {
        renderAutoDetails(autoDetails);
        if (isMobileView()) {
            showDetailsView('autos');
        }
    }
}

/**
 * Handles changes to a selection checkbox for an individual auto.
 * @param {string} type - The type of item being selected ('autos').
 * @param {number} id - The ID of the auto.
 * @param {boolean} isChecked - The new checked state.
 * @param {string|null} [groupId=null] - The ID of the group checkbox, if applicable.
 */
function handleSelectionChange(type, id, isChecked, groupId = null) {
    if (type !== 'autos') return;
    const set = state.selectedAutos;
    if (isChecked) set.add(id);
    else set.delete(id);
    if (groupId) updateGroupCheckboxState(groupId);
    updateMassActionButtons(type);
}

/**
 * Handles changes to a group selection checkbox.
 * @param {boolean} isChecked - The new checked state.
 * @param {number[]} versionIds - An array of auto IDs in the group.
 */
function handleGroupSelectionChange(isChecked, versionIds) {
    versionIds.forEach(id => {
        if (isChecked) state.selectedAutos.add(id);
        else state.selectedAutos.delete(id);
    });
    updateAndRenderAutos();
}

/**
 * Updates the state (checked/indeterminate) of a group checkbox based on its children.
 * @param {string} groupId - The ID of the group checkbox.
 */
function updateGroupCheckboxState(groupId) {
    const brand = state.autos.find(b => b.modelos.some(m => `group-${b.marca}-${m.modelo}`.replace(/[\s\W]/g, '_') === groupId));
    if (!brand) return;
    const model = brand.modelos.find(m => `group-${brand.marca}-${m.modelo}`.replace(/[\s\W]/g, '_') === groupId);
    if (!model) return;
    const groupVersionIds = model.versions.map(v => parseInt(v.id));
    const allSelected = groupVersionIds.every(id => state.selectedAutos.has(id));
    const someSelected = !allSelected && groupVersionIds.some(id => state.selectedAutos.has(id));
    const groupCheckbox = document.getElementById(groupId);
    if (groupCheckbox) {
        groupCheckbox.checked = allSelected;
        groupCheckbox.indeterminate = someSelected;
    }
}

/**
 * Toggles the selection of all visible autos.
 * @param {HTMLInputElement} checkbox - The 'select all' checkbox element.
 */
function toggleSelectAllAutos(checkbox) {
    const allVisibleIds = filterAutos().flatMap(b => b.modelos).flatMap(m => m.versions).map(v => parseInt(v.id));
    if (checkbox.checked) {
        allVisibleIds.forEach(id => state.selectedAutos.add(id));
    } else {
        state.selectedAutos.clear();
    }
    updateAndRenderAutos();
}

/**
 * Updates the state and text of mass action buttons for autos.
 * @param {string} type - The section type ('autos').
 */
function updateMassActionButtons(type) {
    if (type !== 'autos') return;
    const count = state.selectedAutos.size;
    const deleteBtn = document.getElementById('mass-delete-autos-btn');
    if (deleteBtn) {
        deleteBtn.disabled = count === 0;
        deleteBtn.innerHTML = `<i class="bi bi-trash-fill"></i> Eliminar (${count})`;
    }
    const assignBtn = document.getElementById('mass-assign-item-btn');
    if (assignBtn) {
        assignBtn.disabled = count === 0;
        assignBtn.innerHTML = `<i class="bi bi-link-45deg"></i> Asignar Ítem (${count})`;
    }
}

// --- Modals & Forms ---

/**
 * Opens the modal to add a new vehicle.
 */
function openAddAutoModal() {
    if (!state.editingAutoId && (!state.editingGroup.marca || !state.editingGroup.modelo)) {
        const form = document.getElementById('addAutoForm');
        form.reset();
        document.getElementById('autoModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Añadir Vehículo';
        form.querySelector('#remember-brand-toggle').checked = state.rememberBrand;
        if (state.rememberBrand && state.lastUsedBrand) {
            form.querySelector('[name="marca"]').value = state.lastUsedBrand;
        }
        form.querySelector('#remember-model-toggle').checked = state.rememberModel;
        if (state.rememberModel && state.lastUsedModel) {
            form.querySelector('[name="modelo"]').value = state.lastUsedModel;
        }
        document.getElementById('year-ranges-field').style.display = 'block';
        document.getElementById('global-specs-container').style.display = 'block';
        document.getElementById('dynamic-specs-container').innerHTML = '';
        document.getElementById('dynamic-specs-container').style.display = 'block';
    }
    ModalManager.show('addAutoModal');
}

/**
 * Opens the modal to edit an existing vehicle.
 * @param {Event} event - The click event.
 * @param {number} id - The ID of the auto to edit.
 * @param {string} marca - The brand of the auto.
 * @param {string} modelo - The model of the auto.
 */
function openEditAutoModal(event, id, marca, modelo) {
    event.stopPropagation();
    state.editingGroup = { marca: null, modelo: null }; 
    
    let autoToEdit = null;
    const brand = state.autos.find(b => b.marca === marca);
    if(brand){
        const model = brand.modelos.find(m => m.modelo === modelo);
        if(model){
            autoToEdit = model.versions.find(v => v.id == id);
        }
    }
    
    if (!autoToEdit) { console.error("Auto not found for ID:", id); return; }
    
    state.editingAutoId = id;
    const form = document.getElementById('addAutoForm');
    form.querySelector('#auto-id').value = autoToEdit.id;
    form.querySelector('[name="marca"]').value = marca;
    form.querySelector('[name="modelo"]').value = modelo;
    const yearRangesTextarea = form.querySelector('[name="year_ranges"]');
    const start = autoToEdit.anio_inicio; const end = autoToEdit.anio_fin;
    if (start != null) yearRangesTextarea.value = (start == end || end == null) ? start : `${start}-${end}`; else yearRangesTextarea.value = '';
    form.querySelector('[name="spec1"]').value = autoToEdit.spec1 || '';
    form.querySelector('[name="spec2"]').value = autoToEdit.spec2 || '';
    document.getElementById('autoModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> Editar Vehículo';
    document.getElementById('year-ranges-field').style.display = 'block';
    document.getElementById('global-specs-container').style.display = 'block';
    document.getElementById('dynamic-specs-container').style.display = 'none';
    ModalManager.show('addAutoModal');
}

/**
 * Opens the modal to edit a vehicle group (brand/model).
 * @param {Event} event - The click event.
 * @param {string} marca - The brand of the group.
 * @param {string} modelo - The model of the group.
 */
function openEditGroupModal(event, marca, modelo) {
    event.stopPropagation();
    state.editingGroup = { marca, modelo }; state.editingAutoId = null; 
    const form = document.getElementById('addAutoForm');
    form.querySelector('#auto-id').value = ''; 
    form.querySelector('[name="marca"]').value = marca;
    form.querySelector('[name="modelo"]').value = modelo;
    form.querySelector('[name="year_ranges"]').value = '';
    form.querySelector('[name="spec1"]').value = '';
    form.querySelector('[name="spec2"]').value = '';
    document.getElementById('autoModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> Editar Grupo de Vehículos';
    document.getElementById('year-ranges-field').style.display = 'none';
    document.getElementById('global-specs-container').style.display = 'none';
    document.getElementById('dynamic-specs-container').style.display = 'none';
    ModalManager.show('addAutoModal');
}

// --- Save & Delete ---

function parseYearRange(text) { const trimmedText = text.trim(); if (trimmedText === '') return { anio_inicio: '0', anio_fin: '0' }; const parts = trimmedText.split('-'); if (parts.length === 1) return { anio_inicio: parts[0].trim(), anio_fin: parts[0].trim() }; if (parts.length >= 2) return { anio_inicio: (parts[0].trim() || '0'), anio_fin: (parts[1].trim() || '0') }; return { anio_inicio: '0', anio_fin: '0' }; }

/**
 * Saves a new or edited vehicle/group.
 */
async function saveAuto() {
    const saveBtn = document.getElementById('saveAutoBtn');
    toggleButtonLoading(saveBtn, true);
    const form = document.getElementById('addAutoForm'); const marca = form.querySelector('[name="marca"]').value; const modelo = form.querySelector('[name="modelo"]').value; const yearRangesText = form.querySelector('[name="year_ranges"]').value;
    if (!marca || !modelo) { showToast("Marca y Modelo son requeridos.", "warning"); toggleButtonLoading(saveBtn, false); return; }
    
    try {
        if (state.editingGroup.marca && state.editingGroup.modelo) {
            const data = { old_marca: state.editingGroup.marca, old_modelo: state.editingGroup.modelo, new_marca: marca, new_modelo: modelo };
            const result = await fetchAPI(`api/router.php?action=edit_auto_group`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            if (result?.success) { ModalManager.hide('addAutoModal'); await handleDataChange('autos'); showToast('Grupo actualizado con éxito.'); } 
            else { showToast(`Error al editar el grupo: ${result.message || 'Error desconocido'}`, 'danger'); }
            state.editingGroup = { marca: null, modelo: null }; return;
        }
        if (state.editingAutoId) {
            const parsedRange = parseYearRange(yearRangesText);
            const data = { id: state.editingAutoId, marca, modelo, spec1: form.querySelector('[name="spec1"]').value, spec2: form.querySelector('[name="spec2"]').value, anio_inicio: parsedRange.anio_inicio, anio_fin: parsedRange.anio_fin };
            const result = await fetchAPI(`api/router.php?action=edit_auto`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            if (result?.success) { ModalManager.hide('addAutoModal'); await handleDataChange('autos', () => { if (state.selectedAuto?.id == result.id) selectAuto(result.id); }); showToast('Vehículo actualizado con éxito.'); } 
            else showToast(`Error al editar: ${result.message || 'Error desconocido'}`, 'danger'); return;
        }

        const useDynamicSpecs = document.getElementById('global-specs-container').style.display === 'none';
        let initialData;
        if (useDynamicSpecs) {
            const ranges = yearRangesText.split(',').map(r => r.trim()).filter(Boolean);
            const rangesWithSpecs = ranges.map((rangeText, index) => { const parsedRange = parseYearRange(rangeText); const spec1Input = document.querySelector(`.dynamic-spec1[data-range-index="${index}"]`); const spec2Input = document.querySelector(`.dynamic-spec2[data-range-index="${index}"]`); return { ...parsedRange, spec1: spec1Input ? spec1Input.value : null, spec2: spec2Input ? spec2Input.value : null }; });
            initialData = { marca, modelo, ranges: rangesWithSpecs };
        } else {
            const spec1 = form.querySelector('[name="spec1"]').value; const spec2 = form.querySelector('[name="spec2"]').value;
            let parsedRanges; if (!yearRangesText.trim()) parsedRanges = [{ anio_inicio: '0', anio_fin: '0' }]; else parsedRanges = yearRangesText.split(',').map(r => r.trim()).filter(Boolean).map(parseYearRange);
            const rangesWithSpecs = parsedRanges.map(range => ({ ...range, spec1, spec2 }));
            initialData = { marca, modelo, ranges: rangesWithSpecs };
        }
        
        if (initialData.ranges.length === 0) { showToast("Por favor, ingrese al menos un rango de años válido.", "warning"); return; }
        
        const result = await fetchAPI(`api/router.php?action=add_multiple_autos`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(initialData) });
        
        if (result?.success) { 
            if (state.rememberBrand) state.lastUsedBrand = marca; 
            if (state.rememberModel) state.lastUsedModel = modelo; 
            ModalManager.hide('addAutoModal'); 
            await handleDataChange('autos'); 
            showToast('Vehículo(s) añadido(s) con éxito.');
        } else if (result?.error === 'overlap_warning') {
            const confirmed = await ModalManager.ask({
                title: 'Conflicto Detectado',
                message: `${result.message}<br><br><strong>Conflictos:</strong><ul>${result.conflicts.map(c => `<li>${c}</li>`).join('')}</ul><br>¿Deseas forzar el guardado de todos modos?`,
                confirmText: 'Forzar Guardado',
                confirmButtonClass: 'btn-warning',
            });

            if(confirmed) {
                toggleButtonLoading(saveBtn, true);
                const forceResult = await fetchAPI(`api/router.php?action=add_multiple_autos`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...initialData, force: true }) });
                if (forceResult?.success) { if (state.rememberBrand) state.lastUsedBrand = marca; if (state.rememberModel) state.lastUsedModel = modelo; ModalManager.hide('addAutoModal'); await handleDataChange('autos'); showToast('Vehículos guardados con éxito.'); } 
                else showToast(`Error al forzar el guardado: ${forceResult?.message || 'Error desconocido.'}`, 'danger');
                toggleButtonLoading(saveBtn, false);
            }
        } else {
             showToast(`Error al guardar: ${result?.message || 'Error desconocido.'}`, 'danger');
        }
    } finally {
        toggleButtonLoading(saveBtn, false);
    }
}

/**
 * Deletes a single vehicle after confirmation.
 * @param {Event} event - The click event.
 * @param {number} id - The ID of the vehicle to delete.
 */
async function deleteAuto(event, id) { 
    event.stopPropagation(); 
    if (await ModalManager.ask({ title: 'Eliminar Vehículo', message: '¿Seguro que quieres eliminar este vehículo? Esta acción es irreversible.', confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) {
        const result = await fetchAPI('api/router.php?action=delete_auto', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) }); 
        if(result?.success) { 
            if(state.selectedAuto?.id == id) { 
                document.getElementById('auto-details-content').innerHTML = '<div class="initial-view"><i class="bi bi-arrow-left-circle" style="font-size: 5rem;"></i><h2 class="mt-3">Selecciona un vehículo</h2><p class="lead text-muted">Haz clic en una versión de vehículo para ver sus detalles.</p></div>'; 
                state.selectedAuto = null; 
            } 
            await handleDataChange('autos'); 
            showToast('Vehículo eliminado.'); 
        } else showToast('Error al eliminar.', 'danger');
    }
}

/**
 * Deletes an entire vehicle group after confirmation.
 * @param {Event} event - The click event.
 * @param {string} marca - The brand of the group.
 * @param {string} modelo - The model of the group.
 */
async function deleteAutoGroup(event, marca, modelo) { 
    event.stopPropagation(); 
    if (await ModalManager.ask({ title: 'Eliminar Grupo de Vehículos', message: `¿Estás seguro de que quieres eliminar TODOS los vehículos del grupo "${marca} ${modelo}"? Esta acción es irreversible.`, confirmText: 'Eliminar Grupo', confirmButtonClass: 'btn-danger' })) {
        const data = { marca, modelo }; 
        const result = await fetchAPI('api/router.php?action=delete_auto_group', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }); 
        if (result?.success) { 
            if (state.selectedAuto && state.selectedAuto.marca === marca && state.selectedAuto.modelo === modelo) { 
                document.getElementById('auto-details-content').innerHTML = '<div class="initial-view"><i class="bi bi-arrow-left-circle" style="font-size: 5rem;"></i><h2 class="mt-3">Selecciona un vehículo</h2><p class="lead text-muted">Haz clic en una versión de vehículo para ver sus detalles.</p></div>'; 
                state.selectedAuto = null; 
            } 
            await handleDataChange('autos'); 
            showToast('Grupo de vehículos eliminado.'); 
        } else showToast(`Error al eliminar el grupo: ${result.message || 'Error desconocido'}`, 'danger'); 
    }
}

/**
 * Deletes all selected vehicles after confirmation.
 */
async function massDeleteAutos() {
    const ids = Array.from(state.selectedAutos);
    if (ids.length === 0) return;
    if (await ModalManager.ask({ title: `Eliminación Masiva`, message: `¿Estás seguro de que quieres eliminar ${ids.length} vehículo(s) seleccionados?`, confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) {
        const result = await fetchAPI(`api/router.php?action=mass_delete_autos`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids }) });
        if (result?.success) {
            state.selectedAutos.clear();
            const selectAllCheckbox = document.getElementById(`select-all-autos`);
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            await handleDataChange('autos');
            showToast(`Vehículos eliminados.`);
        } else showToast(`Error en la eliminación masiva de vehículos.`, 'danger');
    }
}

/**
 * Starts a new job pre-filled with a specific vehicle.
 * @param {number} autoId - The ID of the vehicle to start the job with.
 */
async function startJobFromVehicle(autoId) {
    // Switch to the 'Trabajos' tab
    const trabajosTabEl = document.getElementById('trabajos-tab');
    if (trabajosTabEl) {
        const tab = bootstrap.Tab.getOrCreateInstance(trabajosTabEl);
        tab.show();
    } else {
        console.error('Could not find the trabajos tab.');
        return;
    }

    // Open the modal
    await openAddTrabajoModal();

    // After a short delay to ensure the modal is fully ready and populated,
    // select the vehicle and trigger the sync logic.
    setTimeout(() => {
        const autoSelect = document.getElementById('trabajo-auto-select');
        if (autoSelect) {
            autoSelect.value = autoId;
            // Dispatch a change event to trigger the item/equipo loading logic
            autoSelect.dispatchEvent(new Event('change'));
        } else {
            console.error('Could not find the vehicle select in the job modal.');
        }
    }, 250); // 250ms delay seems safe for modal transition
}


// --- Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('vehiculos-tab')) return;

    document.getElementById('addAutoModal').addEventListener('hidden.bs.modal', () => { 
        state.editingAutoId = null; 
        state.editingGroup = { marca: null, modelo: null }; 
        document.getElementById('addAutoForm').reset(); 
        document.getElementById('dynamic-specs-container').innerHTML = ''; 
        document.getElementById('global-specs-container').style.display = 'block'; 
    });

    document.getElementById('auto-search').addEventListener('keyup', () => updateAndRenderAutos());
    document.getElementById('auto-sort-select').addEventListener('change', (e) => {
        state.sortOrderAutos = e.target.value;
        updateAndRenderAutos();
    });

    document.getElementById('remember-brand-toggle').addEventListener('change', (e) => { state.rememberBrand = e.target.checked; });
    document.getElementById('remember-model-toggle').addEventListener('change', (e) => { state.rememberModel = e.target.checked; });
    
    const yearRangesTextarea = document.getElementById('year-ranges'), 
          dynamicSpecsContainer = document.getElementById('dynamic-specs-container'), 
          globalSpecsContainer = document.getElementById('global-specs-container');
    
    function updateGlobalSpecInputsState() { 
        const dynamicInputs = dynamicSpecsContainer.querySelectorAll('.dynamic-spec1, .dynamic-spec2'); 
        const hasDynamicFields = dynamicInputs.length > 0; 
        const anyDynamicSpecUsed = hasDynamicFields && Array.from(dynamicInputs).some(input => input.value.trim() !== ''); 
        if (anyDynamicSpecUsed) globalSpecsContainer.style.display = 'none'; else globalSpecsContainer.style.display = 'block'; 
    }
    
    dynamicSpecsContainer.addEventListener('input', (e) => { if (e.target.classList.contains('dynamic-spec1') || e.target.classList.contains('dynamic-spec2')) updateGlobalSpecInputsState(); });
    
    yearRangesTextarea.addEventListener('input', () => { 
        if (state.editingAutoId) return; 
        const value = yearRangesTextarea.value; 
        const hasComma = value.includes(','); 
        dynamicSpecsContainer.innerHTML = ''; 
        if (hasComma) { 
            const ranges = value.split(',').map(r => r.trim()); 
            ranges.forEach((range, index) => { 
                if (range) { 
                    const specFieldsHtml = `<div class="p-2 mb-2 border rounded bg-body-tertiary"><label class="form-label fw-bold">Especificaciones para <span class="text-primary">"${range}"</span></label><div class="row g-2"><div class="col"><input type="text" class="form-control form-control-sm dynamic-spec1" placeholder="Spec 1" list="spec1-list" data-range-index="${index}"></div><div class="col"><input type="text" class="form-control form-control-sm dynamic-spec2" placeholder="Spec 2" list="spec2-list" data-range-index="${index}"></div></div></div>`; 
                    dynamicSpecsContainer.insertAdjacentHTML('beforeend', specFieldsHtml); 
                } 
            }); 
        } 
        updateGlobalSpecInputsState(); 
    });
        
    const autosList = document.getElementById('autos-list');
    autosList.addEventListener('shown.bs.collapse', e => {
        const brandName = e.target.dataset.brandName;
        if(brandName) state.manuallyExpandedBrands.add(brandName);
    });
    autosList.addEventListener('hidden.bs.collapse', e => {
        const brandName = e.target.dataset.brandName;
        if(brandName) state.manuallyExpandedBrands.delete(brandName);
    });
    
    document.getElementById('addAutoModal').addEventListener('keydown', function(e) { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); document.getElementById('saveAutoBtn').click(); } });
});

