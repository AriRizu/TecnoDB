<?php
// ========== Archivo: api/handlers/equipo_handler.php ==========

function getEquipos($conn) {
    // Return a flat array like the other handlers, not grouped
    $sql = "SELECT e.id, e.nombre, e.descripcion, 
                   (e.imagen IS NOT NULL AND LENGTH(e.imagen) > 0) as has_image, 
                   COUNT(ae.auto_id) as usage_count 
            FROM equipos e
            LEFT JOIN auto_equipos ae ON e.id = ae.equipo_id
            GROUP BY e.id, e.nombre, e.descripcion
            ORDER BY e.nombre ASC";
    $result = $conn->query($sql);

    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
        return;
    }

    // Return a simple flat array, not grouped
    $equipos = [];
    while($row = $result->fetch_assoc()) {
        $equipos[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'has_image' => (bool)$row['has_image'],
            'usage_count' => (int)$row['usage_count']
        ];
    }

    echo json_encode($equipos);
}

function addEquipo($conn, $post, $files) {
    $conn->begin_transaction();
    try {
        $stmt_equipo = $conn->prepare("INSERT INTO equipos (nombre, descripcion) VALUES (?, ?)");
        $stmt_equipo->bind_param("ss", $post['nombre'], $post['descripcion']);
        $stmt_equipo->execute();
        $equipoId = $conn->insert_id;
        $stmt_equipo->close();

        if (isset($files['imagen']) && $files['imagen']['error'] == UPLOAD_ERR_OK) {
            $stmt_img = $conn->prepare("UPDATE equipos SET imagen = ?, imagen_mime = ? WHERE id = ?");
            $content = file_get_contents($files['imagen']['tmp_name']);
            $mime = $files['imagen']['type'];
            $null = NULL;
            $stmt_img->bind_param("bsi", $null, $mime, $equipoId);
            $stmt_img->send_long_data(0, $content);
            $stmt_img->execute();
            $stmt_img->close();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'id' => $equipoId]);
    } catch (Exception $e) {
        if ($conn->ping()) { $conn->rollback(); }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function editEquipo($conn, $post, $files) {
    $conn->begin_transaction();
    try {
        $stmt_equipo = $conn->prepare("UPDATE equipos SET nombre = ?, descripcion = ? WHERE id = ?");
        $stmt_equipo->bind_param("ssi", $post['nombre'], $post['descripcion'], $post['id']);
        $stmt_equipo->execute();
        $stmt_equipo->close();

        if (isset($files['imagen']) && $files['imagen']['error'] == UPLOAD_ERR_OK) {
            $stmt_img = $conn->prepare("UPDATE equipos SET imagen = ?, imagen_mime = ? WHERE id = ?");
            $content = file_get_contents($files['imagen']['tmp_name']);
            $mime = $files['imagen']['type'];
            $null = NULL;
            $stmt_img->bind_param("bsi", $null, $mime, $post['id']);
            $stmt_img->send_long_data(0, $content);
            $stmt_img->execute();
            $stmt_img->close();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'id' => $post['id']]);
    } catch (Exception $e) {
        if ($conn->ping()) { $conn->rollback(); }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}


function deleteEquipo($conn, $data) {
    $stmt = $conn->prepare("DELETE FROM equipos WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function getEquipoDetails($conn, $equipoId) {
    $equipo = null;
    $stmt = $conn->prepare("SELECT * FROM equipos WHERE id = ?");
    $stmt->bind_param("i", $equipoId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $equipo = $result->fetch_assoc();
        
        if ($equipo['imagen']) { $equipo['imagen'] = base64_encode($equipo['imagen']); }
        
        $sql_autos = "SELECT a.id, a.marca, a.modelo, a.anio_inicio, a.anio_fin, ae.notas 
                      FROM autos a 
                      JOIN auto_equipos ae ON a.id = ae.auto_id 
                      WHERE ae.equipo_id = ?";
        $stmt_autos = $conn->prepare($sql_autos);
        $stmt_autos->bind_param("i", $equipoId);
        $stmt_autos->execute();
        $result_autos = $stmt_autos->get_result();
        $autos = [];
        while($row = $result_autos->fetch_assoc()) {
            $autos[] = $row;
        }
        $equipo['autos'] = $autos;
        $stmt_autos->close();
    }
    $stmt->close();
    echo json_encode($equipo);
}

function getEquipoImage($conn, $equipoId) {
    $stmt = $conn->prepare("SELECT imagen, imagen_mime FROM equipos WHERE id = ?");
    $stmt->bind_param("i", $equipoId);
    $stmt->execute();
    $stmt->store_result();
    
    $image_data = null;
    $mime_type = null;
    
    $stmt->bind_result($image_data, $mime_type);
    $stmt->fetch();
    $stmt->close();

    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($image_data) {
        header("Content-Type: " . ($mime_type ?: 'application/octet-stream'));
        echo $image_data;
    } else {
        header("Content-Type: image/svg+xml");
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="#eee"/><text x="50" y="55" font-family="Arial" font-size="12" fill="#aaa" text-anchor="middle">No Image</text></svg>';
    }
    exit();
}

function updateEquipoNote($conn, $data) {
    $stmt = $conn->prepare("UPDATE auto_equipos SET notas = ? WHERE auto_id = ? AND equipo_id = ?");
    $stmt->bind_param("sii", $data['notas'], $data['auto_id'], $data['equipo_id']);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function unassignEquipoFromAuto($conn, $data) {
    $stmt = $conn->prepare("DELETE FROM auto_equipos WHERE auto_id = ? AND equipo_id = ?");
    $stmt->bind_param("ii", $data['auto_id'], $data['equipo_id']);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function getAllVehiclesForEquipoAssignment($conn, $equipoId) {
    $sql = "SELECT 
                a.id, a.marca, a.modelo, a.anio_inicio, a.anio_fin, a.spec1, a.spec2,
                (CASE WHEN ae.auto_id IS NOT NULL THEN 1 ELSE 0 END) as is_assigned
            FROM autos a
            LEFT JOIN auto_equipos ae ON a.id = ae.auto_id AND ae.equipo_id = ?
            ORDER BY a.marca, a.modelo, a.anio_inicio DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $equipoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicles = [];
    while($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
    $stmt->close();
    echo json_encode($vehicles);
}

function assignEquipoToMultipleAutos($conn, $data) {
    $equipoId = $data['equipo_id'] ?? null;
    $autoIds = $data['auto_ids'] ?? [];

    if ($equipoId === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Se requiere equipo_id.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // First, remove all existing assignments for this equipo
        $stmt_delete = $conn->prepare("DELETE FROM auto_equipos WHERE equipo_id = ?");
        $stmt_delete->bind_param("i", $equipoId);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        // Now, insert the new assignments
        if (!empty($autoIds) && is_array($autoIds)) {
            $stmt_insert = $conn->prepare("INSERT INTO auto_equipos (auto_id, equipo_id, notas) VALUES (?, ?, '')");
            foreach ($autoIds as $autoId) {
                if (!is_numeric($autoId)) continue;
                $stmt_insert->bind_param("ii", $autoId, $equipoId);
                $stmt_insert->execute();
            }
            $stmt_insert->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Asignaciones de equipo actualizadas.']);
    } catch (Exception $e) {
        if ($conn->ping()) { $conn->rollback(); }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'db_error', 'message' => $e->getMessage()]);
    }
}
?>

