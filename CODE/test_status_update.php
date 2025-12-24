<?php
// Test script to check status column and update status
require 'db.php';

echo "<h1>Test Status Update</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
    table { border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// Check status column definition
echo "<h2>Status Column Definition:</h2>";
$col_info = $conn->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
if ($col_info && $col_info->num_rows > 0) {
    $col = $col_info->fetch_assoc();
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    echo "<tr>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . ($col['Extra'] ?? '') . "</td>";
    echo "</tr>";
    echo "</table>";
    
    // Check if it's ENUM
    if (stripos($col['Type'], 'enum') !== false) {
        echo "<p class='info'>Status column is ENUM type. Extracting allowed values...</p>";
        preg_match("/enum\((.*)\)/i", $col['Type'], $matches);
        if (isset($matches[1])) {
            $enum_values = array_map(function($v) {
                return trim(str_replace("'", "", $v));
            }, explode(',', $matches[1]));
            echo "<p class='info'>Allowed ENUM values: " . implode(', ', $enum_values) . "</p>";
        }
    }
} else {
    echo "<p class='error'>Could not get status column info</p>";
}

// Show current orders with NULL status
echo "<h2>Orders with NULL Status:</h2>";
$null_orders = $conn->query("SELECT id, user_id, vendor_id, total_amount, status, created_at FROM orders WHERE status IS NULL");
if ($null_orders && $null_orders->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>User ID</th><th>Vendor ID</th><th>Total</th><th>Status</th><th>Created</th><th>Action</th></tr>";
    while ($order = $null_orders->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $order['id'] . "</td>";
        echo "<td>" . $order['user_id'] . "</td>";
        echo "<td>" . ($order['vendor_id'] ?? 'N/A') . "</td>";
        echo "<td>$" . number_format($order['total_amount'], 2) . "</td>";
        echo "<td>" . ($order['status'] ?? 'NULL') . "</td>";
        echo "<td>" . $order['created_at'] . "</td>";
        echo "<td>";
        echo "<form method='POST' action='test_status_update.php' style='display: inline;'>";
        echo "<input type='hidden' name='order_id' value='" . $order['id'] . "'>";
        echo "<input type='hidden' name='action' value='update'>";
        echo "<select name='status'>";
        echo "<option value='pending'>Pending</option>";
        echo "<option value='processing'>Processing</option>";
        echo "<option value='shipped'>Shipped</option>";
        echo "<option value='delivered'>Delivered</option>";
        echo "<option value='cancelled'>Cancelled</option>";
        echo "</select>";
        echo "<button type='submit'>Update</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='success'>No orders with NULL status found.</p>";
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    echo "<h2>Update Result:</h2>";
    
    $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    
    if (!$stmt) {
        echo "<p class='error'>Error preparing query: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            if ($affected > 0) {
                echo "<p class='success'>✓ Successfully updated order #$order_id to status '$new_status'</p>";
            } else {
                echo "<p class='error'>✗ No rows affected. Order may not exist or status is already '$new_status'</p>";
            }
        } else {
            echo "<p class='error'>✗ Error executing update: " . $stmt->error . "</p>";
        }
        
        $stmt->close();
    }
    
    echo "<p><a href='test_status_update.php'>Refresh Page</a></p>";
}

$conn->close();
?>

