// assets/js/trabajo-form.js

// --- Form Page Logic ---

document.addEventListener('DOMContentLoaded', () => {
    // Only execute this script's logic on the trabajo-form page
    const formElement = document.getElementById('trabajoForm');
    if (!formElement) {
        return;
    }

    // Initialize state slice for this specific page
    state.editingTrabajoId = null;
    state.trabajoForm = { items: new Map() };
    state.allCortes = null;

    // Anonymous async function to run the page setup
    const initializeForm = async () => {
        try {
            // Check if main functions are available
            if (typeof refreshData !== 'function' || typeof showToast !== 'function') {
                console.error("The global 'refreshData' or 'showToast' functions are not defined. This form may not work correctly.");
                alert("Error crítico: Las funciones principales de la aplicación no están disponibles. La página puede no funcionar.");
                return;
            }
            
            // --- STRATEGIC FIX START ---
            // On this form page, the global `refreshData()` function calls rendering functions
            // (like renderTrabajos, renderAutos) that are meant for the main listing page.
            // These functions will fail here because the required HTML elements (tables, stats cards) do not exist.
            // To prevent a crash, we temporarily replace them with empty dummy functions just before calling refreshData().
            const functionsToOverride = [
                'renderFinancialStats', 'renderTrabajos', 'renderTiposTrabajoManagement', // from trabajo.js
                'renderAutos',      // from auto.js (assumed name)
                'renderItems', 'renderTags', // from item.js (assumed names)
                'renderEquipos',    // from equipo.js (assumed name)
                'renderClientes'    // from cliente.js (assumed name)
            ];
            
            const originalFunctions = {};
            functionsToOverride.forEach(funcName => {
                // Check if the function exists on the window object before overriding
                if (typeof window[funcName] === 'function') {
                    originalFunctions[funcName] = window[funcName]; // Save the original function
                    window[funcName] = () => {}; // Override with an empty function that does nothing
                }
            });
            // --- STRATEGIC FIX END ---

            console.log("Form page detected. Attempting to refresh data...");
            await refreshData(true); // This call is now safe from side effects.
            
            // --- RESTORE ORIGINAL FUNCTIONS ---
            // (Good practice) Restore the original functions after the data refresh is complete.
            for (const funcName in originalFunctions) {
                window[funcName] = originalFunctions[funcName];
            }
            // --- END RESTORE ---

            console.log("Data refreshed successfully. Current state:", JSON.parse(JSON.stringify(state)));

            const urlParams = new URLSearchParams(window.location.search);
            const trabajoId = urlParams.get('id');

            if (trabajoId) {
                console.log(`Editing trabajo with ID: ${trabajoId}`);
                document.getElementById('trabajo-form-title').innerHTML = '<i class="bi bi-pencil-fill"></i> Editar Trabajo';
                await loadTrabajoForEditing(trabajoId);
            } else {
                console.log("Creating a new trabajo.");
                document.getElementById('trabajo-form-title').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Añadir Nuevo Trabajo';
                formElement.reset();
                await populateFormSelectors();
                renderFormItemsList();
            }
            
            initializeEventListeners();
            console.log("Form initialized successfully.");

        } catch (error) {
            console.error("An error occurred during form initialization:", error);
            showToast("Error grave al cargar el formulario. Revise la consola para más detalles.", "danger", 5000);
        }
    };

    initializeForm();
});


async function loadTrabajoForEditing(id) {
    const trabajo = await fetchAPI(`api/router.php?action=get_trabajo_details&id=${id}`);
    if (!trabajo) { 
        showToast('No se pudo cargar la información del trabajo.', 'danger');
        window.location.href = 'index.php?page=main&tab=trabajos';
        return; 
    }

    state.editingTrabajoId = id;
    state.trabajoForm.items.clear();
    trabajo.items.forEach(item => state.trabajoForm.items.set(item.item_id, {cantidad: item.cantidad_usada, stock_original: item.stock}));

    const form = document.getElementById('trabajoForm');
    form.reset();
    
    form.querySelector('#trabajo-id').value = trabajo.id;
    form.querySelector('[name="net_profit"]').value = trabajo.net_profit;
    form.querySelector('[name="gastos"]').value = trabajo.gastos;
    form.querySelector('[name="cliente_patente"]').value = trabajo.cliente_patente || '';
    form.querySelector('[name="cliente_corte"]').value = trabajo.cliente_corte || '';
    form.querySelector('[name="cliente_pincode"]').value = trabajo.cliente_pincode || '';
    form.querySelector('[name="detalle"]').value = trabajo.detalle || '';
    form.querySelector('[name="notas"]').value = trabajo.notas || '';
    form.querySelector('#trabajo-is-not-paid').checked = parseInt(trabajo.is_paid, 10) === 0;

    await populateFormSelectors(trabajo.tipo_trabajo_id, trabajo.auto_id, trabajo.equipos.map(e => e.id), trabajo.cliente_id, trabajo.corte_id);
    
    const preview = document.getElementById('trabajo-imagen-preview');
    if (trabajo.imagen) { 
        preview.src = `data:${trabajo.imagen_mime};base64,${trabajo.imagen}`; 
        preview.classList.remove('d-none'); 
    } else { 
        preview.classList.add('d-none'); 
        preview.src = ''; 
    }

    renderFormItemsList();
}

// --- Event Listeners ---
function initializeEventListeners() {
    document.getElementById('trabajo-item-search').addEventListener('change', (e) => addTrabajoItem(parseInt(e.target.value)));
    
    document.getElementById('trabajo-auto-search-input').addEventListener('keyup', (e) => renderFormAutos(e.target.value));
    document.getElementById('trabajo-item-search-input').addEventListener('keyup', (e) => renderFormItems(e.target.value));
    document.getElementById('trabajo-corte-search-input').addEventListener('keyup', (e) => renderFormCortes(e.target.value));

    document.getElementById('trabajo-auto-select').addEventListener('change', async (e) => {
        const autoId = e.target.value;
        if (!autoId) return;

        const autoDetails = await fetchAPI(`api/router.php?action=get_auto_details&id=${autoId}`);
        if (autoDetails && (autoDetails.items?.length > 0 || autoDetails.equipos?.length > 0)) {
            const confirmedLoad = await ModalManager.ask({
                title: 'Cargar Datos del Vehículo',
                message: `Este vehículo tiene ${autoDetails.items.length} ítem(s) y ${autoDetails.equipos.length} equipo(s) asociados. ¿Deseas cargarlos automáticamente en este trabajo?`,
                confirmText: 'Sí, cargar',
                confirmButtonClass: 'btn-success',
            });

            if (confirmedLoad) {
                state.trabajoForm.items.clear();
                if (autoDetails.items) {
                    autoDetails.items.forEach(item => {
                        const globalItem = state.items.find(i => i.id == item.id);
                        if(globalItem) {
                            state.trabajoForm.items.set(parseInt(item.id), { cantidad: 1, stock_original: globalItem.stock });
                        }
                    });
                }
                renderFormItemsList();

                const equipoSelect = document.getElementById('trabajo-equipo-select');
                Array.from(equipoSelect.options).forEach(opt => opt.selected = false);
                if (autoDetails.equipos) {
                    const equipoIds = autoDetails.equipos.map(e => e.id.toString());
                    Array.from(equipoSelect.options).forEach(opt => {
                        if (equipoIds.includes(opt.value)) {
                            opt.selected = true;
                        }
                    });
                }
                showToast('Ítems y equipos cargados desde el vehículo.', 'success');
            }
        }
    });

    // When a quick-add modal is closed, refresh the relevant selector
    ['addAutoModal', 'addItemModal', 'equipoModal', 'addClienteModal', 'addTipoTrabajoModal'].forEach(modalId => {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', async () => {
                await refreshData(true); // Refresh state data
                // Repopulate all selectors with potentially new data
                const currentTipo = document.getElementById('trabajo-tipo-select').value;
                const currentAuto = document.getElementById('trabajo-auto-select').value;
                const currentEquipos = Array.from(document.getElementById('trabajo-equipo-select').selectedOptions).map(o => o.value);
                const currentCliente = document.getElementById('trabajo-cliente-select').value;
                const currentCorte = document.getElementById('trabajo-corte-select').value;
                await populateFormSelectors(currentTipo, currentAuto, currentEquipos, currentCliente, currentCorte);
            });
        }
    });
}


// --- Save & API ---
async function saveTrabajo() {
    const saveBtn = document.getElementById('saveTrabajoBtn');
    toggleButtonLoading(saveBtn, true);
    const form = document.getElementById('trabajoForm');
    const formData = new FormData(form);

    const isNotPaidCheckbox = form.querySelector('#trabajo-is-not-paid');
    formData.append('is_paid', isNotPaidCheckbox.checked ? '0' : '1');
    formData.delete('is_not_paid');

    Array.from(state.trabajoForm.items.entries()).forEach(([id, data], index) => {
        formData.append(`items[${index}][id]`, id);
        formData.append(`items[${index}][cantidad]`, data.cantidad);
    });
    
    Array.from(document.getElementById('trabajo-equipo-select').selectedOptions).forEach((option, index) => {
        formData.append(`equipos[${index}]`, option.value);
    });

    try {
        const action = state.editingTrabajoId ? 'edit_trabajo' : 'add_trabajo';
        const result = await fetchAPI(`api/router.php?action=${action}`, { method: 'POST', body: formData });
        if (result?.success) {
            showToast(`Trabajo ${state.editingTrabajoId ? 'actualizado' : 'guardado'} con éxito.`, 'success', 2000);
            setTimeout(() => {
                 window.location.href = 'index.php?page=main&tab=trabajos';
            }, 1000);
        } else {
            showToast(`Error al guardar: ${result?.error || 'Error desconocido'}`, 'danger');
        }
    } finally {
        toggleButtonLoading(saveBtn, false);
    }
}

// --- Form Helpers & Renders ---
async function populateFormSelectors(tipoId, autoId, equipoIds = [], clienteId = null, corteId = null) {
    if (!state.allCortes) {
        document.getElementById('trabajo-corte-select').innerHTML = '<option value="">Cargando...</option>';
        state.allCortes = await fetchAPI('api/router.php?action=get_all_cortes');
        if (!state.allCortes) {
            state.allCortes = [];
            document.getElementById('trabajo-corte-select').innerHTML = '<option value="">Error al cargar</option>';
        }
    }

    const tipoSelect = document.getElementById('trabajo-tipo-select');
    tipoSelect.innerHTML = '<option value="">-- Seleccionar --</option>' + (state.tiposTrabajo || []).map(t => `<option value="${t.id}">${t.nombre}</option>`).join('');
    if (tipoId) tipoSelect.value = tipoId;

    renderFormAutos();
    const autoSelect = document.getElementById('trabajo-auto-select');
    if(autoId) autoSelect.value = autoId;

    renderFormItems();

    const equipoSelect = document.getElementById('trabajo-equipo-select');
    equipoSelect.innerHTML = (state.equipos || []).map(e => `<option value="${e.id}">${e.nombre}</option>`).join('');
    if (equipoIds.length > 0) {
        Array.from(equipoSelect.options).forEach(opt => {
            if (equipoIds.includes(parseInt(opt.value))) opt.selected = true;
        });
    }

    const clienteSelect = document.getElementById('trabajo-cliente-select');
    clienteSelect.innerHTML = '<option value="">-- Cliente de Paso --</option>' + (state.clientes || []).map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
    if (clienteId) clienteSelect.value = clienteId;
    
    renderFormCortes();
    const corteSelect = document.getElementById('trabajo-corte-select');
    if (corteId) corteSelect.value = corteId;
}

function renderFormAutos(autoSearch = '') {
    const autoSelect = document.getElementById('trabajo-auto-select');
    const currentVal = autoSelect.value;
    let autoOptions = '<option value="">-- Ninguno --</option>';
    const lowerAutoSearch = autoSearch.toLowerCase();
    
    if (Array.isArray(state.autos)) {
        state.autos.forEach(brand => {
            let groupOptions = '';
            brand.modelos.forEach(model => {
                model.versions.forEach(v => {
                    const year = formatYearRange(v.anio_inicio, v.anio_fin);
                    const autoName = `${brand.marca} ${model.modelo} ${year} ${v.spec1 || ''} ${v.spec2 || ''}`;
                    if (!lowerAutoSearch || autoName.toLowerCase().includes(lowerAutoSearch)) {
                        groupOptions += `<option value="${v.id}">${model.modelo} (${year})</option>`;
                    }
                });
            });
            if (groupOptions) autoOptions += `<optgroup label="${brand.marca}">${groupOptions}</optgroup>`;
        });
    }

    autoSelect.innerHTML = autoOptions;
    if (Array.isArray(state.autos) && state.autos.flatMap(b => b.modelos.flatMap(m => m.versions)).some(v => v.id == currentVal)) {
        autoSelect.value = currentVal;
    }
}

function renderFormCortes(corteSearch = '') {
    const corteSelect = document.getElementById('trabajo-corte-select');
    if (!Array.isArray(state.allCortes)) {
        corteSelect.innerHTML = '<option value="">Cargando cortes...</option>';
        return;
    }
    const currentVal = corteSelect.value;
    const lowerCorteSearch = corteSearch.toLowerCase();
    const filteredCortes = state.allCortes.filter(c => !lowerCorteSearch || c.nombre.toLowerCase().includes(lowerCorteSearch));
    corteSelect.innerHTML = '<option value="">-- Seleccionar Tipo --</option>' + filteredCortes.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
    if (state.allCortes.some(c => c.id == currentVal)) corteSelect.value = currentVal;
}

function renderFormItems(itemSearch = '') {
    const itemSelect = document.getElementById('trabajo-item-search');
    const lowerItemSearch = itemSearch.toLowerCase();
    
    let optionsHTML = '<option value="">Añadir ítem...</option>';
    if (Array.isArray(state.items)) {
        const filteredItems = state.items.filter(i => !lowerItemSearch || `${i.nombre} ${i.cortes || ''}`.toLowerCase().includes(lowerItemSearch));
        optionsHTML += filteredItems.map(i => {
            const corteText = i.cortes ? `[${i.cortes}] ` : '';
            return `<option value="${i.id}">${i.nombre} ${corteText}(Stock: ${i.stock})</option>`;
        }).join('');
    }
    itemSelect.innerHTML = optionsHTML;
}

function renderFormItemsList() {
    const list = document.getElementById('trabajo-items-list');
    list.innerHTML = '';
    if (state.trabajoForm.items.size === 0) {
        list.innerHTML = '<div class="list-group-item text-muted text-center">No se han añadido ítems.</div>';
        return;
    }
    state.trabajoForm.items.forEach((data, id) => {
        if (!Array.isArray(state.items)) return;
        
        const item = state.items.find(i => i.id == id);
        if (item) {
            const el = document.createElement('div');
            el.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap';
            let maxStock = parseInt(item.stock);
            if (state.editingTrabajoId) {
                const originalItemData = state.trabajoForm.items.get(id);
                if (originalItemData) maxStock += originalItemData.cantidad;
            }
            el.innerHTML = `
                <span class="me-2">${item.nombre}</span>
                <div class="d-flex align-items-center mt-2 mt-sm-0">
                    <input type="number" value="${data.cantidad}" min="1" max="${maxStock}" class="form-control form-control-sm me-2" style="width: 70px;" onchange="updateTrabajoItemCantidad(${id}, this.value)">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTrabajoItem(${id})"><i class="bi bi-trash"></i></button>
                </div>`;
            list.appendChild(el);
        }
    });
}

function addTrabajoItem(itemId) {
    if (!itemId || state.trabajoForm.items.has(itemId)) {
        document.getElementById('trabajo-item-search').value = "";
        return;
    }
    if (!Array.isArray(state.items)) return;
    const item = state.items.find(i => i.id == itemId);
    if(item) {
         state.trabajoForm.items.set(parseInt(itemId), {cantidad: 1, stock_original: item.stock});
         renderFormItemsList();
    }
    document.getElementById('trabajo-item-search').value = "";
}

function removeTrabajoItem(itemId) {
    state.trabajoForm.items.delete(itemId);
    renderFormItemsList();
}

function updateTrabajoItemCantidad(itemId, cantidad) {
    const cant = parseInt(cantidad, 10);
    const itemData = state.trabajoForm.items.get(itemId);
    if (itemData) {
        if (cant > 0) {
            itemData.cantidad = cant;
        } else {
            state.trabajoForm.items.delete(itemId);
        }
    }
    renderFormItemsList();
}


// --- Tipo de Trabajo Quick-Add ---
function openAddTipoTrabajoModal() {
    const input = document.getElementById('new-tipo-trabajo-name-input-modal');
    if (input) input.value = '';
    ModalManager.show('addTipoTrabajoModal');
}

async function createNewTipoTrabajoInForm() {
    const btn = document.getElementById('create-tipo-trabajo-btn-modal');
    toggleButtonLoading(btn, true);
    const input = document.getElementById('new-tipo-trabajo-name-input-modal');
    const name = input.value.trim();
    if (!name) { toggleButtonLoading(btn, false); return; }

    const result = await fetchAPI('api/router.php?action=add_tipo_trabajo', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nombre: name }) });
    if (result?.success) { 
        input.value = ''; 
        ModalManager.hide('addTipoTrabajoModal');
        await refreshData(true);
        
        const tipoSelect = document.getElementById('trabajo-tipo-select');
        tipoSelect.innerHTML = '<option value="">-- Seleccionar --</option>' + state.tiposTrabajo.map(t => `<option value="${t.id}">${t.nombre}</option>`).join('');
        tipoSelect.value = result.id;
        showToast('Tipo de trabajo creado.'); 
    } else { 
        showToast(`Error: ${result?.message || 'Error desconocido'}`, 'danger'); 
    }
    toggleButtonLoading(btn, false);
}

