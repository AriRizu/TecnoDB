<!-- pages/dashboard.php -->
<style>
    .dashboard-card {
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: block;
        height: 100%;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,.12);
    }
    .dashboard-card .card-icon {
        font-size: 3rem;
        color: var(--bs-primary);
    }
    .stat-card .card-body {
        min-height: 140px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .dashboard-card .card-icon {
            font-size: 2.5rem;
        }
        .stat-card .card-body {
            min-height: 120px;
        }
        h1 {
            font-size: 1.75rem;
        }
    }
    
    @media (max-width: 767.98px) {
        .container {
            padding-left: 15px;
            padding-right: 15px;
        }
        .stat-card .card-body {
            min-height: 100px;
            padding: 1rem !important;
        }
        .stat-card .card-title {
            font-size: 1.5rem !important;
        }
        .stat-card .card-text {
            font-size: 0.875rem;
        }
        .stat-card .bi {
            font-size: 2rem !important;
        }
        .dashboard-card .card-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem !important;
        }
        .dashboard-card .card-title {
            font-size: 1rem;
        }
        .dashboard-card .card-text {
            font-size: 0.8rem;
        }
        .dashboard-card .card-body {
            padding: 1rem !important;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem !important;
        }
        h2 {
            font-size: 1.25rem;
        }
    }
    
    @media (max-width: 575.98px) {
        .container {
            padding-left: 10px;
            padding-right: 10px;
        }
        .py-4 {
            padding-top: 1.5rem !important;
            padding-bottom: 1.5rem !important;
        }
        .stat-card .card-body {
            min-height: 90px;
        }
        .stat-card .card-title {
            font-size: 1.25rem !important;
        }
        .stat-card .card-text {
            font-size: 0.75rem;
        }
        .g-4 {
            --bs-gutter-x: 0.75rem;
            --bs-gutter-y: 0.75rem;
        }
        .dashboard-card:hover {
            transform: translateY(-3px);
        }
        .dashboard-card .card-title {
            font-size: 0.95rem;
        }
        .dashboard-card .card-text {
            font-size: 0.75rem;
        }
    }
</style>
<div class="container py-4">
    <h1 class="mb-4">Panel de Control</h1>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card text-white bg-primary h-100 stat-card">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title fs-2" id="revenue-stat">$0</h5>
                            <p class="card-text mb-0">Ingresos (30 días)</p>
                        </div>
                        <i class="bi bi-cash-coin fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card text-white bg-success h-100 stat-card">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title fs-2" id="jobs-stat">0</h5>
                            <p class="card-text mb-0">Trabajos (30 días)</p>
                        </div>
                        <i class="bi bi-tools fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card text-white bg-secondary h-100 stat-card">
                <div class="card-body d-flex flex-column justify-content-center">
                     <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title fs-2" id="items-stat">0</h5>
                            <p class="card-text mb-0">Total Items</p>
                        </div>
                        <i class="bi bi-archive-fill fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card text-white bg-danger h-100 stat-card">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title fs-2" id="stock-stat">0</h5>
                            <p class="card-text mb-0">Items sin stock</p>
                        </div>
                        <i class="bi bi-box-seam fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    <!-- End Stats Row -->

    <h2 class="h4 mb-3">Accesos Directos</h2>
    <div class="row g-4">

         <!-- Card: Trabajos y Finanzas -->
        <div class="col-6 col-md-6 col-lg-4">
            <a href="index.php?page=main&tab=trabajos" class="dashboard-card">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center p-4">
                        <i class="bi bi-journal-check card-icon mb-3"></i>
                        <h5 class="card-title">Trabajos</h5>
                        <p class="card-text text-muted">Registra trabajos, procedimientos e ingresos.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Card: Gestión de Vehículos -->
        <div class="col-6 col-md-6 col-lg-4">
            <a href="index.php?page=main&tab=vehiculos" class="dashboard-card">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center p-4">
                        <i class="bi bi-car-front card-icon mb-3"></i>
                        <h5 class="card-title">Gestión de Vehículos</h5>
                        <p class="card-text text-muted">Administra el catálogo de autos, modelos y versiones.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Card: Biblioteca de Ítems -->
        <div class="col-6 col-md-6 col-lg-4">
            <a href="index.php?page=main&tab=items" class="dashboard-card">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center p-4">
                        <i class="bi bi-cpu-fill card-icon mb-3"></i>
                        <h5 class="card-title">Biblioteca de Ítems</h5>
                        <p class="card-text text-muted">Gestiona el inventario de componentes y productos.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Card: Gestión de Equipos -->
        <div class="col-6 col-md-6 col-lg-4">
            <a href="index.php?page=main&tab=equipos" class="dashboard-card">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center p-4">
                        <i class="bi bi-tools card-icon mb-3"></i>
                        <h5 class="card-title">Gestión de Equipos</h5>
                        <p class="card-text text-muted">Define y configura equipos y sus componentes.</p>
                    </div>
                </div>
            </a>
        </div>

       
        
        <!-- Card: Clientes -->
        <div class="col-6 col-md-6 col-lg-4">
            <a href="index.php?page=main&tab=clientes" class="dashboard-card">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center p-4">
                        <i class="bi bi-people-fill card-icon mb-3"></i>
                        <h5 class="card-title">Clientes</h5>
                        <p class="card-text text-muted">Administra la base de datos de clientes y sus vehículos.</p>
                    </div>
                </div>
            </a>
        </div>

            <!-- Future Card Placeholder -->
            <div class="col-6 col-md-6 col-lg-4">
                <a href="#" class="dashboard-card">
                    <div class="card text-center h-100">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center p-4">
                            <i class="bi bi-gear-fill card-icon mb-3"></i>
                            <h5 class="card-title">Configuración</h5>
                            <p class="card-text text-muted">Ajustes (NO IMPLEMENTADO).</p>
                        </div>
                    </div>
                </a>
            </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('api/router.php?action=get_dashboard_stats')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data) {
                document.getElementById('revenue-stat').textContent = `$${parseFloat(data.revenue_last_30_days || 0).toLocaleString('es-AR')}`;
                document.getElementById('jobs-stat').textContent = data.jobs_last_30_days || 0;
                document.getElementById('stock-stat').textContent = data.items_out_of_stock || 0;
                document.getElementById('items-stat').textContent = data.total_items || 0;
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard stats:', error);
        });
});
</script>