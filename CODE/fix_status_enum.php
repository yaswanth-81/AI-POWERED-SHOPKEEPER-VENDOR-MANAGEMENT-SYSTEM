<?php
// Script to fix status ENUM to include all needed values
require 'db.php';

echo "<h1>Fix Status ENUM Column</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
</style>";

// Check current status column definition
$col_info = $conn->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
if ($col_info && $col_info->num_rows > 0) {
    $col = $col_info->fetch_assoc();
    echo "<p class='info'>Current status column type: " . $col['Type'] . "</p>";
    
    // Check if it needs updating
    $needs_update = false;
    $required_values = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (stripos($col['Type'], 'enum') !== false) {
        preg_match("/enum\((.*)\)/i", $col['Type'], $matches);
        if (isset($matches[1])) {
            $current_values = array_map(function($v) {
                return trim(strtolower(str_replace("'", "", $v)));
            }, explode(',', $matches[1]));
            
            echo "<p class='info'>Current ENUM values: " . implode(', ', $current_values) . "</p>";
            echo "<p class='info'>Required values: " . implode(', ', $required_values) . "</p>";
            
            // Check if all required values are present
            foreach ($required_values as $req_val) {
                if (!in_array(strtolower($req_val), $current_values)) {
                    $needs_update = true;
                    break;
                }
            }
        }
    } else {
        // Not an ENUM, might be VARCHAR - we can convert it
        $needs_update = true;
    }
    
    if ($needs_update) {
        echo "<p class='info'>Updating status column to include all required values...</p>";
        
        // Modify the column to be ENUM with all required values
        $enum_values = "'" . implode("','", $required_values) . "'";
        $alter_sql = "ALTER TABLE orders MODIFY COLUMN status ENUM($enum_values) DEFAULT 'pending'";
        
        if ($conn->query($alter_sql) === TRUE) {
            echo "<p class='success'>✓ Successfully updated status column to ENUM(" . implode(', ', $required_values) . ")</p>";
            
            // Update any invalid status values to 'pending'
            $update_invalid = "UPDATE orders SET status = 'pending' WHERE status NOT IN ($enum_values) OR status IS NULL";
            if ($conn->query($update_invalid) === TRUE) {
                echo "<p class='success'>✓ Updated invalid status values to 'pending'</p>";
            }
        } else {
            echo "<p class='error'>✗ Error updating status column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='success'>✓ Status column already has all required values. No update needed.</p>";
    }
    
    // Show final column definition
    $final_col = $conn->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
    if ($final_col && $final_col->num_rows > 0) {
        $final = $final_col->fetch_assoc();
        echo "<p class='info'>Final status column type: " . $final['Type'] . "</p>";
    }
} else {
    echo "<p class='error'>Could not get status column information</p>";
}

// Show status distribution
echo "<h2>Current Order Status Distribution:</h2>";
$status_sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$status_result = $conn->query($status_sql);

if ($status_result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    while ($row = $status_result->fetch_assoc()) {
        $status_display = $row['status'] ?? 'NULL';
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($status_display) . "</td>";
        echo "<td style='padding: 8px;'>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<h2>Next Steps</h2>";
echo "<p>Now you can:</p>";
echo "<ul>";
echo "<li><a href='test_status_update.php'>Test Status Update</a></li>";
echo "<li><a href='vendor_orders.php'>View Vendor Orders</a></li>";
echo "<li><a href='fix_null_order_status.php'>Fix NULL Status Values</a></li>";
echo "</ul>";
?>

