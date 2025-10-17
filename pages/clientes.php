<!-- pages/clientes.php -->
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0"><i class="bi bi-people-fill"></i> Gestión de Clientes</h4>
    </div>
    <div class="card-body">
        <div class="row mb-3 align-items-center">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="cliente-search" class="form-control" placeholder="Buscar por nombre, teléfono, CVU...">
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <button class="btn btn-primary" onclick="openAddClienteModal()">
                    <i class="bi bi-person-plus-fill"></i> Añadir Nuevo Cliente
                </button>
            </div>
        </div>

        <div class="table-responsive" style="height: calc(100vh - 350px); overflow-y: auto;">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>CVU/Alias</th>
                        <th>Trabajos</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="clientes-table-body">
                    <!-- Client rows will be inserted here by cliente.js -->
                </tbody>
            </table>
        </div>
    </div>
</div>
