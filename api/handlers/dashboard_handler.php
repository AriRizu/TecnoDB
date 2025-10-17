<?php
// api/handlers/dashboard_handler.php

/**
 * Gathers key statistics for the main dashboard view using the original database nomenclature.
 * @param mysqli $conn The database connection object.
 * @return array An array containing the calculated statistics.
 */
function getDashboardStats($conn) {
    $stats = [
        'items_out_of_stock' => 0,
        'revenue_last_30_days' => 0,
        'jobs_last_30_days' => 0,
        'total_items' => 0,
    ];

    try {
        // 1. Items out of stock (or below stock_threshold) - Uses original `items` table
        $result_items = $conn->query("SELECT COUNT(*) as count FROM items WHERE stock <= stock_threshold");
        if ($result_items) {
            $row_items = $result_items->fetch_assoc();
            $stats['items_out_of_stock'] = $row_items['count'] ?? 0;
        }

        // 2. Revenue and jobs in the last 30 days - Uses original `trabajos` table
        // NOTE: Using `net_profit` for revenue and `fecha_creacion` for date as per your db_setup.php schema.
        $result_trabajos = $conn->query("SELECT SUM(net_profit) as revenue, COUNT(*) as jobs FROM trabajos WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        if ($result_trabajos) {
            $trabajos_data = $result_trabajos->fetch_assoc();
            if ($trabajos_data) {
                $stats['revenue_last_30_days'] = $trabajos_data['revenue'] ?? 0;
                $stats['jobs_last_30_days'] = $trabajos_data['jobs'] ?? 0;
            }
        }

        // 3. Total unique items - Uses original `clientes` table
        $result_items = $conn->query("SELECT COUNT(id) as count FROM items");
        if ($result_items) {
            $row_items = $result_items->fetch_assoc();
            $stats['total_items'] = $row_items['count'] ?? 0;
        }

        return $stats;

    } catch (Exception $e) {
        // In case of error, return the default stats array
        error_log("Dashboard Stats Error: " . $e->getMessage());
        return $stats;
    }
}

