// assets/js/main.js

// Global state container for shared data
const state = {
    autos: [],
    items: [],
    equipos: [],
    trabajos: [],
    clientes: [],
    tiposTrabajo: [],
    allTags: [],
    allCortes: [],
    selectedAuto: null,
    selectedItem: null,
    selectedEquipo: null,
    scrollPositions: { autos: 0, items: 0, equipos: 0, clientes: 0 },
    autocomplete: { marcas: [], modelos: [], spec1: [], spec2: [], cortes: [] },
};

// --- LAZY LOADING OBSERVER ---
// This single observer will watch for all images with the 'lazy-load' class
window.lazyLoadObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            const src = img.getAttribute('data-src');
            if (src) {
                img.src = src;
                img.classList.remove('lazy-load'); // Stop observing once loaded
            }
            observer.unobserve(img); // Unobserve the image
        }
    });
}, { rootMargin: "0px 0px 200px 0px" }); // Start loading when image is 200px away from viewport


// --- Core UI & Helper Functions ---

/**
 * Checks if the current viewport width is considered mobile (less than Bootstrap's 'sm' breakpoint).
 * @returns {boolean} True if in mobile view.
 */
const isMobileView = () => window.innerWidth < 576;

/**
 * Gets the list and details panel elements for a given section.
 * @param {string} type - The section type ('autos', 'items', 'equipos').
 * @returns {{list: HTMLElement|null, details: HTMLElement|null}}
 */
function getPanelElements(type) {
    switch (type) {
        case 'autos':
            return {
                list: document.getElementById('autos-list-column'),
                details: document.getElementById('autos-details-column')
            };
        case 'items':
            return {
                list: document.getElementById('items-library-grid-container'),
                details: document.getElementById('item-details-container')
            };
        case 'equipos':
            return {
                list: document.getElementById('equipos-list-column'),
                details: document.getElementById('equipos-details-column')
            };
        default:
            return { list: null, details: null };
    }
}

/**
 * Shows the list view and hides the details view on mobile.
 * @param {string} type - The section type ('autos', 'items', 'equipos').
 */
function showListView(type) {
    if (!isMobileView()) return;
    const { list, details } = getPanelElements(type);
    if (list && details) {
        list.classList.remove('view-hidden-mobile');
        details.classList.add('view-hidden-mobile');
    }
}

/**
 * Shows the details view and hides the list view on mobile.
 * @param {string} type - The section type ('autos', 'items', 'equipos').
 */
function showDetailsView(type) {
    if (!isMobileView()) return;
    const { list, details } = getPanelElements(type);
    if (list && details) {
        list.classList.add('view-hidden-mobile');
        details.classList.remove('view-hidden-mobile');
        window.scrollTo(0, 0); // Scroll to top to see the details panel
    }
}

// Manages all Bootstrap modal instances in the application.
const modalInstances = {};
const ModalManager = {
    initialize() {
        document.querySelectorAll('.modal').forEach(modalEl => {
            if (modalEl.id) {
                modalInstances[modalEl.id] = new bootstrap.Modal(modalEl);
            }
        });
    },
    show(id) { if (modalInstances[id]) modalInstances[id].show(); },
    hide(id) { if (modalInstances[id]) modalInstances[id].hide(); },
    ask(options = {}) {
        return new Promise(resolve => {
            const modalEl = document.getElementById('confirmActionModal');
            const confirmBtn = document.getElementById('confirmActionConfirmBtn');
            if (!modalEl || !confirmBtn) { console.error('Confirmation modal elements not found!'); return resolve(false); }
            const onConfirm = () => { this.hide('confirmActionModal'); resolve(true); };
            const onHide = (e) => { if (e.target === modalEl) resolve(false); newConfirmBtn.removeEventListener('click', onConfirm); };
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            newConfirmBtn.addEventListener('click', onConfirm, { once: true });
            modalEl.addEventListener('hidden.bs.modal', onHide, { once: true });
            document.getElementById('confirmActionModalLabel').textContent = options.title || 'Confirmar Acción';
            document.getElementById('confirmActionModalBody').innerHTML = options.message || '¿Estás seguro?';
            newConfirmBtn.textContent = options.confirmText || 'Confirmar';
            newConfirmBtn.className = `btn ${options.confirmButtonClass || 'btn-primary'}`;
            this.show('confirmActionModal');
        });
    },
    showImage(options) {
        const modalEl = document.getElementById('imageViewerModal');
        if (!modalEl) return;
        modalEl.querySelector('#imageViewerContent').src = options.src;
        modalEl.querySelector('#imageViewerModalLabel').textContent = options.title;
        const downloadBtn = modalEl.querySelector('#downloadImageBtn');
        downloadBtn.href = options.src;
        downloadBtn.download = options.downloadName;
        this.show('imageViewerModal');
    }
};

/**
 * Displays a toast notification.
 * @param {string} message - The message to show.
 * @param {string} [type='success'] - The toast type ('success', 'danger', 'warning', etc.).
 */
function showToast(message, type = 'success') {
    const toastContainer = document.querySelector('.toast-container');
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'polite');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    toastContainer.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

/**
 * Toggles a button's state between loading and normal.
 * @param {HTMLElement} button - The button element.
 * @param {boolean} isLoading - Whether to show the loading state.
 */
function toggleButtonLoading(button, isLoading) {
    if (!button) return;
    if (isLoading) {
        button.disabled = true;
        button.dataset.originalHtml = button.innerHTML;
        button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
    } else {
        button.disabled = false;
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    }
}

/**
 * Generates HTML for an empty state message.
 * @param {string} iconClass - The Bootstrap Icon class.
 * @param {string} title - The main title text.
 * @param {string} text - The descriptive text.
 * @returns {string} The HTML string for the empty state.
 */
function getEmptyStateHTML(iconClass, title, text) {
    return `<div class="col-12">
                <div class="initial-view text-center">
                    <i class="${iconClass} text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">${title}</h4>
                    <p class="lead text-muted">${text}</p>
                </div>
            </div>`;
}


/**
 * Previews an image from a file input.
 * @param {HTMLInputElement} input - The file input element.
 * @param {string} previewId - The ID of the img element for the preview.
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.classList.add('d-none');
    }
}


// --- Theme Management ---
const themeToggler = document.getElementById('theme-toggler');
const sunIcon = '<i class="bi bi-sun-fill"></i>';
const moonIcon = '<i class="bi bi-moon-fill"></i>';

/**
 * Applies a color theme to the document and saves it to localStorage.
 * @param {string} theme - The theme to apply ('light' or 'dark').
 */
function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    if(themeToggler) themeToggler.innerHTML = theme === 'dark' ? sunIcon : moonIcon;
    localStorage.setItem('theme', theme);
}

/**
 * Toggles the current color theme.
 */
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
    applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
}

// --- API & Data Handling ---

/**
 * Fetches data from the API endpoint.
 * @param {string} url - The API URL to fetch.
 * @param {object} [options={}] - The options for the fetch request.
 * @returns {Promise<object|null>} The parsed JSON response or null on error.
 */
async function fetchAPI(url, options = {}) {
    let text;
    try {
        const response = await fetch(url, options);
        if (options.body instanceof FormData) {
             text = await response.text();
             try { return JSON.parse(text); }
             catch (e) { console.error("Failed to parse JSON response after FormData post:", text); return { success: false, message: "Respuesta inválida del servidor." }; }
        }
        text = await response.text();
        if (!text && !response.ok) return { success: false, message: `Error ${response.status}: ${response.statusText}` };
        if (!text && response.ok) return { success: true };
        const result = JSON.parse(text);
        if (!response.ok) { console.error("API Error (HTTP Status not OK):", result); return result; }
        return result;
    } catch (error) {
        console.error("Fetch/Parse Error:", error, "\nRaw response text:", text);
        showToast("Error al procesar la respuesta del servidor. Revise la consola (F12).", 'danger');
        return { success: false, message: "Respuesta inválida del servidor." };
    }
}

/**
 * Formats a year range for display.
 * @param {number|string|null} start - The starting year.
 * @param {number|string|null} end - The ending year.
 * @returns {string} The formatted year range string.
 */
function formatYearRange(start, end) {
    const startYear = parseInt(start, 10);
    const endYear = parseInt(end, 10);
    if (isNaN(startYear) || startYear === 0) return 'Cualquier año';
    if (isNaN(endYear) || endYear === 0) return `Desde ${startYear}`;
    if (startYear === endYear) return startYear;
    return `${startYear} - ${endYear}`;
}


/**
 * Fetches all data, updates the state, and re-renders all sections.
 * @param {function} [callback] - An optional callback to run after data is refreshed.
 */
async function refreshData(callbackOrIsSilent) {
    const [autos, items, tags, cortes, equipos, trabajosData, tiposTrabajo, clientes] = await Promise.all([
        fetchAPI('api/router.php?action=get_autos'),
        fetchAPI('api/router.php?action=get_items'),
        fetchAPI('api/router.php?action=get_tags_with_usage'),
        fetchAPI('api/router.php?action=get_cortes_with_usage'),
        fetchAPI('api/router.php?action=get_equipos'),
        fetchAPI('api/router.php?action=get_trabajos'),
        fetchAPI('api/router.php?action=get_tipos_trabajo'),
        fetchAPI('api/router.php?action=get_clientes')
    ]);

    const isSilent = callbackOrIsSilent === true;

    // --- MODIFICATION START ---
    // Always update state first, but ONLY if the fetched data is of the correct type.
    // This prevents a failed API call (which returns an error object) from corrupting a state property that should be an array.
    if (Array.isArray(autos)) state.autos = autos;
    if (Array.isArray(items)) state.items = items;
    if (Array.isArray(tags)) state.allTags = tags;
    if (Array.isArray(cortes)) state.allCortes = cortes;
    if (Array.isArray(equipos)) state.equipos = equipos;
    if (Array.isArray(clientes)) state.clientes = clientes;
    // Special handling for trabajosData, which is an object containing an array and other properties.
    if (trabajosData && Array.isArray(trabajosData.trabajos)) {
        state.trabajos = trabajosData.trabajos;
    }
    if (Array.isArray(tiposTrabajo)) {
        state.tiposTrabajo = tiposTrabajo;
    }
    // --- MODIFICATION END ---

    // Determine if it's safe to render the main page's UI components.
    // It's safe only if this is NOT a silent refresh AND we are on the main page (which has the 'mainTab' element).
    const canRenderMainUI = !isSilent && document.getElementById('mainTab');
    
    if (canRenderMainUI) {
        // This part now safely uses the state which we know has not been corrupted.
        if (trabajosData && trabajosData.trabajos) {
            // Check for function existence before calling, as they live in other files.
            if (typeof renderTrabajos === 'function') renderTrabajos(trabajosData.trabajos);
            if (window.updateStats) window.updateStats(trabajosData.trabajos);
        }
        if (trabajosData && trabajosData.stats) {
            if (typeof renderFinancialStats === 'function') renderFinancialStats(trabajosData.stats);
        }
        if (tiposTrabajo) {
            if (typeof renderTiposTrabajoManagement === 'function') renderTiposTrabajoManagement(tiposTrabajo);
        }

        // Call render functions which exist in their own files
        if (typeof updateAndRenderAutos === 'function') updateAndRenderAutos();
        if (typeof updateAndRenderItems === 'function') updateAndRenderItems();
        if (typeof renderTagsManagement === 'function') renderTagsManagement(tags);
        if (typeof renderCortesManagement === 'function') renderCortesManagement(cortes);
        if (typeof renderEquipos === 'function') renderEquipos(equipos);
        if (typeof renderClientes === 'function') renderClientes();
        if (typeof updateOutOfStockNotification === 'function') updateOutOfStockNotification();
    }

    // If a valid callback FUNCTION was passed, execute it.
    if (typeof callbackOrIsSilent === 'function') {
        callbackOrIsSilent();
    }
}

/**
 * Handles data changes by saving scroll position, refreshing data, and restoring scroll position.
 * @param {string} listType - The type of list being updated ('autos', 'items', 'equipos').
 * @param {function} [callback] - An optional callback to run after data is refreshed.
 */
async function handleDataChange(listType, callback) {
    let containerId;
    switch (listType) {
        case 'autos': containerId = 'autos-list-container'; break;
        case 'items': containerId = 'items-library-grid-container'; break;
        case 'equipos': containerId = 'equipos-list'; break;
        case 'clientes': containerId = 'clientes-table-body'; break;
        default: containerId = null;
    }

    const container = containerId ? document.getElementById(containerId) : null;
    if (container) state.scrollPositions[listType] = container.scrollTop;

    await refreshData(() => {
        if (callback) callback();
        requestAnimationFrame(() => { if (container) container.scrollTop = state.scrollPositions[listType]; });
    });
}

/**
 * Loads all initial data required for the application to start.
 */
async function loadInitialData() {
    await refreshData();
    const [marcas, modelos, spec1, spec2, cortes] = await Promise.all([
        fetchAPI('api/router.php?action=get_autocomplete_data&field=marca'),
        fetchAPI('api/router.php?action=get_autocomplete_data&field=modelo'),
        fetchAPI('api/router.php?action=get_autocomplete_data&field=spec1'),
        fetchAPI('api/router.php?action=get_autocomplete_data&field=spec2'),
        fetchAPI('api/router.php?action=get_autocomplete_data_cortes'),
    ]);
    if (marcas) state.autocomplete.marcas = marcas;
    if (modelos) state.autocomplete.modelos = modelos;
    if (spec1) state.autocomplete.spec1 = spec1;
    if (spec2) state.autocomplete.spec2 = spec2;
    if (cortes) state.autocomplete.cortes = cortes;
}


// --- Document Ready and Global Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
    applyTheme(localStorage.getItem('theme') || 'light');
    ModalManager.initialize();

    // Handle mobile view layout on initial load and resize
    if (isMobileView()) {
        showListView('autos');
        showListView('items');
        showListView('equipos');
    }

    window.addEventListener('resize', () => {
        if (!isMobileView()) {
            // On desktop, ensure all panels are visible
            ['autos', 'items', 'equipos'].forEach(type => {
                const { list, details } = getPanelElements(type);
                if (list) list.classList.remove('view-hidden-mobile');
                if (details) details.classList.remove('view-hidden-mobile');
            });
        } else {
            // On mobile, if no item is selected, show the list
            if (!state.selectedAuto) showListView('autos');
            if (!state.selectedItem) showListView('items');
            if (!state.selectedEquipo) showListView('equipos');
        }
    });

    if (document.getElementById('mainTab')) {
        
        // Setup listener to SAVE tab state for SUB-TABS ONLY.
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', event => {
                const tabListId = event.target.closest('.nav-tabs, .nav-pills')?.id;
                // We only save state for sub-tabs, not the main navigation.
                if (tabListId && tabListId !== 'mainTab') { 
                    localStorage.setItem('activeTab-' + tabListId, event.target.id);
                }
            });
        });

        const initializeSubTabsAndComponents = () => {
            // Restore ONLY sub-tabs by iterating through their containers
            document.querySelectorAll('.nav-tabs, .nav-pills').forEach(tabContainer => {
                const containerId = tabContainer.id;
                if (containerId && containerId !== 'mainTab') {
                    const activeTabId = localStorage.getItem('activeTab-' + containerId);
                    if (activeTabId) {
                        const tabEl = document.getElementById(activeTabId);
                        if (tabEl) {
                            bootstrap.Tab.getOrCreateInstance(tabEl).show();
                        }
                    }
                }
            });

            // Fallback for sub-tabs if they have no saved state
            if (!localStorage.getItem('activeTab-itemSubTab')) {
                const defaultItemSubTab = document.getElementById('explore-items-tab');
                if (defaultItemSubTab) bootstrap.Tab.getOrCreateInstance(defaultItemSubTab).show();
            }
            if (!localStorage.getItem('activeTab-trabajoSubTab')) {
                const defaultTrabajoSubTab = document.getElementById('explore-trabajos-tab');
                if (defaultTrabajoSubTab) bootstrap.Tab.getOrCreateInstance(defaultTrabajoSubTab).show();
            }


            // Setup specific listeners after initial show
            const statsTab = document.getElementById('stats-trabajos-tab');
            if (statsTab) {
                statsTab.addEventListener('shown.bs.tab', () => {
                    if (window.initStats && state.trabajos) {
                        initStats(state.trabajos);
                    }
                });
            }
        };

        // Load initial data, then initialize the sub-tabs and components.
        loadInitialData().then(initializeSubTabsAndComponents).catch(error => {
            console.error("Failed to load initial data or initialize components:", error);
            // Even if data loading fails, we don't need a fallback for the main tab
            // as it's already rendered correctly by the server.
        });
    }
});

