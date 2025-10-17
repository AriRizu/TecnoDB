// assets/js/trabajo.js

// --- Trabajos List Page Logic ---
state.editingTipoTrabajoId = null;


// --- Rendering Functions ---

/**
 * Renders the main financial statistics cards.
 * @param {object} stats - The statistics object from the API.
 */
function renderFinancialStats(stats) {
    // MODIFICATION: Add a guard clause to ensure this only runs if the stat elements exist on the page.
    const profitMesEl = document.getElementById('stat-profit-mes');
    if (!profitMesEl) {
        return; // Exit the function if we're not on the 'trabajos' page.
    }

    const formatCurrency = (value) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0);
    
    profitMesEl.textContent = formatCurrency(stats.profit_mes_actual);
    document.getElementById('stat-gastos-mes').textContent = formatCurrency(stats.gastos_mes_actual);
    document.getElementById('stat-profit-total').textContent = formatCurrency(stats.profit_total);
    document.getElementById('stat-gastos-total').textContent = formatCurrency(stats.gastos_total);
}

/**
 * Renders the table of jobs.
 * @param {Array} trabajosToRender - The array of job objects.
 */
function renderTrabajos(trabajosToRender) {
    // MODIFICATION: Add a guard clause.
    const tbody = document.getElementById('trabajos-table-body');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (!trabajosToRender || trabajosToRender.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted p-4">No se encontraron trabajos.</td></tr>';
        return;
    }
    trabajosToRender.forEach(trabajo => {
        const tr = document.createElement('tr');
        const isPaid = parseInt(trabajo.is_paid, 10) === 1;
        if (!isPaid) {
            tr.classList.add('border', 'border-warning', 'border-2');
        }
        const corteDisplay = trabajo.tipo_corte_nombre 
            ? `${trabajo.tipo_corte_nombre} (${trabajo.cliente_corte || 'N/A'})` 
            : (trabajo.cliente_corte || '-');

        tr.innerHTML = `
            <td>
                ${!isPaid ? '<span class="badge bg-warning text-dark me-1" title="No Pagado"><i class="bi bi-exclamation-triangle-fill"></i></span>' : ''}
                <span class="badge bg-primary">${trabajo.tipo_trabajo_nombre || 'N/A'}</span>
            </td>
            <td>${trabajo.cliente_nombre || '<em class="text-muted">De Paso</em>'}</td>
            <td>${trabajo.auto_nombre || '<em class="text-muted">N/A</em>'}</td>
            <td><code>${trabajo.cliente_patente || '-'}</code></td>
            <td><code>${corteDisplay}</code></td>
            <td class="text-success fw-bold">${new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(trabajo.net_profit)}</td>
            <td class="text-danger">${new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(trabajo.gastos)}</td>
            <td>${new Date(trabajo.fecha_creacion).toLocaleDateString()}</td>
            <td class="text-end action-buttons">
                <button class="btn btn-sm btn-outline-secondary" onclick="openEditTrabajoPage(event, ${trabajo.id})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteTrabajo(event, ${trabajo.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Renders the table for managing job types.
 * @param {Array} tipos - The array of job type objects.
 */
function renderTiposTrabajoManagement(tipos) {
    // MODIFICATION: Add a guard clause.
    const tbody = document.getElementById('tipos-trabajo-management-table');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (!tipos || tipos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No hay tipos de trabajo creados.</td></tr>';
        return;
    }
    tipos.forEach(tipo => {
        const tr = document.createElement('tr');
        const isEditing = state.editingTipoTrabajoId == tipo.id;
        tr.innerHTML = `
            <td id="tipo-trabajo-name-${tipo.id}">${isEditing ? `<input type="text" class="form-control form-control-sm" value="${tipo.nombre}">` : tipo.nombre}</td>
            <td><span class="badge rounded-pill bg-secondary">${tipo.usage_count}</span></td>
            <td class="text-end action-buttons">${isEditing ? 
                `<button class="btn btn-sm btn-success" onclick="saveTipoTrabajo(event, ${tipo.id})"><i class="bi bi-check-lg"></i></button>
                 <button class="btn btn-sm btn-secondary" onclick="cancelEditTipoTrabajo(event)"><i class="bi bi-x-lg"></i></button>` : 
                `<button class="btn btn-sm btn-outline-secondary" onclick="editTipoTrabajo(event, ${tipo.id})"><i class="bi bi-pencil-fill"></i></button>
                 <button class="btn btn-sm btn-outline-danger" onclick="deleteTipoTrabajo(event, ${tipo.id})"><i class="bi bi-trash"></i></button>`}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// --- Search & Update ---
function updateAndRenderTrabajos() {
    const searchInput = document.getElementById('trabajo-search');
    if (!searchInput) return;
    const searchTerm = searchInput.value.toLowerCase();
    const filtered = state.trabajos.filter(t => {
        const searchIn = `${t.tipo_trabajo_nombre || ''} ${t.cliente_nombre || ''} ${t.auto_nombre || ''} ${t.cliente_patente || ''} ${t.tipo_corte_nombre || ''} ${t.cliente_corte || ''}`.toLowerCase();
        return searchIn.includes(searchTerm);
    });
    renderTrabajos(filtered);
}


// --- Navigation and Deletion ---
function openEditTrabajoPage(event, id) {
    event.stopPropagation();
    window.location.href = `index.php?page=trabajo-form&id=${id}`;
}


async function deleteTrabajo(event, id) {
    event.stopPropagation();
    if (await ModalManager.ask({ title: 'Eliminar Trabajo', message: '¿Seguro que quieres eliminar este trabajo? Se restaurará el stock de los ítems usados. Esta acción es irreversible.', confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) {
        const result = await fetchAPI('api/router.php?action=delete_trabajo', { method: 'POST', body: JSON.stringify({ id }) });
        if(result?.success) {
            await refreshData();
            showToast('Trabajo eliminado.');
        } else {
            showToast('Error al eliminar el trabajo.', 'danger');
        }
    }
}

// --- Job Type Management ---
async function createNewTipoTrabajo() {
    const btn = document.getElementById('create-tipo-trabajo-btn');
    toggleButtonLoading(btn, true);
    const input = document.getElementById('new-tipo-trabajo-name-input');
    const name = input.value.trim();
    if (!name) { toggleButtonLoading(btn, false); return; }
    const result = await fetchAPI('api/router.php?action=add_tipo_trabajo', { method: 'POST', body: JSON.stringify({ nombre: name }) });
    if (result?.success) { input.value = ''; await refreshData(); showToast('Tipo de trabajo creado.'); } 
    else { showToast(`Error: ${result?.message || 'Error desconocido'}`, 'danger'); }
    toggleButtonLoading(btn, false);
}
function editTipoTrabajo(event, id) { event.stopPropagation(); state.editingTipoTrabajoId = id; renderTiposTrabajoManagement(state.tiposTrabajo); }
function cancelEditTipoTrabajo(event) { event.stopPropagation(); state.editingTipoTrabajoId = null; renderTiposTrabajoManagement(state.tiposTrabajo); }
async function saveTipoTrabajo(event, id) { event.stopPropagation(); const input = document.querySelector(`#tipo-trabajo-name-${id} input`); const result = await fetchAPI('api/router.php?action=edit_tipo_trabajo', { method: 'POST', body: JSON.stringify({ id, nombre: input.value }) }); if(result?.success) { state.editingTipoTrabajoId = null; await refreshData(); showToast('Tipo de trabajo guardado.'); } else if(result?.error === 'duplicate') { showToast(result.message, 'warning'); } else { showToast('Error al guardar.', 'danger'); } }
async function deleteTipoTrabajo(event, id) { event.stopPropagation(); if (await ModalManager.ask({ title: 'Eliminar Tipo de Trabajo', message: '¿Seguro que quieres eliminar este tipo? No se puede deshacer.', confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) { const result = await fetchAPI('api/router.php?action=delete_tipo_trabajo', { method: 'POST', body: JSON.stringify({ id }) }); if(result?.success) { await refreshData(); showToast('Tipo de trabajo eliminado.'); } else showToast('Error al eliminar.', 'danger'); } }


// --- Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('trabajo-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', updateAndRenderTrabajos);
    }
});
