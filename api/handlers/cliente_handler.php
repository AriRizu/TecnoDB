<?php
// ========== Archivo: api/handlers/cliente_handler.php ==========

function getClientes($conn) {
    $sql = "SELECT c.*, (SELECT COUNT(t.id) FROM trabajos t WHERE t.cliente_id = c.id) as trabajo_count
            FROM clientes c
            ORDER BY c.nombre ASC";
    $result = $conn->query($sql);
    $clientes = [];
    while($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    echo json_encode($clientes);
}

function addCliente($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO clientes (nombre, telefono, cvu, notas) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $data['nombre'], $data['telefono'], $data['cvu'], $data['notas']);
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        $new_cliente_stmt = $conn->prepare("SELECT c.*, (SELECT COUNT(t.id) FROM trabajos t WHERE t.cliente_id = c.id) as trabajo_count FROM clientes c WHERE c.id = ?");
        $new_cliente_stmt->bind_param("i", $new_id);
        $new_cliente_stmt->execute();
        $new_cliente = $new_cliente_stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'cliente' => $new_cliente]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function editCliente($conn, $data) {
    $stmt = $conn->prepare("UPDATE clientes SET nombre = ?, telefono = ?, cvu = ?, notas = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $data['nombre'], $data['telefono'], $data['cvu'], $data['notas'], $data['id']);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function deleteCliente($conn, $data) {
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function getClienteDetails($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();
    $stmt->close();

    if ($cliente) {
        $stmt_trabajos = $conn->prepare("
            SELECT
                tr.id, tr.net_profit, tr.gastos, tr.fecha_creacion,
                tt.nombre as tipo_trabajo_nombre,
                CONCAT(a.marca, ' ', a.modelo) as auto_nombre
            FROM trabajos tr
            LEFT JOIN tipos_trabajo tt ON tr.tipo_trabajo_id = tt.id
            LEFT JOIN autos a ON tr.auto_id = a.id
            WHERE tr.cliente_id = ?
            ORDER BY tr.fecha_creacion DESC
        ");
        $stmt_trabajos->bind_param("i", $id);
        $stmt_trabajos->execute();
        $trabajos_result = $stmt_trabajos->get_result();
        $cliente['trabajos'] = [];
        while($row = $trabajos_result->fetch_assoc()) {
            $cliente['trabajos'][] = $row;
        }
        $stmt_trabajos->close();
    }

    echo json_encode($cliente);
}

?>
