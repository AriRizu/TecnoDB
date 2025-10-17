<?php
// ========== Archivo: api/handlers/item_handler.php ==========

// Get all items with their tags and cortes
function getItems($conn) {
    $sql = "
        SELECT 
            i.id, i.nombre, i.nombres_secundarios, i.ubicacion, i.descripcion, i.stock, i.stock_threshold,
            (i.imagen IS NOT NULL AND LENGTH(i.imagen) > 0) as has_image,
            GROUP_CONCAT(DISTINCT t.nombre SEPARATOR ', ') as tags,
            GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as cortes
        FROM items i
        LEFT JOIN item_tags it ON i.id = it.item_id
        LEFT JOIN tags t ON it.tag_id = t.id
        LEFT JOIN item_cortes ic ON i.id = ic.item_id
        LEFT JOIN cortes c ON ic.corte_id = c.id
        GROUP BY i.id
        ORDER BY i.nombre
    ";
    $result = $conn->query($sql);
    $items = [];
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
}

// Add a new item, including images and stock threshold
function addItem($conn, $post, $files) {
    $conn->begin_transaction();
    try {
        // Step 1: Insert item with text data.
        $stmt_item = $conn->prepare("INSERT INTO items (nombre, nombres_secundarios, ubicacion, descripcion, stock, stock_threshold) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_item->bind_param("ssssii", 
            $post['nombre'],
            $post['nombres_secundarios'], 
            $post['ubicacion'], 
            $post['descripcion'],
            $post['stock'],
            $post['stock_threshold']
        );
        $stmt_item->execute();
        $itemId = $conn->insert_id;
        $stmt_item->close();

        // Step 2: If there's a main image, update the record.
        if (isset($files['imagen']) && $files['imagen']['error'] == UPLOAD_ERR_OK) {
            $stmt_img = $conn->prepare("UPDATE items SET imagen = ?, imagen_mime = ? WHERE id = ?");
            $content = file_get_contents($files['imagen']['tmp_name']);
            $mime = $files['imagen']['type'];
            $null = NULL;
            $stmt_img->bind_param("bsi", $null, $mime, $itemId);
            $stmt_img->send_long_data(0, $content);
            $stmt_img->execute();
            $stmt_img->close();
        }

        // Step 3: If there's a detail image, update the record.
        if (isset($files['imagen_detalle']) && $files['imagen_detalle']['error'] == UPLOAD_ERR_OK) {
            $stmt_det = $conn->prepare("UPDATE items SET imagen_detalle = ?, imagen_detalle_mime = ? WHERE id = ?");
            $content_det = file_get_contents($files['imagen_detalle']['tmp_name']);
            $mime_det = $files['imagen_detalle']['type'];
            $null_det = NULL;
            $stmt_det->bind_param("bsi", $null_det, $mime_det, $itemId);
            $stmt_det->send_long_data(0, $content_det);
            $stmt_det->execute();
            $stmt_det->close();
        }

        // Step 4: Process tags and cortes.
        processTags($conn, $itemId, $post['tags'] ?? '');
        processCortes($conn, $itemId, $post['corte'] ?? '');
        
        $conn->commit();
        echo json_encode(['success' => true, 'id' => $itemId]);

    } catch (mysqli_sql_exception $exception) {
        http_response_code(500);
        $errorMessage = $exception->getMessage();

        if (strpos(strtolower($errorMessage), 'went away') !== false || strpos(strtolower($errorMessage), 'max_allowed_packet') !== false) {
            echo json_encode(['success' => false, 'error' => 'Image file is too large. Increase `max_allowed_packet` in your MySQL server configuration.']);
            exit();
        } else {
            if ($conn->ping()) { $conn->rollback(); }
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit();
        }
    }
}

// Edit an existing item, including image updates and stock threshold
function editItem($conn, $post, $files) {
    $conn->begin_transaction();
    try {
        $stmt_item = $conn->prepare("UPDATE items SET nombre = ?, nombres_secundarios = ?, ubicacion = ?, descripcion = ?, stock = ?, stock_threshold = ? WHERE id = ?");
        $stmt_item->bind_param("ssssiii", $post['nombre'], $post['nombres_secundarios'], $post['ubicacion'], $post['descripcion'], $post['stock'], $post['stock_threshold'], $post['id']);
        $stmt_item->execute();
        $stmt_item->close();

        if (isset($files['imagen']) && $files['imagen']['error'] == UPLOAD_ERR_OK) {
            $stmt_img = $conn->prepare("UPDATE items SET imagen = ?, imagen_mime = ? WHERE id = ?");
            $content = file_get_contents($files['imagen']['tmp_name']);
            $mime = $files['imagen']['type'];
            $null = NULL;
            $stmt_img->bind_param("bsi", $null, $mime, $post['id']);
            $stmt_img->send_long_data(0, $content);
            $stmt_img->execute();
            $stmt_img->close();
        }

        if (isset($files['imagen_detalle']) && $files['imagen_detalle']['error'] == UPLOAD_ERR_OK) {
            $stmt_det = $conn->prepare("UPDATE items SET imagen_detalle = ?, imagen_detalle_mime = ? WHERE id = ?");
            $content_det = file_get_contents($files['imagen_detalle']['tmp_name']);
            $mime_det = $files['imagen_detalle']['type'];
            $null_det = NULL;
            $stmt_det->bind_param("bsi", $null_det, $mime_det, $post['id']);
            $stmt_det->send_long_data(0, $content_det);
            $stmt_det->execute();
            $stmt_det->close();
        }
        
        processTags($conn, $post['id'], $post['tags'] ?? '');
        processCortes($conn, $post['id'], $post['corte'] ?? '');
        
        $conn->commit();
        echo json_encode(['success' => true, 'id' => $post['id']]);
    } catch (mysqli_sql_exception $exception) {
        http_response_code(500);
        $errorMessage = $exception->getMessage();
        if (strpos(strtolower($errorMessage), 'went away') !== false || strpos(strtolower($errorMessage), 'max_allowed_packet') !== false) {
            echo json_encode(['success' => false, 'error' => 'Image file is too large. Increase `max_allowed_packet` in your MySQL server configuration.']);
            exit();
        } else {
            if ($conn->ping()) { $conn->rollback(); }
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit();
        }
    }
}

// Get details of a single item
function getItemDetails($conn, $itemId) {
    $item = null;
    $stmt = $conn->prepare("
        SELECT 
            i.*, 
            GROUP_CONCAT(DISTINCT t.nombre SEPARATOR ', ') as tags,
            GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as cortes
        FROM items i 
        LEFT JOIN item_tags it ON i.id = it.item_id 
        LEFT JOIN tags t ON it.tag_id = t.id 
        LEFT JOIN item_cortes ic ON i.id = ic.item_id
        LEFT JOIN cortes c ON ic.corte_id = c.id
        WHERE i.id = ? 
        GROUP BY i.id
    ");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        
        if ($item['imagen']) { $item['imagen'] = base64_encode($item['imagen']); }
        if ($item['imagen_detalle']) { $item['imagen_detalle'] = base64_encode($item['imagen_detalle']); }

        $sql_autos = "SELECT a.* FROM autos a JOIN auto_items ai ON a.id = ai.auto_id WHERE ai.item_id = ?";
        $stmt_autos = $conn->prepare($sql_autos);
        $stmt_autos->bind_param("i", $itemId);
        $stmt_autos->execute();
        $result_autos = $stmt_autos->get_result();
        $autos = [];
        while($row = $result_autos->fetch_assoc()) { $autos[] = $row; }
        $item['autos'] = $autos;
        $stmt_autos->close();
    }
    $stmt->close();
    echo json_encode($item);
}

// --- Tag Functions ---
function getTagsWithUsage($conn) {
    $sql = "SELECT t.id, t.nombre, COUNT(it.tag_id) as usage_count FROM tags t LEFT JOIN item_tags it ON t.id = it.tag_id GROUP BY t.id, t.nombre ORDER BY usage_count DESC, t.nombre ASC";
    $result = $conn->query($sql);
    $tags = [];
    while($row = $result->fetch_assoc()) { $tags[] = $row; }
    echo json_encode($tags);
}

function processTags($conn, $itemId, $tagsString) {
    $stmt_delete = $conn->prepare("DELETE FROM item_tags WHERE item_id = ?");
    $stmt_delete->bind_param("i", $itemId);
    $stmt_delete->execute();
    $stmt_delete->close();

    if (!empty($tagsString)) {
        $tags = array_unique(array_filter(array_map('trim', explode(',', $tagsString))));
        $stmt_tag = $conn->prepare("INSERT INTO tags (nombre) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $stmt_link = $conn->prepare("INSERT INTO item_tags (item_id, tag_id) VALUES (?, ?)");

        foreach ($tags as $tagName) {
            $stmt_tag->bind_param("s", $tagName);
            $stmt_tag->execute();
            $tagId = $conn->insert_id;
            $stmt_link->bind_param("ii", $itemId, $tagId);
            $stmt_link->execute();
        }
        $stmt_tag->close();
        $stmt_link->close();
    }
}

function editTag($conn, $data) {
    $stmt_check = $conn->prepare("SELECT id FROM tags WHERE nombre = ? AND id != ?");
    $stmt_check->bind_param("si", $data['nombre'], $data['id']);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) { echo json_encode(['success' => false, 'error' => 'duplicate', 'message' => 'Tag name already exists.']); return; }
    $stmt_check->close();
    $stmt = $conn->prepare("UPDATE tags SET nombre = ? WHERE id = ?");
    $stmt->bind_param("si", $data['nombre'], $data['id']);
    if($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

function addTag($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO tags (nombre) VALUES (?)");
    $stmt->bind_param("s", $data['nombre']);
    if ($stmt->execute()) { echo json_encode(['success' => true, 'id' => $conn->insert_id]); } 
    else {
        if ($conn->errno == 1062) { http_response_code(409); echo json_encode(['success' => false, 'error' => 'duplicate', 'message' => 'Tag already exists.']); } 
        else { http_response_code(500); echo json_encode(['success' => false, 'error' => $stmt->error]); }
    }
    $stmt->close();
}

function deleteTag($conn, $data) {
    $stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    if($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

function massDeleteTags($conn, $data) {
    $ids = $data['ids']; if (empty($ids) || !is_array($ids)) { echo json_encode(['success' => false, 'error' => 'No IDs provided']); return; }
    $placeholders = implode(',', array_fill(0, count($ids), '?')); $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM tags WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

// --- Corte Functions ---
function getCortesWithUsage($conn) {
    $sql = "SELECT c.id, c.nombre, c.bitting, (c.imagen IS NOT NULL AND LENGTH(c.imagen) > 0) as has_image, COUNT(ic.corte_id) as usage_count 
            FROM cortes c 
            LEFT JOIN item_cortes ic ON c.id = ic.corte_id 
            GROUP BY c.id, c.nombre, c.bitting 
            ORDER BY c.nombre ASC";
    $result = $conn->query($sql);
    $cortes = [];
    while($row = $result->fetch_assoc()) { $cortes[] = $row; }
    echo json_encode($cortes);
}

function getCorteDetails($conn, $corteId) {
    $stmt = $conn->prepare("SELECT * FROM cortes WHERE id = ?");
    $stmt->bind_param("i", $corteId);
    $stmt->execute();
    $result = $stmt->get_result();
    $corte = $result->fetch_assoc();
    if ($corte && $corte['imagen']) {
        $corte['imagen'] = base64_encode($corte['imagen']);
    }
    echo json_encode($corte);
    $stmt->close();
}

function processCortes($conn, $itemId, $corteString) {
    $stmt_delete = $conn->prepare("DELETE FROM item_cortes WHERE item_id = ?");
    $stmt_delete->bind_param("i", $itemId);
    $stmt_delete->execute();
    $stmt_delete->close();

    if (!empty($corteString)) {
        $corteName = trim($corteString);
        $stmt_corte = $conn->prepare("INSERT INTO cortes (nombre) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $stmt_link = $conn->prepare("INSERT INTO item_cortes (item_id, corte_id) VALUES (?, ?)");
        
        $stmt_corte->bind_param("s", $corteName);
        $stmt_corte->execute();
        $corteId = $conn->insert_id;
        
        $stmt_link->bind_param("ii", $itemId, $corteId);
        $stmt_link->execute();
        
        $stmt_corte->close();
        $stmt_link->close();
    }
}

function editCorte($conn, $post, $files) {
    $corteId = $post['id'];
    $stmt_check = $conn->prepare("SELECT id FROM cortes WHERE nombre = ? AND id != ?");
    $stmt_check->bind_param("si", $post['nombre'], $corteId);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'duplicate', 'message' => 'Corte name already exists.']);
        return;
    }
    $stmt_check->close();

    $stmt = $conn->prepare("UPDATE cortes SET nombre = ?, bitting = ? WHERE id = ?");
    $stmt->bind_param("ssi", $post['nombre'], $post['bitting'], $corteId);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        return;
    }
    $stmt->close();

    if (isset($files['imagen']) && $files['imagen']['error'] == UPLOAD_ERR_OK) {
        $stmt_img = $conn->prepare("UPDATE cortes SET imagen = ?, imagen_mime = ? WHERE id = ?");
        $content = file_get_contents($files['imagen']['tmp_name']);
        $mime = $files['imagen']['type'];
        $null = NULL;
        $stmt_img->bind_param("bsi", $null, $mime, $corteId);
        $stmt_img->send_long_data(0, $content);
        $stmt_img->execute();
        $stmt_img->close();
    }
    echo json_encode(['success' => true]);
}


function addCorte($conn, $post, $files) {
    $stmt = $conn->prepare("INSERT INTO cortes (nombre, bitting) VALUES (?, ?)");
    $stmt->bind_param("ss", $post['nombre'], $post['bitting']);
    
    if ($stmt->execute()) {
        $corteId = $conn->insert_id;
        if (isset($files['imagen']) && $files['imagen']['error'] == UPLOAD_ERR_OK) {
            $stmt_img = $conn->prepare("UPDATE cortes SET imagen = ?, imagen_mime = ? WHERE id = ?");
            $content = file_get_contents($files['imagen']['tmp_name']);
            $mime = $files['imagen']['type'];
            $null = NULL;
            $stmt_img->bind_param("bsi", $null, $mime, $corteId);
            $stmt_img->send_long_data(0, $content);
            $stmt_img->execute();
            $stmt_img->close();
        }
        echo json_encode(['success' => true, 'id' => $corteId]);
    } else {
        if ($conn->errno == 1062) { http_response_code(409); echo json_encode(['success' => false, 'error' => 'duplicate', 'message' => 'Corte already exists.']); } 
        else { http_response_code(500); echo json_encode(['success' => false, 'error' => $stmt->error]); }
    }
    $stmt->close();
}

function deleteCorte($conn, $data) {
    $stmt = $conn->prepare("DELETE FROM cortes WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    if($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

function massDeleteCortes($conn, $data) {
    $ids = $data['ids']; if (empty($ids) || !is_array($ids)) { echo json_encode(['success' => false, 'error' => 'No IDs provided']); return; }
    $placeholders = implode(',', array_fill(0, count($ids), '?')); $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM cortes WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

function getAutocompleteDataForCortes($conn) {
    $sql = "SELECT nombre FROM cortes ORDER BY nombre";
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row['nombre'];
    }
    echo json_encode($data);
}

// --- Other Functions ---
function getCorteImage($conn, $corteId) {
    $stmt = $conn->prepare("SELECT imagen, imagen_mime FROM cortes WHERE id = ?");
    $stmt->bind_param("i", $corteId);
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

function getItemImage($conn, $itemId, $type = 'main') {
    $column = $type === 'detail' ? 'imagen_detalle' : 'imagen';
    $mime_column = $type === 'detail' ? 'imagen_detalle_mime' : 'imagen_mime';

    $stmt = $conn->prepare("SELECT $column, $mime_column FROM items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
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


function deleteItem($conn, $data) {
    $id = $data['id']; $stmt = $conn->prepare("DELETE FROM items WHERE id = ?"); $stmt->bind_param("i", $id);
    if ($stmt->execute()) { echo json_encode(['success' => true]); } else { echo json_encode(['success' => false, 'error' => $stmt->error]); }
    $stmt->close();
}

function massDeleteItems($conn, $data) {
    $ids = $data['ids']; if (empty($ids) || !is_array($ids)) { echo json_encode(['success' => false, 'error' => 'No IDs provided']); return; }
    $placeholders = implode(',', array_fill(0, count($ids), '?')); $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM items WHERE id IN ($placeholders)"); $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) { echo json_encode(['success' => true]); } else { echo json_encode(['success' => false, 'error' => $stmt->error]); }
    $stmt->close();
}

function updateItemStock($conn, $data) {
    $itemId = $data['item_id']; $change = $data['change'];
    $stmt = $conn->prepare("UPDATE items SET stock = stock + ? WHERE id = ? AND stock + ? >= 0"); $stmt->bind_param("iii", $change, $itemId, $change);
    if ($stmt->execute()) {
        $stmt_get_stock = $conn->prepare("SELECT stock FROM items WHERE id = ?"); $stmt_get_stock->bind_param("i", $itemId);
        $stmt_get_stock->execute(); $result = $stmt_get_stock->get_result(); $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'new_stock' => $row['stock']]); $stmt_get_stock->close();
    } else { echo json_encode(['success' => false, 'error' => $stmt->error]); }
    $stmt->close();
}

function copyItem($conn, $data) {
    $originalId = $data['id'] ?? 0;
    if ($originalId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid ID provided']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt_get = $conn->prepare("SELECT * FROM items WHERE id = ?");
        $stmt_get->bind_param("i", $originalId);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Item not found.");
        }
        $originalItem = $result->fetch_assoc();
        $stmt_get->close();

        $newName = "Copia de " . $originalItem['nombre'];
        
        $stmt_insert = $conn->prepare("
            INSERT INTO items (
                nombre, nombres_secundarios, stock, stock_threshold, imagen, imagen_mime, 
                ubicacion, imagen_detalle, imagen_detalle_mime, descripcion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $null = NULL;
        $stmt_insert->bind_param(
            "ssiisbsbss",
            $newName,
            $originalItem['nombres_secundarios'],
            $originalItem['stock'],
            $originalItem['stock_threshold'],
            $null, // for imagen blob
            $originalItem['imagen_mime'],
            $originalItem['ubicacion'],
            $null, // for imagen_detalle blob
            $originalItem['imagen_detalle_mime'],
            $originalItem['descripcion']
        );
        
        if (!empty($originalItem['imagen'])) {
            $stmt_insert->send_long_data(4, $originalItem['imagen']);
        }
        
        if (!empty($originalItem['imagen_detalle'])) {
            $stmt_insert->send_long_data(7, $originalItem['imagen_detalle']);
        }

        $stmt_insert->execute();
        $newItemId = $conn->insert_id;
        $stmt_insert->close();

        $stmt_copy_tags = $conn->prepare("INSERT INTO item_tags (item_id, tag_id) SELECT ?, tag_id FROM item_tags WHERE item_id = ?");
        $stmt_copy_tags->bind_param("ii", $newItemId, $originalId);
        $stmt_copy_tags->execute();
        $stmt_copy_tags->close();
        
        $stmt_copy_cortes = $conn->prepare("INSERT INTO item_cortes (item_id, corte_id) SELECT ?, corte_id FROM item_cortes WHERE item_id = ?");
        $stmt_copy_cortes->bind_param("ii", $newItemId, $originalId);
        $stmt_copy_cortes->execute();
        $stmt_copy_cortes->close();

        $conn->commit();
        echo json_encode(['success' => true, 'id' => $newItemId]);

    } catch (Exception $e) {
        if ($conn->ping()) {
            $conn->rollback();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function assignVehiclesToItem($conn, $data) {
    $itemId = $data['item_id'] ?? null; $autoIds = $data['auto_ids'] ?? [];
    if ($itemId === null) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'item_id is required.']); return; }
    $conn->begin_transaction();
    try {
        $stmt_delete = $conn->prepare("DELETE FROM auto_items WHERE item_id = ?"); $stmt_delete->bind_param("i", $itemId); $stmt_delete->execute(); $stmt_delete->close();
        if (!empty($autoIds) && is_array($autoIds)) {
            $stmt_insert = $conn->prepare("INSERT INTO auto_items (auto_id, item_id) VALUES (?, ?)");
            foreach ($autoIds as $autoId) { if (!is_numeric($autoId)) continue; $stmt_insert->bind_param("ii", $autoId, $itemId); $stmt_insert->execute(); }
            $stmt_insert->close();
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Vehicle associations updated successfully.']);
    } catch (Exception $e) {
        $conn->rollback(); http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'db_error', 'message' => $e->getMessage()]);
    }
}
?>
