<!-- pages/main.php -->
<?php
// Determine the active tab from the URL, default to 'vehiculos'
$active_tab = $_GET['tab'] ?? 'vehiculos';
?>
<!-- Main Tab Navigation -->
<ul class="nav nav-tabs" id="mainTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php if ($active_tab === 'vehiculos') echo 'active'; ?>" id="vehiculos-tab" data-bs-toggle="tab" data-bs-target="#vehiculos-tab-pane" type="button"><i class="bi bi-car-front"></i> Gestión de Vehículos</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link position-relative <?php if ($active_tab === 'items') echo 'active'; ?>" id="items-tab" data-bs-toggle="tab" data-bs-target="#items-tab-pane" type="button">
            <i class="bi bi-cpu-fill"></i> Biblioteca de Ítems
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="out-of-stock-badge"></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php if ($active_tab === 'equipos') echo 'active'; ?>" id="equipos-tab" data-bs-toggle="tab" data-bs-target="#equipos-tab-pane" type="button"><i class="bi bi-tools"></i> Gestión de Equipos</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php if ($active_tab === 'trabajos') echo 'active'; ?>" id="trabajos-tab" data-bs-toggle="tab" data-bs-target="#trabajos-tab-pane" type="button"><i class="bi bi-journal-check"></i> Trabajos y Finanzas</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php if ($active_tab === 'clientes') echo 'active'; ?>" id="clientes-tab" data-bs-toggle="tab" data-bs-target="#clientes-tab-pane" type="button"><i class="bi bi-people-fill"></i> Clientes</button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="mainTabContent">
    <div class="tab-pane fade <?php if ($active_tab === 'vehiculos') echo 'show active'; ?>" id="vehiculos-tab-pane" role="tabpanel" tabindex="0">
        <?php include 'vehicles.php'; ?>
    </div>
    <div class="tab-pane fade <?php if ($active_tab === 'items') echo 'show active'; ?>" id="items-tab-pane" role="tabpanel" tabindex="0">
        <?php include 'items.php'; ?>
    </div>
    <div class="tab-pane fade <?php if ($active_tab === 'equipos') echo 'show active'; ?>" id="equipos-tab-pane" role="tabpanel" tabindex="0">
        <?php include 'equipos.php'; ?>
    </div>
    <div class="tab-pane fade <?php if ($active_tab === 'trabajos') echo 'show active'; ?>" id="trabajos-tab-pane" role="tabpanel" tabindex="0">
        <?php include 'trabajos.php'; ?>
    </div>
    <div class="tab-pane fade <?php if ($active_tab === 'clientes') echo 'show active'; ?>" id="clientes-tab-pane" role="tabpanel" tabindex="0">
        <?php include 'clientes.php'; ?>
    </div>
</div>

<?php
// Include all the modals needed for the dashboard
include __DIR__ . '/../partials/modals.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // When a tab is shown, update the URL for better navigation history
    const tabElms = document.querySelectorAll('#mainTab button[data-bs-toggle="tab"]');
    tabElms.forEach(function(tabElm) {
        tabElm.addEventListener('shown.bs.tab', function(event) {
            const tabId = event.target.id.replace('-tab', '');
            const newUrl = `${window.location.protocol}//${window.location.host}${window.location.pathname}?page=main&tab=${tabId}`;
            window.history.pushState({path: newUrl}, '', newUrl);
        });
    });
});
</script>
