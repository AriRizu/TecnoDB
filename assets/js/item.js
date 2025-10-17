// assets/js/item.js

// --- State Slice for Items, Tags, Cortes ---
state.selectedItems = new Set();
state.selectedTags = new Set();
state.selectedCortes = new Set();
state.editingItemId = null;
state.editingTagId = null;
state.editingCorteId = null;
state.itemsPerRowConfig = { '1': 2, '2': 3, '3': 4, '4': 5, '5': 6, '6': 8 };
state.itemsPerRow = 6;
state.sortOrderItems = 'nombre-asc';


// --- Item Rendering ---

/**
 * Creates the HTML string for a single item card.
 * @param {object} item - The item object.
 * @returns {string} The HTML string for the card.
 */
function createItemCardHTML(item) {
    const itemsCount = state.itemsPerRow;
    const isOutOfStock = parseInt(item.stock, 10) === 0;
    const isLowStock = !isOutOfStock && parseInt(item.stock_threshold, 10) > 0 && parseInt(item.stock, 10) <= parseInt(item.stock_threshold, 10);

    const tagsHtml = item.tags ? `<div class="mt-2">${item.tags.split(', ').map(t => `<span class="tag">${t}</span>`).join('')}</div>` : '';
    const corteHtml = item.cortes ? `<span class="badge bg-info-subtle text-info-emphasis ms-2">${item.cortes}</span>` : '';

    const imageHtml = item.has_image
        ? `<img src="api/router.php?action=get_item_image&id=${item.id}&t=${new Date().getTime()}" class="card-img-top" alt="${item.nombre}" loading="lazy">`
        : `<div class="item-image-placeholder"><i class="bi bi-image-alt"></i></div>`;
    
    const outOfStockWarning = isOutOfStock ? '<div class="text-danger fw-bold mt-1 out-of-stock-warning"><i class="bi bi-exclamation-triangle-fill"></i> SIN STOCK</div>' : '';
    const lowStockWarning = isLowStock ? '<div class="text-warning fw-bold mt-1 low-stock-warning"><i class="bi bi-exclamation-circle-fill"></i> STOCK BAJO</div>' : '';
    
    let cardBodyHtml, cardFooterHtml;

    if (!isMobileView() && itemsCount > 6) {
        cardBodyHtml = `
            <div class="card-body clickable p-2 text-center" onclick="selectItem(${item.id})">
                <h6 class="card-title m-0 text-truncate" style="font-size: 0.8rem;" title="${item.nombre}">${item.nombre}</h6>
                ${item.cortes ? `<span class="badge bg-info-subtle text-info-emphasis mt-1" style="font-size: 0.6rem;">${item.cortes}</span>` : ''}
                ${outOfStockWarning}
                ${lowStockWarning}
            </div>`;
    } else {
        cardBodyHtml = `
            <div class="card-body clickable" onclick="selectItem(${item.id})">
                <h5 class="card-title d-flex align-items-center">${item.nombre}${corteHtml}</h5>
                ${item.ubicacion ? `<p class="card-subtitle mb-2 text-muted small"><i class="bi bi-geo-alt-fill"></i> ${item.ubicacion}</p>` : ''}
                <p class="card-text text-muted small">${(item.descripcion || 'Sin descripción').substring(0, 70)}...</p>
                ${tagsHtml}
                ${outOfStockWarning}
                ${lowStockWarning}
            </div>`;
    }

    // --- Refactored Footer Logic ---
    // The logic is now split: first by the number of items per row for desktop views,
    // and then uses Bootstrap's responsive classes to show the correct footer based on screen size.
    // This removes the dependency on the isMobileView() JavaScript function for the footer.
    
    // The "mobile" footer is the default view for screens smaller than 'lg' (992px).
    // It's a single, space-efficient row that works well for the fixed 2-column mobile layout.
    const mobileFooterHtml = `
        <div class="card-footer p-1">
    <div class="input-group input-group-sm mb-1">
       <button class="btn btn-sm btn-outline-secondary py-0" type="button" onclick="updateStock(event, ${item.id}, -1)" ${isOutOfStock ? 'disabled' : ''}>-</button>
       <input type="text" class="form-control text-center py-0 ${isOutOfStock ? 'text-danger fw-bold' : isLowStock ? 'text-warning' : ''}" style="font-size: 0.75rem;" value="${item.stock}" readonly id="stock-count-${item.id}-desktop" aria-label="Stock">
       <button class="btn btn-sm btn-outline-secondary py-0" type="button" onclick="updateStock(event, ${item.id}, 1)">+</button>
   </div>
   <div class="d-flex justify-content-between align-items-center">
       <div class="form-check"><input class="form-check-input" type="checkbox" value="${item.id}" id="item-check-${item.id}-desktop" onchange="handleItemSelectionChange(${item.id}, this.checked)" ${state.selectedItems.has(item.id) ? 'checked' : ''}></div>
       <div class="btn-group">
           <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="copyItem(event, ${item.id})" title="Copiar"><i class="bi bi-copy"></i></button>
           <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="openEditItemModal(event, ${item.id})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
           <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteItem(event, ${item.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
       </div>
   </div>
</div>

`;
        
    let desktopFooterHtml = '';

    if (itemsCount > 6) {
        // Compact desktop view for 8+ items per row. Vertically stacked for density.
        desktopFooterHtml = `
            <div class="card-footer p-1">
                 <div class="input-group input-group-sm mb-1">
                    <button class="btn btn-sm btn-outline-secondary py-0" type="button" onclick="updateStock(event, ${item.id}, -1)" ${isOutOfStock ? 'disabled' : ''}>-</button>
                    <input type="text" class="form-control text-center py-0 ${isOutOfStock ? 'text-danger fw-bold' : isLowStock ? 'text-warning' : ''}" style="font-size: 0.75rem;" value="${item.stock}" readonly id="stock-count-${item.id}-desktop" aria-label="Stock">
                    <button class="btn btn-sm btn-outline-secondary py-0" type="button" onclick="updateStock(event, ${item.id}, 1)">+</button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="form-check"><input class="form-check-input" type="checkbox" value="${item.id}" id="item-check-${item.id}-desktop" onchange="handleItemSelectionChange(${item.id}, this.checked)" ${state.selectedItems.has(item.id) ? 'checked' : ''}></div>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="copyItem(event, ${item.id})" title="Copiar"><i class="bi bi-copy"></i></button>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="openEditItemModal(event, ${item.id})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteItem(event, ${item.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>`;
    } else if (itemsCount > 3) {
        // Medium density desktop view for 4-6 items per row.
        let actionBtnClasses = "btn btn-sm";
        if (itemsCount === 6) {
            actionBtnClasses += " py-0 px-1";
        }
        desktopFooterHtml = `
            <div class="card-footer p-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${item.id}" id="item-check-${item.id}-desktop" onchange="handleItemSelectionChange(${item.id}, this.checked)" ${state.selectedItems.has(item.id) ? 'checked' : ''}>
                        <label class="form-check-label small" for="item-check-${item.id}-desktop"> Sel.</label>
                    </div>
                    <div class="btn-group">
                        <button class="${actionBtnClasses} btn-outline-info" onclick="copyItem(event, ${item.id})" title="Copiar"><i class="bi bi-copy"></i></button>
                        <button class="${actionBtnClasses} btn-outline-secondary" onclick="openEditItemModal(event, ${item.id})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                        <button class="${actionBtnClasses} btn-outline-danger" onclick="deleteItem(event, ${item.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <div class="input-group input-group-sm">
                    <button class="btn btn-outline-secondary" type="button" onclick="updateStock(event, ${item.id}, -1)" ${isOutOfStock ? 'disabled' : ''}>-</button>
                    <input type="text" class="form-control text-center ${isOutOfStock ? 'text-danger fw-bold' : isLowStock ? 'text-warning' : ''}" value="${item.stock}" readonly id="stock-count-${item.id}-desktop" aria-label="Stock">
                    <button class="btn btn-outline-secondary" type="button" onclick="updateStock(event, ${item.id}, 1)">+</button>
                </div>
            </div>`;
    }

    if (desktopFooterHtml) {
        // When a specific desktop layout is needed (for >3 items/row), we render both desktop and mobile versions
        // and toggle their visibility based on the 'lg' breakpoint.
        cardFooterHtml = `
            <div class="d-none d-lg-block">${desktopFooterHtml}</div>
            <div class="d-lg-none">${mobileFooterHtml}</div>
        `;
    } else {
        // When itemCount is low (<=3), the default single-row footer is suitable for all screen sizes.
        cardFooterHtml = `
            <div class="card-footer d-flex flex-column flex-sm-row justify-content-sm-between align-items-center gap-2">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" value="${item.id}" id="item-check-${item.id}" onchange="handleItemSelectionChange(${item.id}, this.checked)" ${state.selectedItems.has(item.id) ? 'checked' : ''}>
        <label class="form-check-label small d-none d-sm-inline" for="item-check-${item.id}"> Sel.</label>
    </div>
    <div class="input-group input-group-sm" style="max-width: 120px;">
        <button class="btn btn-outline-secondary" type="button" onclick="updateStock(event, ${item.id}, -1)" ${isOutOfStock ? 'disabled' : ''}>-</button>
        <input type="text" class="form-control text-center ${isOutOfStock ? 'text-danger fw-bold' : isLowStock ? 'text-warning' : ''}" value="${item.stock}" readonly id="stock-count-${item.id}" aria-label="Stock">
        <button class="btn btn-outline-secondary" type="button" onclick="updateStock(event, ${item.id}, 1)">+</button>
    </div>
    <div class="btn-group">
        <button class="btn btn-sm btn-outline-info" onclick="copyItem(event, ${item.id})" title="Copiar"><i class="bi bi-copy"></i></button>
        <button class="btn btn-sm btn-outline-secondary" onclick="openEditItemModal(event, ${item.id})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(event, ${item.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
    </div>
</div>
`;
    }


    return `<div class="card item-card ${state.selectedItem?.id == item.id ? 'active' : ''} ${isOutOfStock ? 'border-danger' : isLowStock ? 'border-warning' : ''}">
                <div class="clickable" onclick="selectItem(${item.id})">${imageHtml}</div>
                ${cardBodyHtml}
                ${cardFooterHtml}
            </div>`;
}

/**
 * Renders the entire item library grid.
 * @param {Array} itemsToRender - The filtered and sorted array of item objects.
 */
function renderItemsLibrary(itemsToRender) {
    const grid = document.getElementById('items-library-grid');
    grid.innerHTML = '';
    
    const sortedItems = sortItems(itemsToRender);

    if (sortedItems.length === 0) {
        grid.innerHTML = getEmptyStateHTML('bi bi-search', 'No se encontraron ítems', 'Intenta ajustar tus criterios de búsqueda.');
        return;
    }

    let colClass;
    if (!isMobileView()) {
        switch (state.itemsPerRow) {
            case 2: colClass = 'col-lg-6'; break;
            case 3: colClass = 'col-lg-4'; break;
            case 4: colClass = 'col-lg-3'; break;
            case 5: colClass = 'col-custom-5'; break;
            case 6: colClass = 'col-lg-2'; break;
            case 8: colClass = 'col-custom-8'; break;
            default: colClass = 'col-lg-4';
        }
    } else {
        colClass = ''; 
    }

    sortedItems.forEach(item => {
        const col = document.createElement('div');
        col.className = `col-md-6 ${colClass}`;
        col.innerHTML = createItemCardHTML(item);
        grid.appendChild(col);
    });

    updateItemMassActionButtons();
}

/**
 * Renders the details of a selected item.
 * @param {object} item - The item object.
 */
function renderItemDetails(item) {
    state.selectedItem = item;
    const content = document.getElementById('item-details-content');
    let autosHtml = '<div class="list-group-item text-muted text-center">No asignado a vehículos.</div>';
    if (item.autos && item.autos.length > 0) { autosHtml = item.autos.map(auto => `<li class="list-group-item">${auto.marca} ${auto.modelo} (${formatYearRange(auto.anio_inicio, auto.anio_fin)})</li>`).join(''); }
    const secondaryNamesHtml = item.nombres_secundarios ? item.nombres_secundarios.split(',').map(n => `<span class="tag bg-info-subtle text-info-emphasis">${n.trim()}</span>`).join(' ') : '<em class="text-muted">N/A</em>';
    const tagsHtml = item.tags ? item.tags.split(', ').map(t => `<span class="tag">${t}</span>`).join('') : '';
    
    const imgSrc1 = item.imagen ? `data:${item.imagen_mime};base64,${item.imagen}` : '';
    const imageHtml = item.imagen ? `<div class="mb-3 text-center"><img src="${imgSrc1}" class="img-fluid rounded clickable" alt="Imagen principal" style="max-height: 150px;" onclick="openItemImage(this, '${item.nombre.replace(/'/g, "\\'")}', 'Principal')"></div>` : '';
    
    const imgSrc2 = item.imagen_detalle ? `data:${item.imagen_detalle_mime};base64,${item.imagen_detalle}` : '';
    const detailImageHtml = item.imagen_detalle ? `<div class="mb-3 text-center"><img src="${imgSrc2}" class="img-fluid rounded clickable" alt="Imagen de detalle" style="max-height: 150px;" onclick="openItemImage(this, '${item.nombre.replace(/'/g, "\\'")}', 'Detalle')"></div>` : '';

    const corteHtml = item.cortes ? `<h6>Corte</h6><p><span class="tag bg-primary">${item.cortes}</span></p>` : '';
    
    const manageAutosButton = `<button class="btn btn-sm btn-outline-primary" onclick="openAssignVehiclesToItemModal(${item.id}, '${item.nombre.replace(/'/g, "\\'")}', [${item.autos.map(a => a.id).join(',')}])"><i class="bi bi-pencil-square"></i> Gestionar</button>`;
    
    const isOutOfStock = parseInt(item.stock, 10) === 0;
    const isLowStock = !isOutOfStock && parseInt(item.stock_threshold, 10) > 0 && parseInt(item.stock, 10) <= parseInt(item.stock_threshold, 10);
    const outOfStockWarningDetails = isOutOfStock ? `<div class="alert alert-danger d-flex align-items-center" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><div>Este ítem se encuentra sin stock.</div></div>` : '';
    const lowStockWarningDetails = isLowStock ? `<div class="alert alert-warning d-flex align-items-center" role="alert"><i class="bi bi-exclamation-circle-fill me-2"></i><div>El stock de este ítem es bajo.</div></div>` : '';

    content.innerHTML = `<div class="card h-100"><div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center"><h4 class="h5 mb-0 text-truncate" title="${item.nombre}">${item.nombre}</h4>${item.ubicacion ? `<span class="badge bg-secondary"><i class="bi bi-geo-alt-fill"></i> ${item.ubicacion}</span>` : ''}</div><div class="card-body d-flex flex-column"><div style="overflow-y: auto;">${outOfStockWarningDetails}${lowStockWarningDetails}${imageHtml}${detailImageHtml}${corteHtml}<h6>Nombres Secundarios</h6><p>${secondaryNamesHtml}</p><h6>Stock</h6><p><span class="badge ${isOutOfStock ? 'bg-danger' : isLowStock ? 'bg-warning text-dark' : 'bg-primary'} fs-6">${item.stock}</span> (Umbral: ${item.stock_threshold})</p><h6>Descripción</h6><p>${item.descripcion || '<em class="text-muted">Sin descripción.</em>'}</p><h6>Tags</h6><div>${tagsHtml || '<em class="text-muted">Sin tags.</em>'}</div><hr><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Vehículos asociados (${item.autos?.length || 0}):</h6>${manageAutosButton}</div></div><div class="flex-grow-1" style="overflow-y: auto;"><ul class="list-group list-group-flush">${autosHtml}</ul></div></div></div>`;
    renderItemsLibrary(filterItems());
}

/**
 * Renders the table for managing tags.
 * @param {Array} tagsToRender - The array of tag objects.
 */
function renderTagsManagement(tagsToRender) { const tbody = document.getElementById('tags-management-table'); if (!tbody) return; tbody.innerHTML = ''; if (!tagsToRender || tagsToRender.length === 0) { tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay tags.</td></tr>'; return; } tagsToRender.forEach(tag => { const tr = document.createElement('tr'); const isEditing = state.editingTagId == tag.id; tr.innerHTML = `<td><input class="form-check-input" type="checkbox" value="${tag.id}" onchange="handleTagCorteSelectionChange('tags', ${tag.id}, this.checked)" ${state.selectedTags.has(tag.id) ? 'checked' : ''}></td><td id="tag-name-${tag.id}">${isEditing ? `<input type="text" class="form-control form-control-sm" value="${tag.nombre}">` : tag.nombre}</td><td><span class="badge rounded-pill bg-secondary">${tag.usage_count}</span></td><td class="text-end action-buttons">${isEditing ? `<button class="btn btn-sm btn-success" onclick="saveTag(event, ${tag.id})"><i class="bi bi-check-lg"></i></button><button class="btn btn-sm btn-secondary" onclick="cancelEditTag(event)"><i class="bi bi-x-lg"></i></button>` : `<button class="btn btn-sm btn-outline-secondary" onclick="editTag(event, ${tag.id})"><i class="bi bi-pencil-fill"></i></button><button class="btn btn-sm btn-outline-danger" onclick="deleteTag(event, ${tag.id})"><i class="bi bi-trash"></i></button>`}</td>`; tbody.appendChild(tr); }); updateItemMassActionButtons(); }

/**
 * Renders the table for managing cortes.
 * @param {Array} cortesToRender - The array of corte objects.
 */
function renderCortesManagement(cortesToRender) {
    const tbody = document.getElementById('cortes-management-table');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (!cortesToRender || cortesToRender.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay cortes.</td></tr>';
        return;
    }
    cortesToRender.forEach(corte => {
        const tr = document.createElement('tr');
        const imageHtml = corte.has_image
            ? `<img src="api/router.php?action=get_corte_image&id=${corte.id}&t=${new Date().getTime()}" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;" alt="${corte.nombre}">`
            : '<div class="corte-image-placeholder"><i class="bi bi-image-alt"></i></div>';
        
        tr.innerHTML = `
            <td><input class="form-check-input" type="checkbox" value="${corte.id}" onchange="handleTagCorteSelectionChange('cortes', ${corte.id}, this.checked)" ${state.selectedCortes.has(corte.id) ? 'checked' : ''}></td>
            <td>${imageHtml}</td>
            <td>${corte.nombre}</td>
            <td><code>${corte.bitting || '-'}</code></td>
            <td><span class="badge rounded-pill bg-secondary">${corte.usage_count}</span></td>
            <td class="text-end action-buttons">
                <button class="btn btn-sm btn-outline-secondary" onclick="openEditCorteModal(event, ${corte.id})"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteCorte(event, ${corte.id})"><i class="bi bi-trash"></i></button>
            </td>`;
        tbody.appendChild(tr);
    });
    updateItemMassActionButtons();
}


// --- Autocomplete Rendering ---
function renderTagSuggestions(suggestions) { const container = document.getElementById('tags-suggestions'); container.innerHTML = ''; if(suggestions.length === 0) { container.style.display = 'none'; return; } container.style.display = 'block'; suggestions.forEach(tag => { const div = document.createElement('div'); div.className = 'list-group-item list-group-item-action autocomplete-suggestion tag-suggestion'; div.innerHTML = `<span>${tag.nombre}</span><span class="badge bg-info-subtle text-info-emphasis rounded-pill">${tag.usage_count}</span>`; div.onclick = () => selectTagSuggestion(tag.nombre); container.appendChild(div); }); }
function renderCorteSuggestions(suggestions) { const container = document.getElementById('corte-suggestions'); container.innerHTML = ''; if(suggestions.length === 0) { container.style.display = 'none'; return; } container.style.display = 'block'; suggestions.forEach(corteName => { const div = document.createElement('div'); div.className = 'list-group-item list-group-item-action autocomplete-suggestion'; div.textContent = corteName; div.onclick = () => selectCorteSuggestion(corteName); container.appendChild(div); }); }

// --- Filtering, Sorting, and Searching ---
function updateAndRenderItems() { 
    // Add a guard clause to ensure this only runs if the item library elements exist on the page.
    const searchInput = document.getElementById('item-library-search');
    if (!searchInput) {
        return; // Exit the function if we're not on the main 'items' page.
    }
    const filtered = filterItems(); 
    renderItemsLibrary(filtered); 
}

function filterItems() {
    const criteria = {
        quickSearch: document.getElementById('item-library-search').value.toLowerCase(),
        nombre: document.getElementById('adv-item-nombre').value.toLowerCase(),
        corte: document.getElementById('adv-item-corte').value.toLowerCase(),
        tag: document.getElementById('adv-item-tag').value.toLowerCase(),
        ubicacion: document.getElementById('adv-item-ubicacion').value.toLowerCase()
    };
    return state.items.filter(i => {
        const quickSearchText = `${i.nombre} ${i.nombres_secundarios || ''} ${i.cortes || ''} ${i.tags || ''}`.toLowerCase();
        if (criteria.quickSearch && !quickSearchText.includes(criteria.quickSearch)) return false;
        if (criteria.nombre && !(i.nombre.toLowerCase().includes(criteria.nombre) || (i.nombres_secundarios && i.nombres_secundarios.toLowerCase().includes(criteria.nombre)))) return false;
        if (criteria.corte && !(i.cortes && i.cortes.toLowerCase().includes(criteria.corte))) return false;
        if (criteria.tag && !(i.tags && i.tags.toLowerCase().includes(criteria.tag))) return false;
        if (criteria.ubicacion && !(i.ubicacion && i.ubicacion.toLowerCase().includes(criteria.ubicacion))) return false;
        return true;
    });
}
function sortItems(itemsToSort) {
    const sorted = [...itemsToSort];
    switch (state.sortOrderItems) {
        case 'nombre-asc': sorted.sort((a, b) => a.nombre.localeCompare(b.nombre)); break;
        case 'nombre-desc': sorted.sort((a, b) => b.nombre.localeCompare(a.nombre)); break;
        case 'stock-asc': sorted.sort((a, b) => parseInt(a.stock) - parseInt(b.stock)); break;
        case 'stock-desc': sorted.sort((a, b) => parseInt(b.stock) - parseInt(a.stock)); break;
        case 'creacion-asc': sorted.sort((a, b) => parseInt(a.id) - parseInt(b.id)); break;
        case 'creacion-desc': sorted.sort((a, b) => parseInt(b.id) - parseInt(a.id)); break;
    }
    sorted.sort((a, b) => {
        const aIsOutOfStock = parseInt(a.stock, 10) === 0;
        const bIsOutOfStock = parseInt(b.stock, 10) === 0;
        if (aIsOutOfStock && !bIsOutOfStock) return -1;
        if (!aIsOutOfStock && bIsOutOfStock) return 1;
        return 0;
    });
    return sorted;
}
function performItemSearch() { updateAndRenderItems(); }
function resetItemSearch() { document.getElementById('item-library-search').value = ''; document.getElementById('adv-item-nombre').value = ''; document.getElementById('adv-item-corte').value = ''; document.getElementById('adv-item-tag').value = ''; document.getElementById('adv-item-ubicacion').value = ''; performItemSearch(); }

// --- Selection ---
async function selectItem(id) { 
    const item = await fetchAPI(`api/router.php?action=get_item_details&id=${id}`); 
    if(item) {
        renderItemDetails(item);
        if (isMobileView()) {
            showDetailsView('items');
        }
    }
}
function handleItemSelectionChange(id, isChecked) {
    if (isChecked) state.selectedItems.add(id); else state.selectedItems.delete(id);
    updateItemMassActionButtons();
}
function handleTagCorteSelectionChange(type, id, isChecked) {
    const set = (type === 'tags') ? state.selectedTags : state.selectedCortes;
    if (isChecked) set.add(id); else set.delete(id);
    updateItemMassActionButtons();
}
function toggleSelectAllItems(checkbox) {
    const allVisibleIds = filterItems().map(i => i.id);
    if (checkbox.checked) {
        allVisibleIds.forEach(id => state.selectedItems.add(id));
    } else {
        state.selectedItems.clear();
    }
    updateAndRenderItems();
}
function toggleSelectAllTags(checkbox) {
    if (checkbox.checked) state.allTags.forEach(t => state.selectedTags.add(t.id));
    else state.selectedTags.clear();
    renderTagsManagement(state.allTags);
}
function toggleSelectAllCortes(checkbox) {
    if (checkbox.checked) state.allCortes.forEach(c => state.selectedCortes.add(c.id));
    else state.selectedCortes.clear();
    renderCortesManagement(state.allCortes);
}

function updateItemMassActionButtons() {
    const itemCount = state.selectedItems.size;
    const deleteItemsBtn = document.getElementById(`mass-delete-items-btn`);
    if(deleteItemsBtn) { deleteItemsBtn.disabled = itemCount === 0; deleteItemsBtn.innerHTML = `<i class="bi bi-trash-fill"></i> Eliminar (${itemCount})`; }

    const tagCount = state.selectedTags.size;
    const deleteTagsBtn = document.getElementById(`mass-delete-tags-btn`);
    if(deleteTagsBtn) { deleteTagsBtn.disabled = tagCount === 0; deleteTagsBtn.innerHTML = `<i class="bi bi-trash-fill"></i> Eliminar (${tagCount})`; }
    
    const corteCount = state.selectedCortes.size;
    const deleteCortesBtn = document.getElementById(`mass-delete-cortes-btn`);
    if(deleteCortesBtn) { deleteCortesBtn.disabled = corteCount === 0; deleteCortesBtn.innerHTML = `<i class="bi bi-trash-fill"></i> Eliminar (${corteCount})`; }
}

// --- Modals & Forms ---
function openAddItemModal() {
    if (!state.editingItemId) {
        document.getElementById('addItemForm').reset();
        document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Añadir Ítem';
        previewImage(document.getElementById('item-imagen'), 'imagen-preview');
        previewImage(document.getElementById('item-imagen-detalle'), 'imagen-detalle-preview');
    }
    ModalManager.show('addItemModal');
}
async function openEditItemModal(event, id) {
    if (event) event.stopPropagation();
    const item = await fetchAPI(`api/router.php?action=get_item_details&id=${id}`);
    if (!item) { showToast('No se pudo cargar la información del ítem.', 'danger'); return; }
    state.editingItemId = id;
    const form = document.getElementById('addItemForm');
    form.querySelector('#item-id').value = item.id;
    form.querySelector('[name="nombre"]').value = item.nombre;
    form.querySelector('[name="corte"]').value = item.cortes || '';
    form.querySelector('[name="nombres_secundarios"]').value = item.nombres_secundarios || '';
    form.querySelector('[name="ubicacion"]').value = item.ubicacion || '';
    form.querySelector('[name="stock"]').value = item.stock;
    form.querySelector('[name="stock_threshold"]').value = item.stock_threshold;
    form.querySelector('[name="descripcion"]').value = item.descripcion || '';
    form.querySelector('#tags-input').value = item.tags || '';
    
    const preview1 = document.getElementById('imagen-preview');
    if (item.imagen) { preview1.src = `data:${item.imagen_mime};base64,${item.imagen}`; preview1.classList.remove('d-none'); } 
    else { preview1.classList.add('d-none'); preview1.src = ''; }
    const preview2 = document.getElementById('imagen-detalle-preview');
    if (item.imagen_detalle) { preview2.src = `data:${item.imagen_detalle_mime};base64,${item.imagen_detalle}`; preview2.classList.remove('d-none'); }
    else { preview2.classList.add('d-none'); preview2.src = ''; }

    document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> Editar Ítem';
    ModalManager.show('addItemModal');
}

// --- Save & Delete ---
async function saveItem() {
    const saveBtn = document.getElementById('saveItemBtn');
    toggleButtonLoading(saveBtn, true);
    const form = document.getElementById('addItemForm');
    const formData = new FormData(form);
    if (!formData.get('nombre')) {
        showToast("El nombre del ítem es requerido.", "warning");
        toggleButtonLoading(saveBtn, false);
        return;
    }
    
    try {
        const action = state.editingItemId ? 'edit_item' : 'add_item';
        const result = await fetchAPI(`api/router.php?action=${action}`, { method: 'POST', body: formData });

        if (result?.success) { 
            ModalManager.hide('addItemModal'); 
            await handleDataChange('items', () => { if(result.id) selectItem(result.id); }); 
            showToast(`Ítem ${state.editingItemId ? 'actualizado' : 'guardado'} con éxito.`);
        } else {
            showToast(`Error al ${state.editingItemId ? 'editar' : 'guardar'} el ítem: ${result?.error || 'Error desconocido'}`, 'danger');
        }
    } finally {
        toggleButtonLoading(saveBtn, false);
    }
}

async function deleteItem(event, id) { 
    event.stopPropagation(); 
    if (await ModalManager.ask({ title: 'Eliminar Ítem', message: '¿Seguro que quieres eliminar este ítem? Esta acción es irreversible.', confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) {
        const result = await fetchAPI('api/router.php?action=delete_item', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) }); 
        if(result?.success) { 
            if(state.selectedItem?.id == id) { 
                document.getElementById('item-details-content').innerHTML = '<div class="initial-view"><i class="bi bi-box-seam" style="font-size: 5rem;"></i><h2 class="mt-3">Selecciona un ítem</h2><p class="lead text-muted">Haz clic para ver sus relaciones.</p></div>'; 
                state.selectedItem = null; 
            } 
            await handleDataChange('items'); 
            showToast('Ítem eliminado.'); 
        } else showToast('Error al eliminar el ítem.', 'danger'); 
    }
}
async function massDeleteItems() {
    const ids = Array.from(state.selectedItems); if(ids.length === 0) return; if (await ModalManager.ask({ title: `Eliminación Masiva`, message: `¿Estás seguro de que quieres eliminar ${ids.length} ítem(s) seleccionados?`, confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) { const result = await fetchAPI(`api/router.php?action=mass_delete_items`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids }) }); if(result?.success) { state.selectedItems.clear(); document.getElementById(`select-all-items`).checked = false; await handleDataChange('items'); showToast(`Ítem(s) eliminados.`); } else showToast(`Error en la eliminación masiva de ítems.`, 'danger'); } 
}

// --- Stock & Copying ---
function updateOutOfStockNotification() {
    const outOfStockItems = state.items.filter(item => parseInt(item.stock, 10) === 0).length;
    const badge = document.getElementById('out-of-stock-badge');
    if (badge) {
        if (outOfStockItems > 0) {
            badge.textContent = outOfStockItems;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }
}
async function updateStock(event, itemId, change) {
    event.stopPropagation();
    const itemInState = state.items.find(i => i.id == itemId);
    if (!itemInState || (parseInt(itemInState.stock, 10) + change) < 0) return;

    const result = await fetchAPI('api/router.php?action=update_item_stock', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ item_id: itemId, change: change }) });

    if (result?.success) {
        const oldStock = parseInt(itemInState.stock, 10);
        itemInState.stock = result.new_stock;
        if (result.new_stock === 0 && oldStock > 0) showToast(`¡El ítem '${itemInState.nombre}' se ha quedado sin stock!`, 'warning');
        updateAndRenderItems();
        updateOutOfStockNotification();
        if (state.selectedItem?.id == itemId) { 
            state.selectedItem.stock = result.new_stock; 
            renderItemDetails(state.selectedItem); 
        }
    } else { 
        showToast('Error al actualizar el stock.', 'danger'); 
    }
}
async function copyItem(event, itemId) {
    event.stopPropagation();
    if (await ModalManager.ask({ title: 'Copiar Ítem', message: '¿Estás seguro de que quieres crear una copia de este ítem?', confirmText: 'Copiar', confirmButtonClass: 'btn-primary' })) {
        const result = await fetchAPI('api/router.php?action=copy_item', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: itemId }) });
        if (result?.success && result.id) {
            await handleDataChange('items');
            showToast('Ítem copiado con éxito. Ahora puedes editar la copia.');
            openEditItemModal(null, result.id);
        } else {
            showToast('Error al copiar el ítem: ' + (result?.error || 'Error desconocido.'), 'danger');
        }
    }
}

// --- Tag and Corte Management ---
async function createNewTag() { const btn = document.getElementById('create-tag-btn'); toggleButtonLoading(btn, true); const input = document.getElementById('new-tag-name-input'); const name = input.value.trim(); if (!name) { toggleButtonLoading(btn, false); return; } const result = await fetchAPI('api/router.php?action=add_tag', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nombre: name }) }); if (result?.success) { input.value = ''; await refreshData(); showToast('Tag creado.'); } else { showToast(`Error: ${result?.message || 'Error desconocido'}`, 'danger'); } toggleButtonLoading(btn, false); }
function editTag(event, id) { event.stopPropagation(); state.editingTagId = id; renderTagsManagement(state.allTags); }
function cancelEditTag(event) { event.stopPropagation(); state.editingTagId = null; renderTagsManagement(state.allTags); }
async function saveTag(event, id) { event.stopPropagation(); const input = document.querySelector(`#tag-name-${id} input`); const result = await fetchAPI('api/router.php?action=edit_tag', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, nombre: input.value }) }); if(result?.success) { state.editingTagId = null; await refreshData(); showToast('Tag guardado.'); } else if(result?.error === 'duplicate') { showToast(result.message, 'warning'); } else { showToast('Error al guardar el tag.', 'danger'); } }
async function deleteTag(event, id) { event.stopPropagation(); if (await ModalManager.ask({ title: 'Eliminar Tag', message: '¿Seguro que quieres eliminar este tag?', confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) { const result = await fetchAPI('api/router.php?action=delete_tag', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) }); if(result?.success) { await refreshData(); showToast('Tag eliminado.'); } else showToast('Error al eliminar el tag.', 'danger'); } }
async function massDeleteTags() { const ids = Array.from(state.selectedTags); if(ids.length === 0) return; if (await ModalManager.ask({ title: `Eliminación Masiva`, message: `¿Estás seguro de que quieres eliminar ${ids.length} tag(s) seleccionados?`, confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) { const result = await fetchAPI(`api/router.php?action=mass_delete_tags`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids }) }); if(result?.success) { state.selectedTags.clear(); document.getElementById(`select-all-tags`).checked = false; await refreshData(); showToast(`Tag(s) eliminados.`); } else showToast(`Error en la eliminación masiva de tags.`, 'danger'); } }

function openAddCorteModal() {
    state.editingCorteId = null;
    document.getElementById('corteForm').reset();
    document.getElementById('corteModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Añadir Corte';
    const preview = document.getElementById('corte-imagen-preview');
    preview.classList.add('d-none');
    preview.src = '';
    ModalManager.show('corteModal');
}

async function openEditCorteModal(event, id) {
    event.stopPropagation();
    const corte = await fetchAPI(`api/router.php?action=get_corte_details&id=${id}`);
    if (!corte) {
        showToast('No se pudo cargar la información del corte.', 'danger');
        return;
    }
    state.editingCorteId = id;
    const form = document.getElementById('corteForm');
    form.querySelector('#corte-id').value = corte.id;
    form.querySelector('[name="nombre"]').value = corte.nombre;
    form.querySelector('[name="bitting"]').value = corte.bitting || '';
    
    const preview = document.getElementById('corte-imagen-preview');
    if (corte.imagen) { 
        preview.src = `data:${corte.imagen_mime};base64,${corte.imagen}`; 
        preview.classList.remove('d-none'); 
    } else { 
        preview.classList.add('d-none'); 
        preview.src = ''; 
    }
    document.getElementById('corteModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> Editar Corte';
    ModalManager.show('corteModal');
}

async function saveCorte() {
    const saveBtn = document.getElementById('saveCorteBtn');
    toggleButtonLoading(saveBtn, true);
    const form = document.getElementById('corteForm');
    const formData = new FormData(form);
    if (!formData.get('nombre')) {
        showToast("El nombre del corte es requerido.", "warning");
        toggleButtonLoading(saveBtn, false);
        return;
    }
    
    try {
        const action = state.editingCorteId ? 'edit_corte' : 'add_corte';
        const result = await fetchAPI(`api/router.php?action=${action}`, { method: 'POST', body: formData });
        if (result?.success) {
            ModalManager.hide('corteModal');
            await refreshData();
            showToast(`Corte ${state.editingCorteId ? 'actualizado' : 'guardado'} con éxito.`);
        } else {
            showToast(`Error: ${result?.message || 'Error desconocido'}`, 'danger');
        }
    } finally {
        toggleButtonLoading(saveBtn, false);
    }
}

async function deleteCorte(event, id) { event.stopPropagation(); if (await ModalManager.ask({ title: 'Eliminar Corte', message: '¿Seguro que quieres eliminar este corte?', confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) { const result = await fetchAPI('api/router.php?action=delete_corte', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) }); if(result?.success) { await refreshData(); showToast('Corte eliminado.'); } else showToast('Error al eliminar el corte.', 'danger'); } }
async function massDeleteCortes() { const ids = Array.from(state.selectedCortes); if(ids.length === 0) return; if (await ModalManager.ask({ title: `Eliminación Masiva`, message: `¿Estás seguro de que quieres eliminar ${ids.length} corte(s) seleccionados?`, confirmText: 'Eliminar', confirmButtonClass: 'btn-danger' })) { const result = await fetchAPI(`api/router.php?action=mass_delete_cortes`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids }) }); if(result?.success) { state.selectedCortes.clear(); document.getElementById(`select-all-cortes`).checked = false; await refreshData(); showToast(`Corte(s) eliminados.`); } else showToast(`Error en la eliminación masiva de cortes.`, 'danger'); } }


// --- Image & Assignment Modals ---
function openItemImage(element, itemName, imageType) { event.stopPropagation(); const title = `${itemName} - ${imageType}`; const downloadName = `${itemName.replace(/[\s\W]/g, '_')}_${imageType.toLowerCase()}.jpg`; ModalManager.showImage({ src: element.src, title: title, downloadName: downloadName }); }
function copyImageUrl() { const imageUrl = document.getElementById('imageViewerContent').src; if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(imageUrl).then(() => showToast('URL de la imagen copiada al portapapeles.')).catch(() => fallbackCopyToClipboard(imageUrl)); } else { fallbackCopyToClipboard(imageUrl); } }
function fallbackCopyToClipboard(text) { const textArea = document.createElement("textarea"); textArea.value = text; textArea.style.position = "fixed"; textArea.style.top = "-999px"; textArea.style.left = "-999px"; document.body.appendChild(textArea); textArea.focus(); textArea.select(); try { if (document.execCommand('copy')) { showToast('URL de la imagen copiada al portapapeles.'); } else { showToast('No se pudo copiar la URL.', 'danger'); } } catch (err) { showToast('Error al copiar la URL.', 'danger'); console.error('Error al copiar:', err); } document.body.removeChild(textArea); }
function openAssignItemToMultipleAutosModal(fromDetails = false) { if (fromDetails && state.selectedAuto) { state.selectedAutos.add(parseInt(state.selectedAuto.id)); } if (state.selectedAutos.size === 0) { showToast("Selecciona al menos un vehículo.", "info"); return; } document.getElementById('assign-to-multiple-autos-count').textContent = state.selectedAutos.size; renderItemsForMassAssignment(); ModalManager.show('assignItemToMultipleAutosModal'); }
function renderItemsForMassAssignment() { const list = document.getElementById('massAssignItemsList'); const searchTerm = document.getElementById('item-search-for-mass-assign-modal').value.toLowerCase(); const filteredItems = state.items.filter(i => i.nombre.toLowerCase().includes(searchTerm)); list.innerHTML = ''; if (filteredItems.length === 0) { list.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay ítems que coincidan.</td></tr>'; return; } filteredItems.forEach(item => { const row = document.createElement('tr'); row.innerHTML = `<td>${item.nombre}</td><td>${item.descripcion || ''}</td><td>${item.tags ? item.tags.split(', ').map(t => `<span class="tag">${t}</span>`).join('') : ''}</td><td class="text-end"><button class="btn btn-sm btn-success" onclick="assignItemToSelectedAutos(${item.id})">Asignar <i class="bi bi-check-lg"></i></button></td>`; list.appendChild(row); }); }
async function assignItemToSelectedAutos(itemId) { const autoIds = Array.from(state.selectedAutos); if (autoIds.length === 0) return; const result = await fetchAPI('api/router.php?action=assign_item_to_multiple_autos', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ item_id: itemId, auto_ids: autoIds }) }); if (result?.success) { ModalManager.hide('assignItemToMultipleAutosModal'); document.querySelectorAll('#autos-list input[type="checkbox"]').forEach(cb => cb.checked = false); state.selectedAutos.clear(); updateItemMassActionButtons(); if(state.selectedAuto && autoIds.includes(parseInt(state.selectedAuto.id, 10))) { await handleDataChange('autos', () => selectAuto(state.selectedAuto.id)); } else { await handleDataChange('autos'); } showToast('Ítem asignado con éxito.'); } else showToast('Error al asignar el ítem: ' + (result?.message || 'Error desconocido.'), 'danger'); }
function openAssignVehiclesToItemModal(itemId, itemName, associatedAutoIds) { const modal = document.getElementById('assignVehiclesToItemModal'); document.getElementById('assign-item-name').textContent = itemName; modal.dataset.itemId = itemId; modal.dataset.initialSelectedAutoIds = JSON.stringify(associatedAutoIds); document.getElementById('vehicle-search-for-item-modal').value = ''; renderVehiclesForAssignment(); ModalManager.show('assignVehiclesToItemModal'); }
function renderVehiclesForAssignment() {
    const modal = document.getElementById('assignVehiclesToItemModal');
    const listContainer = document.getElementById('assign-vehicles-list');
    const searchTerm = document.getElementById('vehicle-search-for-item-modal').value;
    const associatedAutoIds = new Set(JSON.parse(modal.dataset.initialSelectedAutoIds || '[]'));
    
    const filteredBrands = filterAutos();

    if (filteredBrands.length === 0) { listContainer.innerHTML = '<p class="text-center text-muted mt-3">No hay vehículos que coincidan.</p>'; return; }

    let html = '<div class="accordion" id="brandsAccordionModal">';
    filteredBrands.forEach((brand, brandIndex) => {
        const brandId = `brand-assign-${brandIndex}`;
        html += `<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#${brandId}">${brand.marca}</button></h2><div id="${brandId}" class="accordion-collapse collapse show"><div class="accordion-body">`;
        brand.modelos.forEach(model => {
            const allVersionIds = model.versions.map(v => parseInt(v.id)); const areAllSelected = allVersionIds.every(id => associatedAutoIds.has(id));
            html += `<div class="mb-2 p-2 border rounded"><div class="form-check fw-bold"><input class="form-check-input" type="checkbox" value="" id="model-check-${brand.marca}-${model.modelo}" data-version-ids='[${allVersionIds.join(',')}]' onchange="handleAssignGroupCheck(this, true)" ${areAllSelected ? 'checked' : ''}><label class="form-check-label" for="model-check-${brand.marca}-${model.modelo}">${model.modelo}</label></div><ul class="list-group list-group-flush">`;
            model.versions.forEach(auto => { const isChecked = associatedAutoIds.has(parseInt(auto.id)); const yearText = formatYearRange(auto.anio_inicio, auto.anio_fin); const specText = [auto.spec1, auto.spec2].filter(Boolean).join(' / '); html += `<li class="list-group-item"><label class="d-flex align-items-center w-100"><input class="form-check-input me-3" type="checkbox" value="${auto.id}" onchange="handleAssignGroupCheck(this, false)" ${isChecked ? 'checked' : ''}><div class="flex-grow-1"><strong>${yearText}</strong>${specText ? `<div class="spec-details">${specText}</div>` : ''}</div></label></li>`; });
            html += `</ul></div>`;
        });
        html += '</div></div></div>';
    });
    html += '</div>';
    listContainer.innerHTML = html;
    
    listContainer.querySelectorAll('input[id^="model-check-"]').forEach(groupCheckbox => { const versionIds = JSON.parse(groupCheckbox.dataset.versionIds || '[]'); const allSelected = versionIds.every(id => associatedAutoIds.has(id)); const someSelected = !allSelected && versionIds.some(id => associatedAutoIds.has(id)); if(someSelected) groupCheckbox.indeterminate = true; });
}
function handleAssignGroupCheck(checkbox, isGroup) { const modal = document.getElementById('assignVehiclesToItemModal'); let selectedIds = new Set(JSON.parse(modal.dataset.initialSelectedAutoIds || '[]')); if (isGroup) { const versionIds = JSON.parse(checkbox.dataset.versionIds || '[]'); versionIds.forEach(id => { if (checkbox.checked) selectedIds.add(id); else selectedIds.delete(id); }); } else { const id = parseInt(checkbox.value); if (checkbox.checked) selectedIds.add(id); else selectedIds.delete(id); } modal.dataset.initialSelectedAutoIds = JSON.stringify(Array.from(selectedIds)); renderVehiclesForAssignment(); }
async function saveVehicleAssignments() {
    const saveBtn = document.getElementById('save-vehicle-assignments-btn');
    toggleButtonLoading(saveBtn, true);
    const modal = document.getElementById('assignVehiclesToItemModal');
    const itemId = modal.dataset.itemId;
    if (!itemId) return;
    try {
        const selectedAutoIds = JSON.parse(modal.dataset.initialSelectedAutoIds || '[]');
        const result = await fetchAPI('api/router.php?action=assign_vehicles_to_item', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ item_id: parseInt(itemId), auto_ids: selectedAutoIds }) });
        if (result?.success) { ModalManager.hide('assignVehiclesToItemModal'); await selectItem(itemId); showToast('Asignaciones guardadas.');} 
        else { showToast(`Error al guardar las asignaciones: ${result?.message || 'Error desconocido'}`, 'danger'); }
    } finally {
        toggleButtonLoading(saveBtn, false);
    }
}
async function unassignItem(autoId, itemId) { const result = await fetchAPI('api/router.php?action=unassign_item_from_auto', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ auto_id: autoId, item_id: itemId }) }); if (result.success) { await handleDataChange('autos', () => selectAuto(autoId)); showToast('Ítem desasignado.'); } else { showToast('Error al desasignar el ítem.', 'danger'); } }

// --- Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
    if(!document.getElementById('items-tab')) return;
    
    document.getElementById('addItemModal').addEventListener('hidden.bs.modal', () => { 
        state.editingItemId = null; 
        document.getElementById('addItemForm').reset(); 
        document.getElementById('imagen-preview').classList.add('d-none'); 
        document.getElementById('imagen-detalle-preview').classList.add('d-none'); 
    });
    
    const corteModal = document.getElementById('corteModal');
    if (corteModal) {
        corteModal.addEventListener('hidden.bs.modal', () => {
            state.editingCorteId = null;
            document.getElementById('corteForm').reset();
            const preview = document.getElementById('corte-imagen-preview');
            preview.classList.add('d-none');
            preview.src = '#';
        });
        corteModal.addEventListener('keydown', function(e) { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); document.getElementById('saveCorteBtn').click(); } });
    }

    document.getElementById('vehicle-search-for-item-modal').addEventListener('keyup', () => renderVehiclesForAssignment());
    document.getElementById('item-library-search').addEventListener('keyup', () => updateAndRenderItems());
    document.getElementById('item-search-for-mass-assign-modal').addEventListener('keyup', () => renderItemsForMassAssignment());

    document.getElementById('item-sort-select').addEventListener('change', (e) => {
        state.sortOrderItems = e.target.value;
        updateAndRenderItems();
    });

    const slider = document.getElementById('items-per-row-slider');
    const sliderValue = document.getElementById('items-per-row-value');
    slider.addEventListener('input', e => {
        const configValue = state.itemsPerRowConfig[e.target.value];
        state.itemsPerRow = configValue;
        sliderValue.textContent = configValue;
        updateAndRenderItems();
    });

    const tagsInput = document.getElementById('tags-input'), corteInput = document.getElementById('corte-input');
    tagsInput.addEventListener('keyup', e => { if (['ArrowDown', 'ArrowUp', 'Enter', 'Tab', 'Escape'].includes(e.key)) return; const parts = e.target.value.split(','); const currentTag = parts[parts.length - 1].trim().toLowerCase(); if(currentTag) { renderTagSuggestions(state.allTags.filter(t => t.nombre.toLowerCase().startsWith(currentTag))); } else { document.getElementById('tags-suggestions').style.display = 'none'; } });
    corteInput.addEventListener('keyup', e => { if (['ArrowDown', 'ArrowUp', 'Enter', 'Tab', 'Escape'].includes(e.key)) return; const value = e.target.value.toLowerCase(); if(value) { renderCorteSuggestions(state.autocomplete.cortes.filter(c => c.toLowerCase().startsWith(value))); } else { document.getElementById('corte-suggestions').style.display = 'none'; } });

    document.addEventListener('click', e => { 
        if (document.getElementById('tags-suggestions') && !e.target.closest('#tags-input-container')) { document.getElementById('tags-suggestions').style.display = 'none'; }
        if (document.getElementById('corte-suggestions') && !e.target.closest('#corte-input-container')) { document.getElementById('corte-suggestions').style.display = 'none'; }
    });

    document.getElementById('addItemModal').addEventListener('keydown', function(e) { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); document.getElementById('saveItemBtn').click(); } });
});
