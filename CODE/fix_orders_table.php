<?php
// Script to ensure orders table has the correct structure
require 'db.php';

echo "<h1>Fixing Orders Table Structure</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
</style>";

// Check if vendor_id column exists
$check_vendor_id = $conn->query("SHOW COLUMNS FROM orders LIKE 'vendor_id'");
if (!$check_vendor_id || $check_vendor_id->num_rows == 0) {
    echo "<p class='info'>Adding vendor_id column to orders table...</p>";
    $sql = "ALTER TABLE orders ADD COLUMN vendor_id INT(11) NULL AFTER user_id";
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✓ vendor_id column added successfully</p>";
    } else {
        echo "<p class='error'>✗ Error adding vendor_id column: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✓ vendor_id column already exists</p>";
}

// Check if total column exists (should NOT exist, only total_amount)
$check_total = $conn->query("SHOW COLUMNS FROM orders LIKE 'total'");
if ($check_total && $check_total->num_rows > 0) {
    echo "<p class='info'>Removing incorrect 'total' column (should use total_amount only)...</p>";
    $sql = "ALTER TABLE orders DROP COLUMN total";
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✓ Removed incorrect 'total' column</p>";
    } else {
        echo "<p class='error'>✗ Error removing 'total' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✓ No incorrect 'total' column found (correct)</p>";
}

// Show current table structure
echo "<h2>Current Orders Table Structure:</h2>";
$result = $conn->query("DESCRIBE orders");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<h2>Next Steps</h2>";
echo "<p>Now you can:</p>";
echo "<ul>";
echo "<li><a href='checkout.php'>Test Checkout</a></li>";
echo "<li><a href='shopkeeper_dashboard.php'>Go to Shopkeeper Dashboard</a></li>";
echo "</ul>";
?>

