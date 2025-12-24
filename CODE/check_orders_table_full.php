<?php
include 'db.php';

// Check orders table structure
$result = $conn->query("DESCRIBE orders");

echo "<h2>Orders Table Structure:</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}

echo "</table>";

// Check for total_amount column
$total_amount_exists = false;
$result = $conn->query("SHOW COLUMNS FROM orders LIKE 'total_amount'");
if ($result->num_rows > 0) {
    $total_amount_exists = true;
    echo "<p>total_amount column exists in orders table</p>";
} else {
    echo "<p>total_amount column does NOT exist in orders table</p>";
}

// Add total_amount column if it doesn't exist
if (!$total_amount_exists) {
    echo "<p>Adding total_amount column to orders table...</p>";
    
    $alter_table = "ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,2) AFTER total";
    
    if ($conn->query($alter_table) === TRUE) {
        echo "<p>total_amount column added successfully</p>";
    } else {
        echo "<p>Error adding total_amount column: " . $conn->error . "</p>";
    }
}

// Get sample data
$data = $conn->query("SELECT * FROM orders LIMIT 5");

echo "<h3>Sample Data:</h3>";
if ($data->num_rows > 0) {
    echo "<pre>";
    while ($row = $data->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "<p>No data found in orders table</p>";
}

$conn->close();
?>