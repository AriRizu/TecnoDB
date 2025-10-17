<?php
// ========== Archivo: api/handlers/search_handler.php ==========

/**
 * Performs a global search across multiple tables.
 *
 * @param mysqli $conn The database connection object.
 */
function globalSearch($conn) {
    $term = $_GET['term'] ?? '';

    // We require at least 2 characters to start searching to avoid overly broad queries.
    if (strlen($term) < 2) {
        echo json_encode([]);
        return;
    }

    $searchTerm = '%' . $conn->real_escape_string($term) . '%';
    $results = [];

    // This SQL query unites results from several tables.
    // Each subquery is structured to return a common format: id, name, type, and context.
    $sql = "
        (SELECT 
            id, 
            CONCAT(marca, ' ', modelo) as name, 
            'Auto' as type, 
            CONCAT('Año: ', anio_inicio, '-', anio_fin) as context 
        FROM autos 
        WHERE marca LIKE ? OR modelo LIKE ?)

        UNION ALL

        (SELECT 
            id, 
            nombre as name, 
            'Item' as type, 
            ubicacion as context 
        FROM items 
        WHERE nombre LIKE ? OR nombres_secundarios LIKE ?)

        UNION ALL

        (SELECT 
            id, 
            cliente_patente as name, 
            'Trabajo' as type, 
            SUBSTRING(detalle, 1, 50) as context -- Truncate long details
        FROM trabajos 
        WHERE cliente_patente LIKE ? OR detalle LIKE ?)

        UNION ALL

        (SELECT 
            id, 
            nombre as name, 
            'Cliente' as type, 
            CONCAT_WS(' | ', telefono, cvu) as context 
        FROM clientes 
        WHERE nombre LIKE ? OR telefono LIKE ? OR cvu LIKE ?)
        
        UNION ALL

        (SELECT
            id,
            nombre as name,
            'Equipo' as type,
            SUBSTRING(descripcion, 1, 50) as context -- Also search and truncate equipos
        FROM equipos
        WHERE nombre LIKE ?)

        LIMIT 20 -- Limit the number of results to keep it fast
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
        return;
    }

    // Bind parameters for each placeholder in the query
    $stmt->bind_param("ssssssssss", 
        $searchTerm, $searchTerm, 
        $searchTerm, $searchTerm, 
        $searchTerm, $searchTerm, 
        $searchTerm, $searchTerm, $searchTerm,
        $searchTerm
    );
    
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    $stmt->close();
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($results);
}
?>
