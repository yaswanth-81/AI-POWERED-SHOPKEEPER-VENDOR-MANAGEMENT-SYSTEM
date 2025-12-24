<?php
// Script to fix NULL status values in orders table
require 'db.php';

echo "<h1>Fixing NULL Order Status Values</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
</style>";

// Count NULL status orders
$count_sql = "SELECT COUNT(*) as null_count FROM orders WHERE status IS NULL";
$count_result = $conn->query($count_sql);
$null_count = $count_result ? $count_result->fetch_assoc()['null_count'] : 0;

echo "<p class='info'>Found $null_count orders with NULL status.</p>";

if ($null_count > 0) {
    // Update NULL status to 'pending'
    $update_sql = "UPDATE orders SET status = 'pending' WHERE status IS NULL";
    if ($conn->query($update_sql) === TRUE) {
        echo "<p class='success'>✓ Successfully updated $null_count orders with NULL status to 'pending'</p>";
    } else {
        echo "<p class='error'>✗ Error updating orders: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✓ No orders with NULL status found. All orders have valid status values.</p>";
}

// Show current status distribution
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
echo "<li><a href='vendor_orders.php'>View Vendor Orders</a></li>";
echo "<li><a href='shopkeeper_orders.php'>View Shopkeeper Orders</a></li>";
echo "<li><a href='vendor_dashboard.php'>Go to Vendor Dashboard</a></li>";
echo "</ul>";
?>

