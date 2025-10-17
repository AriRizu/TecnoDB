<!-- partials/header.php -->
<?php $page = $_GET['page'] ?? 'landing'; ?>
<style>
    .tecn-h-text { color: #4586A4; }
    .ok-h-text { color: #809A50; }
    .ey-h-text { color: #CB2C52; }

    /* Global Search Styles */
    .global-search-wrapper {
        position: relative;
        width: 300px;
    }
    /* Styles for the dropdown results container */
    .global-search-results {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        margin-top: 0.25rem;
        z-index: 1050;
        max-height: 400px;
        overflow-y: auto;
        box-shadow: var(--bs-box-shadow);
    }
    .global-search-results .list-group-item {
        cursor: pointer;
    }
    .global-search-results .list-group-item:hover {
        background-color: var(--bs-secondary-bg);
    }
    .result-context {
        font-size: 0.8em;
        color: var(--bs-secondary-color);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px; /* Prevents long context from breaking layout */
    }
    .result-type {
        font-size: 0.75em;
        font-weight: bold;
        color: #fff; /* White text for all badges */
    }
    /* Color coding for result types */
    .result-type-Auto { background-color: #0d6efd; }
    .result-type-Item { background-color: #198754; }
    .result-type-Trabajo { background-color: #ffc107; color: #000 !important; }
    .result-type-Cliente { background-color: #fd7e14; }
    .result-type-Equipo { background-color: #6f42c1; }
</style>
<nav class="navbar navbar-expand-lg bg-body-secondary sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php?page=dashboard">
            <img src="favicon.ico" alt="TecnokeyDB Logo" class="m-1" style="height: 32px; width: 32px;"> <span class="tecn-h-text">Tecn</span><span class="ok-h-text">ok</span><span class="ey-h-text">ey</span><span class="db-text">DB</span>
        </a>

        <?php if ($page === 'main'): ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbarNav" aria-controls="mainNavbarNav" aria-expanded="false" aria-label="Toggle navigation">
             <span class="navbar-toggler-icon"></span>
        </button>
        <?php endif; ?>

        <div class="collapse navbar-collapse" id="mainNavbarNav">
            <?php if ($page === 'main'): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=main&tab=vehiculos">Vehículos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=main&tab=items">Ítems</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="index.php?page=main&tab=equipos">Equipos</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="index.php?page=main&tab=trabajos">Trabajos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=main&tab=clientes">Clientes</a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <?php if ($page === 'main'): ?>
            <!-- Global Search Bar HTML -->
            <div class="global-search-wrapper">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="global-search-input" placeholder="Búsqueda rápida..." autocomplete="off">
                </div>
                <div class="global-search-results" id="global-search-results">
                    <!-- Search results will be dynamically injected here -->
                </div>
            </div>
            <?php endif; ?>
            <button class="btn btn-outline-secondary" id="theme-toggler" onclick="toggleTheme()" title="Toggle theme"><i class="bi bi-moon-fill"></i></button>
        </div>
    </div>
</nav>

<?php if ($page === 'main'): ?>
<script>
// This script will only be included on the main page with the tabs.
document.addEventListener('DOMContentLoaded', () => {
    // Sync active state of nav-links in the header with the current tab
    const currentTab = new URLSearchParams(window.location.search).get('tab') || 'vehiculos';
    const navLinks = document.querySelectorAll('#mainNavbarNav .nav-link');
    navLinks.forEach(link => {
        if(link.href.includes(`tab=${currentTab}`)) {
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        } else {
            link.classList.remove('active');
            link.removeAttribute('aria-current');
        }
    });

    const searchInput = document.getElementById('global-search-input');
    const searchResultsContainer = document.getElementById('global-search-results');
    let debounceTimer;

    const debounce = (callback, time) => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(callback, time);
    };

    const performSearch = async () => {
        const term = searchInput.value.trim();
        if (term.length < 2) {
            searchResultsContainer.style.display = 'none';
            searchResultsContainer.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`api/router.php?action=global_search&term=${encodeURIComponent(term)}`);
            if (!response.ok) throw new Error('Network response was not ok');
            const results = await response.json();
            renderResults(results);
        } catch (error) {
            console.error('Search failed:', error);
            searchResultsContainer.style.display = 'block';
            searchResultsContainer.innerHTML = `<div class="list-group-item list-group-item-danger">Error en la búsqueda.</div>`;
        }
    };

    const renderResults = (results) => {
        searchResultsContainer.innerHTML = '';
        if (results.length === 0) {
            searchResultsContainer.innerHTML = '<div class="list-group-item text-muted">No se encontraron resultados.</div>';
        } else {
            const ul = document.createElement('ul');
            ul.className = 'list-group';
            results.forEach(result => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.dataset.type = result.type.toLowerCase();
                li.dataset.id = result.id;
                
                li.innerHTML = `
                    <div>
                        <div class="fw-bold">${result.name}</div>
                        <div class="result-context">${result.context || ''}</div>
                    </div>
                    <span class="badge result-type-${result.type}">${result.type}</span>
                `;
                
                li.addEventListener('click', () => handleResultClick(result.type, result.id));
                ul.appendChild(li);
            });
            searchResultsContainer.appendChild(ul);
        }
        searchResultsContainer.style.display = 'block';
    };

    const handleResultClick = (type, id) => {
        const typeToTabId = {
            'Auto': 'vehiculos-tab',
            'Item': 'items-tab',
            'Equipo': 'equipos-tab',
            'Trabajo': 'trabajos-tab',
            'Cliente': 'clientes-tab'
        };

        const typeToFunctionName = {
            'Auto': 'selectAuto',
            'Item': 'selectItem',
            'Equipo': 'selectEquipo',
            'Trabajo': 'openEditTrabajoModal',
            'Cliente': 'openEditClienteModal'
        };

        const functionName = typeToFunctionName[type];
        const tabId = typeToTabId[type];

        if (!tabId || !functionName) {
            console.error(`No tab or function mapping found for type: ${type}`);
            return;
        }

        const tabEl = document.getElementById(tabId);
        if (!tabEl) {
            console.error(`Tab element with ID '${tabId}' not found.`);
            return;
        }

        const openDetails = () => {
            if (typeof window[functionName] === 'function') {
                if (functionName.startsWith('open')) {
                    window[functionName](null, id);
                } else {
                    window[functionName](id);
                }
            } else {
                console.warn(`Function ${functionName} not found. Details for ${type} cannot be shown.`);
            }
        };

        searchInput.value = '';
        searchResultsContainer.style.display = 'none';
        searchResultsContainer.innerHTML = '';
        
        if (tabEl.classList.contains('active')) {
            openDetails();
        } else {
            tabEl.addEventListener('shown.bs.tab', openDetails, { once: true });
            const tab = bootstrap.Tab.getOrCreateInstance(tabEl);
            tab.show();
        }
    };

    searchInput.addEventListener('input', () => debounce(performSearch, 300));

    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length > 1 && searchResultsContainer.childElementCount > 0) {
            searchResultsContainer.style.display = 'block';
        }
    });

    document.addEventListener('click', (event) => {
        if (!searchResultsContainer.contains(event.target) && event.target !== searchInput) {
            searchResultsContainer.style.display = 'none';
        }
    });
});
</script>
<?php endif; ?>
