// assets/js/cliente.js

// --- State Slice for Clientes ---
state.editingClienteId = null;

/**
 * Renders the clients table based on the current state and search term.
 */
function renderClientes() {
    const clienteSearchEl = document.getElementById('cliente-search');
    if (!clienteSearchEl) return; // Don't run if the clients tab is not active

    const searchTerm = clienteSearchEl.value.toLowerCase() || '';
    const tbody = document.getElementById('clientes-table-body');
    if (!tbody) return;

    const filteredClientes = state.clientes.filter(cliente => {
        const searchIn = `${cliente.nombre} ${cliente.telefono || ''} ${cliente.cvu || ''}`.toLowerCase();
        return searchIn.includes(searchTerm);
    });

    tbody.innerHTML = '';
    if (filteredClientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted p-4">No se encontraron clientes.</td></tr>';
        return;
    }

    filteredClientes.forEach(cliente => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${cliente.nombre}</strong></td>
            <td>${cliente.telefono || '<em class="text-muted">N/A</em>'}</td>
            <td><code>${cliente.cvu || '<em class="text-muted">N/A</em>'}</code></td>
            <td><span class="badge rounded-pill bg-info">${cliente.trabajo_count || 0}</span></td>
            <td class="text-end action-buttons">
                <button class="btn btn-sm btn-outline-secondary" onclick="openEditClienteModal(event, ${cliente.id})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteCliente(event, ${cliente.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Opens the modal to add a new client.
 * @param {boolean} [isQuickAdd=false] - True if opened from the trabajo modal.
 */
function openAddClienteModal(isQuickAdd = false) {
    state.editingClienteId = null;
    const form = document.getElementById('addClienteForm');
    form.reset();
    document.getElementById('clienteModalTitle').innerHTML = '<i class="bi bi-person-plus-fill"></i> Añadir Nuevo Cliente';
    const saveBtn = document.getElementById('saveClienteBtn');
    saveBtn.dataset.isQuickAdd = isQuickAdd; // Store quick-add state
    ModalManager.show('addClienteModal');
}

/**
 * Opens the modal to edit an existing client.
 * @param {Event} event - The click event.
 * @param {number} id - The ID of the client to edit.
 */
async function openEditClienteModal(event, id) {
    event.stopPropagation();
    const cliente = state.clientes.find(c => c.id === id);
    if (!cliente) {
        showToast('No se pudo encontrar la información del cliente.', 'danger');
        return;
    }

    state.editingClienteId = id;
    const form = document.getElementById('addClienteForm');
    form.reset();
    document.getElementById('clienteModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> Editar Cliente';

    form.querySelector('#cliente-id').value = cliente.id;
    form.querySelector('[name="nombre"]').value = cliente.nombre;
    form.querySelector('[name="telefono"]').value = cliente.telefono || '';
    form.querySelector('[name="cvu"]').value = cliente.cvu || '';
    form.querySelector('[name="notas"]').value = cliente.notas || '';
    
    document.getElementById('saveClienteBtn').dataset.isQuickAdd = false;
    ModalManager.show('addClienteModal');
}

/**
 * Saves a new or existing client to the database.
 */
async function saveCliente() {
    const saveBtn = document.getElementById('saveClienteBtn');
    toggleButtonLoading(saveBtn, true);
    const form = document.getElementById('addClienteForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    if (!data.nombre || data.nombre.trim() === '') {
        showToast('El nombre del cliente es obligatorio.', 'warning');
        toggleButtonLoading(saveBtn, false);
        return;
    }

    try {
        const action = state.editingClienteId ? 'edit_cliente' : 'add_cliente';
        const result = await fetchAPI(`api/router.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (result?.success) {
            ModalManager.hide('addClienteModal');
            showToast(`Cliente ${state.editingClienteId ? 'actualizado' : 'guardado'} con éxito.`);
            
            const isQuickAdd = saveBtn.dataset.isQuickAdd === 'true';

            await handleDataChange('clientes'); 
            
            // If it's a quick add, re-select the newly added client in the trabajo modal
            if (isQuickAdd && action === 'add_cliente' && result.cliente) {
                const clienteSelect = document.getElementById('trabajo-cliente-select');
                if (clienteSelect) {
                    // We need to wait for the state to update from handleDataChange, so we use a small timeout
                    setTimeout(() => {
                        clienteSelect.value = result.cliente.id;
                    }, 100);
                }
            }

        } else {
            showToast(`Error al guardar: ${result?.message || 'Error desconocido'}`, 'danger');
        }
    } finally {
        toggleButtonLoading(saveBtn, false);
    }
}

/**
 * Deletes a client after confirmation.
 * @param {Event} event - The click event.
 * @param {number} id - The ID of the client to delete.
 */
async function deleteCliente(event, id) {
    event.stopPropagation();
    if (await ModalManager.ask({
        title: 'Eliminar Cliente',
        message: '¿Seguro que quieres eliminar este cliente? Los trabajos asociados no se eliminarán, pero perderán la asignación a este cliente. Esta acción es irreversible.',
        confirmText: 'Sí, Eliminar',
        confirmButtonClass: 'btn-danger'
    })) {
        const result = await fetchAPI('api/router.php?action=delete_cliente', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        if (result?.success) {
            await handleDataChange('clientes');
            showToast('Cliente eliminado.');
        } else {
            showToast('Error al eliminar el cliente.', 'danger');
        }
    }
}

// --- Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
    const clienteSearch = document.getElementById('cliente-search');
    if (clienteSearch) {
        clienteSearch.addEventListener('keyup', renderClientes);
    }

    // When the clientes tab is shown, render the table.
    const clientesTab = document.getElementById('clientes-tab');
    if (clientesTab) {
        clientesTab.addEventListener('shown.bs.tab', renderClientes);
    }
});
