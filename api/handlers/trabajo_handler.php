<?php
// ========== Archivo: api/handlers/trabajo_handler.php ==========

// --- Tipos de Trabajo ---
function getTiposTrabajo($conn) {
    $sql = "SELECT tt.id, tt.nombre, COUNT(t.id) as usage_count 
            FROM tipos_trabajo tt 
            LEFT JOIN trabajos t ON tt.id = t.tipo_trabajo_id 
            GROUP BY tt.id, tt.nombre 
            ORDER BY tt.nombre ASC";
    $result = $conn->query($sql);
    $tipos = [];
    while($row = $result->fetch_assoc()) { $tipos[] = $row; }
    echo json_encode($tipos);
}

function addTipoTrabajo($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO tipos_trabajo (nombre) VALUES (?)");
    $stmt->bind_param("s", $data['nombre']);
    if ($stmt->execute()) { echo json_encode(['success' => true, 'id' => $conn->insert_id]); } 
    else {
        if ($conn->errno == 1062) { http_response_code(409); echo json_encode(['success' => false, 'error' => 'duplicate', 'message' => 'Ese tipo de trabajo ya existe.']); } 
        else { http_response_code(500); echo json_encode(['success' => false, 'error' => $stmt->error]); }
    }
    $stmt->close();
}

function editTipoTrabajo($conn, $data) {
    $stmt_check = $conn->prepare("SELECT id FROM tipos_trabajo WHERE nombre = ? AND id != ?");
    $stmt_check->bind_param("si", $data['nombre'], $data['id']);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) { echo json_encode(['success' => false, 'error' => 'duplicate', 'message' => 'Ese tipo de trabajo ya existe.']); return; }
    $stmt_check->close();
    $stmt = $conn->prepare("UPDATE tipos_trabajo SET nombre = ? WHERE id = ?");
    $stmt->bind_param("si", $data['nombre'], $data['id']);
    if($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

function deleteTipoTrabajo($conn, $data) {
    $stmt = $conn->prepare("DELETE FROM tipos_trabajo WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    if($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
}

// --- Trabajos ---
function getTrabajos($conn) {
    $sql = "SELECT 
                tr.id, tr.net_profit, tr.gastos, tr.cliente_patente, tr.fecha_creacion, tr.is_paid,
                tr.cliente_corte,
                tt.nombre as tipo_trabajo_nombre,
                c.nombre as cliente_nombre,
                co.nombre as tipo_corte_nombre,
                CONCAT(a.marca, ' ', a.modelo) as auto_nombre
            FROM trabajos tr
            LEFT JOIN tipos_trabajo tt ON tr.tipo_trabajo_id = tt.id
            LEFT JOIN autos a ON tr.auto_id = a.id
            LEFT JOIN clientes c ON tr.cliente_id = c.id
            LEFT JOIN cortes co ON tr.corte_id = co.id
            ORDER BY tr.fecha_creacion DESC";
    
    $trabajos_result = $conn->query($sql);
    $trabajos = [];
    while($row = $trabajos_result->fetch_assoc()) {
        $trabajos[] = $row;
    }

    // Calcular estadísticas solo de trabajos pagados
    $stats_sql = "SELECT 
                    SUM(CASE WHEN MONTH(fecha_creacion) = MONTH(CURDATE()) AND YEAR(fecha_creacion) = YEAR(CURDATE()) THEN net_profit ELSE 0 END) as profit_mes_actual,
                    SUM(CASE WHEN MONTH(fecha_creacion) = MONTH(CURDATE()) AND YEAR(fecha_creacion) = YEAR(CURDATE()) THEN gastos ELSE 0 END) as gastos_mes_actual,
                    SUM(net_profit) as profit_total,
                    SUM(gastos) as gastos_total
                  FROM trabajos
                  WHERE is_paid = 1";
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

    echo json_encode(['trabajos' => $trabajos, 'stats' => $stats]);
}

function getTrabajoDetails($conn, $trabajoId) {
    $stmt = $conn->prepare("
        SELECT tr.*, co.nombre as tipo_corte_nombre 
        FROM trabajos tr
        LEFT JOIN cortes co ON tr.corte_id = co.id
        WHERE tr.id = ?
    ");
    $stmt->bind_param("i", $trabajoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $trabajo = $result->fetch_assoc();
    $stmt->close();

    if ($trabajo) {
        if ($trabajo['imagen']) { $trabajo['imagen'] = base64_encode($trabajo['imagen']); }

        // Get items
        $stmt_items = $conn->prepare("SELECT ti.item_id, i.nombre, i.stock, ti.cantidad_usada FROM items i JOIN trabajo_items ti ON i.id = ti.item_id WHERE ti.trabajo_id = ?");
        $stmt_items->bind_param("i", $trabajoId);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        $trabajo['items'] = [];
        while($row = $items_result->fetch_assoc()) { $trabajo['items'][] = $row; }
        $stmt_items->close();

        // Get equipos
        $stmt_equipos = $conn->prepare("SELECT e.id, e.nombre FROM equipos e JOIN trabajo_equipos te ON e.id = te.equipo_id WHERE te.trabajo_id = ?");
        $stmt_equipos->bind_param("i", $trabajoId);
        $stmt_equipos->execute();
        $equipos_result = $stmt_equipos->get_result();
        $trabajo['equipos'] = [];
        while($row = $equipos_result->fetch_assoc()) { $trabajo['equipos'][] = $row; }
        $stmt_equipos->close();
    }

    echo json_encode($trabajo);
}

function addTrabajo($conn, $post, $files) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO trabajos (net_profit, gastos, tipo_trabajo_id, cliente_patente, cliente_corte, cliente_pincode, corte_id, auto_id, cliente_id, detalle, notas, is_paid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $auto_id = empty($post['auto_id']) ? null : $post['auto_id'];
        $cliente_id = empty($post['cliente_id']) ? null : $post['cliente_id'];
        $tipo_trabajo_id = empty($post['tipo_trabajo_id']) ? null : $post['tipo_trabajo_id'];
        $corte_id = empty($post['corte_id']) ? null : $post['corte_id'];
        $is_paid = isset($post['is_paid']) ? (int)$post['is_paid'] : 1;
        
        $stmt->bind_param("ddisssiiissi", 
            $post['net_profit'], $post['gastos'], $tipo_trabajo_id, 
            $post['cliente_patente'], $post['cliente_corte'], $post['cliente_pincode'], 
            $corte_id, $auto_id, $cliente_id, 
            $post['detalle'], $post['notas'], $is_paid
        );
        $stmt->execute();
        $trabajoId = $conn->insert_id;
        $stmt->close();

        if (isset($files['imagen']) && $files['imagen']['error'] == UPLOAD_ERR_OK) {
            $stmt_img = $conn->prepare("UPDATE trabajos SET imagen = ?, imagen_mime = ? WHERE id = ?");
            $content = file_get_contents($files['imagen']['tmp_name']);
            $mime = $files['imagen']['type'];
            $null = NULL;
            $stmt_img->bind_param("bsi", $null, $mime, $trabajoId);
            $stmt_img->send_long_data(0, $content);
            $stmt_img->execute();
            $stmt_img->close();
        }

        // Link items and decrement stock
        if (isset($post['items']) && is_array($post['items'])) {
            $stmt_link_item = $conn->prepare("INSERT INTO trabajo_items (trabajo_id, item_id, cantidad_usada) VALUES (?, ?, ?)");
            $stmt_update_stock = $conn->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
            foreach ($post['items'] as $item) {
                $stmt_link_item->bind_param("iii", $trabajoId, $item['id'], $item['cantidad']);
                $stmt_link_item->execute();
                $stmt_update_stock->bind_param("ii", $item['cantidad'], $item['id']);
                $stmt_update_stock->execute();
            }
            $stmt_link_item->close();
            $stmt_update_stock->close();
        }

        // Link equipos
        if (isset($post['equipos']) && is_array($post['equipos'])) {
            $stmt_link_equipo = $conn->prepare("INSERT INTO trabajo_equipos (trabajo_id, equipo_id) VALUES (?, ?)");
            foreach ($post['equipos'] as $equipoId) {
                $stmt_link_equipo->bind_param("ii", $trabajoId, $equipoId);
                $stmt_link_equipo->execute();
            }
            $stmt_link_equipo->close();
        }
        
        if ($auto_id) {
            if (isset($post['items']) && is_array($post['items'])) {
                $stmt_sync_item = $conn->prepare("INSERT IGNORE INTO auto_items (auto_id, item_id) VALUES (?, ?)");
                foreach ($post['items'] as $item) {
                    $stmt_sync_item->bind_param("ii", $auto_id, $item['id']);
                    $stmt_sync_item->execute();
                }
                $stmt_sync_item->close();
            }
            if (isset($post['equipos']) && is_array($post['equipos'])) {
                $stmt_sync_equipo = $conn->prepare("INSERT IGNORE INTO auto_equipos (auto_id, equipo_id) VALUES (?, ?)");
                foreach ($post['equipos'] as $equipoId) {
                    $stmt_sync_equipo->bind_param("ii", $auto_id, $equipoId);
                    $stmt_sync_equipo->execute();
                }
                $stmt_sync_equipo->close();
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'id' => $trabajoId]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function editTrabajo($conn, $post, $files) {
    $trabajoId = $post['id'];
    $conn->begin_transaction();
    try {
        // Restore stock from old items
        $stmt_get_old_items = $conn->prepare("SELECT item_id, cantidad_usada FROM trabajo_items WHERE trabajo_id = ?");
        $stmt_get_old_items->bind_param("i", $trabajoId);
        $stmt_get_old_items->execute();
        $old_items_result = $stmt_get_old_items->get_result();
        $stmt_restore_stock = $conn->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
        while($item = $old_items_result->fetch_assoc()) {
            $stmt_restore_stock->bind_param("ii", $item['cantidad_usada'], $item['item_id']);
            $stmt_restore_stock->execute();
        }
        $stmt_get_old_items->close();
        $stmt_restore_stock->close();

        $conn->query("DELETE FROM trabajo_items WHERE trabajo_id = $trabajoId");
        $conn->query("DELETE FROM trabajo_equipos WHERE trabajo_id = $trabajoId");

        // Update main trabajo record
        $stmt_update = $conn->prepare("UPDATE trabajos SET net_profit=?, gastos=?, tipo_trabajo_id=?, cliente_patente=?, cliente_corte=?, cliente_pincode=?, corte_id=?, auto_id=?, cliente_id=?, detalle=?, notas=?, is_paid=? WHERE id=?");
        
        $auto_id = empty($post['auto_id']) ? null : $post['auto_id'];
        $cliente_id = empty($post['cliente_id']) ? null : $post['cliente_id'];
        $tipo_trabajo_id = empty($post['tipo_trabajo_id']) ? null : $post['tipo_trabajo_id'];
        $corte_id = empty($post['corte_id']) ? null : $post['corte_id'];
        $is_paid = isset($post['is_paid']) ? (int)$post['is_paid'] : 1;
        
        $stmt_update->bind_param("ddisssiiissii", 
            $post['net_profit'], $post['gastos'], $tipo_trabajo_id, 
            $post['cliente_patente'], $post['cliente_corte'], $post['cliente_pincode'], 
            $corte_id, $auto_id, $cliente_id, 
            $post['detalle'], $post['notas'], $is_paid, $trabajoId
        );
        $stmt_update->execute();
        $stmt_update->close();

        if (isset($files['imagen']) && $files['imagen']['error'] == UPLOAD_ERR_OK) {
            $stmt_img = $conn->prepare("UPDATE trabajos SET imagen = ?, imagen_mime = ? WHERE id = ?");
            $content = file_get_contents($files['imagen']['tmp_name']);
            $mime = $files['imagen']['type'];
            $null = NULL;
            $stmt_img->bind_param("bsi", $null, $mime, $trabajoId);
            $stmt_img->send_long_data(0, $content);
            $stmt_img->execute();
            $stmt_img->close();
        }

        if (isset($post['items']) && is_array($post['items'])) {
            $stmt_link_item = $conn->prepare("INSERT INTO trabajo_items (trabajo_id, item_id, cantidad_usada) VALUES (?, ?, ?)");
            $stmt_update_stock = $conn->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
            foreach ($post['items'] as $item) {
                $stmt_link_item->bind_param("iii", $trabajoId, $item['id'], $item['cantidad']);
                $stmt_link_item->execute();
                $stmt_update_stock->bind_param("ii", $item['cantidad'], $item['id']);
                $stmt_update_stock->execute();
            }
            $stmt_link_item->close();
            $stmt_update_stock->close();
        }

        if (isset($post['equipos']) && is_array($post['equipos'])) {
            $stmt_link_equipo = $conn->prepare("INSERT INTO trabajo_equipos (trabajo_id, equipo_id) VALUES (?, ?)");
            foreach ($post['equipos'] as $equipoId) {
                $stmt_link_equipo->bind_param("ii", $trabajoId, $equipoId);
                $stmt_link_equipo->execute();
            }
            $stmt_link_equipo->close();
        }
        
        if ($auto_id) {
            if (isset($post['items']) && is_array($post['items'])) {
                $stmt_sync_item = $conn->prepare("INSERT IGNORE INTO auto_items (auto_id, item_id) VALUES (?, ?)");
                foreach ($post['items'] as $item) {
                    $stmt_sync_item->bind_param("ii", $auto_id, $item['id']);
                    $stmt_sync_item->execute();
                }
                $stmt_sync_item->close();
            }
            if (isset($post['equipos']) && is_array($post['equipos'])) {
                $stmt_sync_equipo = $conn->prepare("INSERT IGNORE INTO auto_equipos (auto_id, equipo_id) VALUES (?, ?)");
                foreach ($post['equipos'] as $equipoId) {
                    $stmt_sync_equipo->bind_param("ii", $auto_id, $equipoId);
                    $stmt_sync_equipo->execute();
                }
                $stmt_sync_equipo->close();
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'id' => $trabajoId]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function deleteTrabajo($conn, $data) {
    $trabajoId = $data['id'];
    $conn->begin_transaction();
    try {
        $stmt_get_items = $conn->prepare("SELECT item_id, cantidad_usada FROM trabajo_items WHERE trabajo_id = ?");
        $stmt_get_items->bind_param("i", $trabajoId);
        $stmt_get_items->execute();
        $items_result = $stmt_get_items->get_result();
        $stmt_restore_stock = $conn->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
        while($item = $items_result->fetch_assoc()) {
            $stmt_restore_stock->bind_param("ii", $item['cantidad_usada'], $item['item_id']);
            $stmt_restore_stock->execute();
        }
        $stmt_get_items->close();
        $stmt_restore_stock->close();

        $stmt_delete = $conn->prepare("DELETE FROM trabajos WHERE id = ?");
        $stmt_delete->bind_param("i", $trabajoId);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getTrabajoImage($conn, $trabajoId) {
    $stmt = $conn->prepare("SELECT imagen, imagen_mime FROM trabajos WHERE id = ?");
    $stmt->bind_param("i", $trabajoId);
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

/**
 * NEW FUNCTION
 * Fetches all cortes for the trabajo modal dropdown.
 */
function getAllCortes($conn) {
    $sql = "SELECT id, nombre FROM cortes ORDER BY nombre ASC";
    $result = $conn->query($sql);
    $cortes = [];
    while($row = $result->fetch_assoc()) {
        $cortes[] = $row;
    }
    echo json_encode($cortes);
}
?>

