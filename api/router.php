<?php
header("Content-Type: application/json; charset=UTF-8");

// The router is in /api, so config.php is one level up.
require_once '../config.php'; 

// These files are in the same directory or a subdirectory.
require_once 'db_setup.php';
require_once 'handlers/auto_handler.php';
require_once 'handlers/item_handler.php';
require_once 'handlers/equipo_handler.php';
require_once 'handlers/trabajo_handler.php';
require_once 'handlers/cliente_handler.php'; 
require_once 'handlers/search_handler.php';
require_once 'handlers/dashboard_handler.php'; // Added dashboard handler

$conn = getDbConnection();
initializeDatabase($conn);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Allow multipart/form-data for specific actions
$isFormData = in_array($action, ['add_item', 'edit_item', 'add_trabajo', 'edit_trabajo', 'add_equipo', 'edit_equipo', 'add_corte', 'edit_corte']);
$data = $isFormData ? null : json_decode(file_get_contents('php://input'), true);

switch ($action) {
    // --- Dashboard Route ---
    case 'get_dashboard_stats':
        echo json_encode(getDashboardStats($conn));
        break;

    // --- Auto Routes ---
    case 'get_autos': getGroupedAutos($conn); break;
    case 'edit_auto': editAuto($conn, $data); break;
    case 'get_auto_details': if (isset($_GET['id'])) getAutoDetails($conn, $_GET['id']); break;
    case 'unassign_item_from_auto': unassignItemFromAuto($conn, $data); break;
    case 'get_autocomplete_data': if (isset($_GET['field'])) getAutocompleteDataForAutos($conn, $_GET['field']); break;
    case 'delete_auto': deleteAuto($conn, $data); break;
    case 'mass_delete_autos': massDeleteAutos($conn, $data); break;
    case 'add_multiple_autos': addMultipleAutos($conn, $data); break;
    case 'assign_item_to_multiple_autos': assignItemToMultipleAutos($conn, $data); break;
    case 'edit_auto_group': editAutoGroup($conn, $data); break;
    case 'delete_auto_group': deleteAutoGroup($conn, $data); break;

    // --- Item, Tag, and Corte Routes ---
    case 'get_items': getItems($conn); break;
    case 'add_item': addItem($conn, $_POST, $_FILES); break;
    case 'edit_item': editItem($conn, $_POST, $_FILES); break;
    case 'get_item_image': if (isset($_GET['id'])) getItemImage($conn, $_GET['id'], $_GET['type'] ?? 'main'); break;
    case 'get_item_details': if (isset($_GET['id'])) getItemDetails($conn, $_GET['id']); break;
    case 'delete_item': deleteItem($conn, $data); break;
    case 'mass_delete_items': massDeleteItems($conn, $data); break;
    case 'assign_vehicles_to_item': assignVehiclesToItem($conn, $data); break;
    case 'update_item_stock': updateItemStock($conn, $data); break;
    case 'copy_item': copyItem($conn, $data); break;
    
    // Tags
    case 'get_tags_with_usage': getTagsWithUsage($conn); break;
    case 'edit_tag': editTag($conn, $data); break;
    case 'add_tag': addTag($conn, $data); break;
    case 'delete_tag': deleteTag($conn, $data); break;
    case 'mass_delete_tags': massDeleteTags($conn, $data); break;
    
    // Cortes
    case 'get_cortes_with_usage': getCortesWithUsage($conn); break;
    case 'add_corte': addCorte($conn, $_POST, $_FILES); break;
    case 'edit_corte': editCorte($conn, $_POST, $_FILES); break;
    case 'delete_corte': deleteCorte($conn, $data); break;
    case 'mass_delete_cortes': massDeleteCortes($conn, $data); break;
    case 'get_autocomplete_data_cortes': getAutocompleteDataForCortes($conn); break;
    case 'get_corte_details': if (isset($_GET['id'])) getCorteDetails($conn, $_GET['id']); break;
    case 'get_corte_image': if (isset($_GET['id'])) getCorteImage($conn, $_GET['id']); break;

    // --- Equipo Routes ---
    case 'get_equipos': getEquipos($conn); break;
    case 'add_equipo': addEquipo($conn, $_POST, $_FILES); break;
    case 'edit_equipo': editEquipo($conn, $_POST, $_FILES); break;
    case 'delete_equipo': deleteEquipo($conn, $data); break;
    case 'get_equipo_details': if (isset($_GET['id'])) getEquipoDetails($conn, $_GET['id']); break;
    case 'get_equipo_image': if (isset($_GET['id'])) getEquipoImage($conn, $_GET['id']); break;
    case 'unassign_equipo_from_auto': unassignEquipoFromAuto($conn, $data); break;
    case 'update_equipo_note': updateEquipoNote($conn, $data); break;
    case 'assign_equipo_to_multiple_autos': assignEquipoToMultipleAutos($conn, $data); break;
    case 'get_all_vehicles_for_equipo_assignment': if (isset($_GET['equipo_id'])) getAllVehiclesForEquipoAssignment($conn, $_GET['equipo_id']); break;

    // --- Rutas de Trabajos ---
    case 'get_trabajos': getTrabajos($conn); break;
    case 'add_trabajo': addTrabajo($conn, $_POST, $_FILES); break;
    case 'edit_trabajo': editTrabajo($conn, $_POST, $_FILES); break;
    case 'delete_trabajo': deleteTrabajo($conn, $data); break;
    case 'get_trabajo_details': if (isset($_GET['id'])) getTrabajoDetails($conn, $_GET['id']); break;
    case 'get_trabajo_image': if (isset($_GET['id'])) getTrabajoImage($conn, $_GET['id']); break;
    case 'get_all_cortes': getAllCortes($conn); break; // NEW: Route for the trabajo modal dropdown

    // --- Rutas de Clientes ---
    case 'get_clientes': getClientes($conn); break;
    case 'add_cliente': addCliente($conn, $data); break;
    case 'edit_cliente': editCliente($conn, $data); break;
    case 'delete_cliente': deleteCliente($conn, $data); break;
    case 'get_cliente_details': if (isset($_GET['id'])) getClienteDetails($conn, $_GET['id']); break;

    // --- Rutas de Tipos de Trabajo ---
    case 'get_tipos_trabajo': getTiposTrabajo($conn); break;
    case 'add_tipo_trabajo': addTipoTrabajo($conn, $data); break;
    case 'edit_tipo_trabajo': editTipoTrabajo($conn, $data); break;
    case 'delete_tipo_trabajo': deleteTipoTrabajo($conn, $data); break;

    // --- Global Search Route ---
    case 'global_search': globalSearch($conn); break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}

$conn->close();
?>
