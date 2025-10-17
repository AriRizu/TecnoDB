<?php
// ========== Archivo: api/handlers/auto_handler.php ==========

/**
 * Gets all vehicles, processed into a nested structure grouped by brand.
 */
function getGroupedAutos($conn) {
    $sql = "
        SELECT
            marca,
            modelo,
            GROUP_CONCAT(
                CONCAT(
                    id, '::',
                    IFNULL(anio_inicio, ''), '::',
                    IFNULL(anio_fin, ''), '::',
                    IFNULL(spec1, ''), '::',
                    IFNULL(spec2, '')
                )
                ORDER BY anio_inicio DESC
                SEPARATOR '||'
            ) as versions
        FROM
            autos
        GROUP BY
            marca,
            modelo
        ORDER BY
            marca ASC,
            modelo ASC
    ";

    $result = $conn->query($sql);
    $brands = [];

    while($row = $result->fetch_assoc()) {
        $versions_str = $row['versions'];
        $versions = [];

        if (!empty($versions_str)) {
            $versions_arr = explode('||', $versions_str);
            foreach ($versions_arr as $version_str) {
                $parts = explode('::', $version_str, 5);
                $versions[] = [
                    'id' => $parts[0] ?? null,
                    'anio_inicio' => ($parts[1] ?? '') === '' ? null : $parts[1],
                    'anio_fin'    => ($parts[2] ?? '') === '' ? null : $parts[2],
                    'spec1'       => ($parts[3] ?? '') === '' ? null : $parts[3],
                    'spec2'       => ($parts[4] ?? '') === '' ? null : $parts[4]
                ];
            }
        }
        
        if (!isset($brands[$row['marca']])) {
            $brands[$row['marca']] = [
                'marca' => $row['marca'],
                'modelos' => []
            ];
        }

        $brands[$row['marca']]['modelos'][] = [
            'modelo' => $row['modelo'],
            'versions' => $versions
        ];
    }
    echo json_encode(array_values($brands));
}

function getAutoDetails($conn, $autoId) {
    $auto = null;
    $stmt = $conn->prepare("SELECT * FROM autos WHERE id = ?");
    $stmt->bind_param("i", $autoId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $auto = $result->fetch_assoc();
        
        // Fetch associated items
        $stmt_items = $conn->prepare("SELECT i.* FROM items i JOIN auto_items ai ON i.id = ai.item_id WHERE ai.auto_id = ?");
        $stmt_items->bind_param("i", $autoId);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        $items = [];
        while($row = $result_items->fetch_assoc()) {
            $items[] = $row;
        }
        $auto['items'] = $items;
        $stmt_items->close();

        // Fetch associated equipos (NEW)
        $stmt_equipos = $conn->prepare("
            SELECT e.*, ae.notas 
            FROM equipos e 
            JOIN auto_equipos ae ON e.id = ae.equipo_id 
            WHERE ae.auto_id = ?
        ");
        $stmt_equipos->bind_param("i", $autoId);
        $stmt_equipos->execute();
        $result_equipos = $stmt_equipos->get_result();
        $equipos = [];
        while($row = $result_equipos->fetch_assoc()) {
            $equipos[] = $row;
        }
        $auto['equipos'] = $equipos;
        $stmt_equipos->close();
    }
    $stmt->close();
    echo json_encode($auto);
}

function addMultipleAutos($conn, $data) {
    $marca = $data['marca'];
    $modelo = $data['modelo'];
    $ranges = $data['ranges'];
    $force_insert = $data['force'] ?? false;

    if (empty($ranges) || !is_array($ranges)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No year ranges provided.']);
        return;
    }

    $conflicting_ranges_messages = [];
    if (!$force_insert) {
        $stmt_check = $conn->prepare("SELECT anio_inicio, anio_fin FROM autos WHERE marca = ? AND modelo = ?");
        $stmt_check->bind_param("ss", $marca, $modelo);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $db_existing_ranges = [];
        while ($row = $result->fetch_assoc()) {
            $db_existing_ranges[] = $row;
        }
        $stmt_check->close();

        foreach ($ranges as $range) {
            $new_start = (int)$range['anio_inicio'];
            $new_end = (int)$range['anio_fin'];
            if ($new_start > 0) {
                foreach ($db_existing_ranges as $existing_range) {
                    $old_start = (int)$existing_range['anio_inicio'];
                    if ($old_start > 0 && ($new_start <= (int)$existing_range['anio_fin']) && ($new_end >= $old_start)) {
                        $conflicting_ranges_messages[] = "The range ($new_start-$new_end) overlaps with an existing vehicle ($old_start-{$existing_range['anio_fin']}).";
                        break;
                    }
                }
            }
        }
    }

    if (!empty($conflicting_ranges_messages) && !$force_insert) {
        http_response_code(409); // Conflict
        echo json_encode([
            'success' => false,
            'error'   => 'overlap_warning',
            'message' => 'Some year ranges overlap with existing vehicles. Do you want to save them anyway?',
            'conflicts' => $conflicting_ranges_messages
        ]);
        return;
    }
    
    $conn->begin_transaction();
    try {
        $stmt_insert = $conn->prepare("INSERT INTO autos (marca, modelo, anio_inicio, anio_fin, spec1, spec2) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($ranges as $range) {
            $start = (int)$range['anio_inicio'];
            $end = (int)$range['anio_fin'];
            $spec1 = $range['spec1'] ?? null;
            $spec2 = $range['spec2'] ?? null;
            $stmt_insert->bind_param("ssiiss", $marca, $modelo, $start, $end, $spec1, $spec2);
            if (!$stmt_insert->execute()) {
                throw new Exception("Database error inserting range $start-$end: " . $stmt_insert->error);
            }
        }
        $stmt_insert->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => count($ranges) . ' vehicle(s) added successfully.']);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'db_error', 'message' => $e->getMessage()]);
    }
}


function editAuto($conn, $data) {
    $id = $data['id'];
    $marca = $data['marca'];
    $modelo = $data['modelo'];
    $new_start = (int)$data['anio_inicio'];
    $new_end = (int)$data['anio_fin'];

    $stmt = $conn->prepare("UPDATE autos SET marca = ?, modelo = ?, anio_inicio = ?, anio_fin = ?, spec1 = ?, spec2 = ? WHERE id = ?");
    $stmt->bind_param("ssiissi", $marca, $modelo, $new_start, $new_end, $data['spec1'], $data['spec2'], $id);
    if ($stmt->execute()) echo json_encode(['success' => true, 'id' => $id]);
    else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

function editAutoGroup($conn, $data) {
    $oldMarca = $data['old_marca'] ?? '';
    $oldModelo = $data['old_modelo'] ?? '';
    $newMarca = $data['new_marca'] ?? '';
    $newModelo = $data['new_modelo'] ?? '';

    if (empty($oldMarca) || empty($oldModelo) || empty($newMarca) || empty($newModelo)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Old and new Brand and Model are required.']);
        return;
    }

    if ($oldMarca !== $newMarca || $oldModelo !== $newModelo) {
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM autos WHERE marca = ? AND modelo = ?");
        $stmt_check->bind_param("ss", $newMarca, $newModelo);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row = $result->fetch_row();
        if ($row[0] > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'The new Brand and Model combination already exists as a separate group.']);
            return;
        }
        $stmt_check->close();
    }

    $stmt = $conn->prepare("UPDATE autos SET marca = ?, modelo = ? WHERE marca = ? AND modelo = ?");
    $stmt->bind_param("ssss", $newMarca, $newModelo, $oldMarca, $oldModelo);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vehicle group updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error, 'message' => 'Error updating vehicle group.']);
    }
    $stmt->close();
}

function deleteAutoGroup($conn, $data) {
    $marca = $data['marca'] ?? '';
    $modelo = $data['modelo'] ?? '';

    if (empty($marca) || empty($modelo)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Brand and Model are required to delete the group.']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM autos WHERE marca = ? AND modelo = ?");
    $stmt->bind_param("ss", $marca, $modelo);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vehicle group deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error, 'message' => 'Error deleting vehicle group.']);
    }
    $stmt->close();
}

function assignItemToMultipleAutos($conn, $data) {
    $itemId = $data['item_id'] ?? null;
    $autoIds = $data['auto_ids'] ?? [];

    if ($itemId === null || empty($autoIds) || !is_array($autoIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'item_id and an array of auto_ids are required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT IGNORE INTO auto_items (auto_id, item_id) VALUES (?, ?)");
        
        foreach ($autoIds as $autoId) {
            if (!is_numeric($autoId)) continue; 
            $stmt->bind_param("ii", $autoId, $itemId);
            if (!$stmt->execute()) {
                throw new Exception("Database error assigning item to vehicle ID $autoId: " . $stmt->error);
            }
        }
        $stmt->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item assigned to ' . count($autoIds) . ' vehicle(s) successfully.']);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'db_error', 'message' => $e->getMessage()]);
    }
}

function unassignItemFromAuto($conn, $data) {
    $stmt = $conn->prepare("DELETE FROM auto_items WHERE auto_id = ? AND item_id = ?");
    $stmt->bind_param("ii", $data['auto_id'], $data['item_id']);
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

function getAutocompleteDataForAutos($conn, $field) {
    $allowed_fields = ['marca', 'modelo', 'spec1', 'spec2'];
    if (!in_array($field, $allowed_fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid field for autocomplete']);
        return;
    }
    $sql = "SELECT DISTINCT $field FROM autos WHERE $field IS NOT NULL AND $field != '' ORDER BY $field";
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row[$field];
    }
    echo json_encode($data);
}

function deleteAuto($conn, $data) {
    $id = $data['id'];
    $stmt = $conn->prepare("DELETE FROM autos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

function massDeleteAutos($conn, $data) {
    $ids = $data['ids'];
    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['success' => false, 'error' => 'No IDs provided']);
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM autos WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}
?>
