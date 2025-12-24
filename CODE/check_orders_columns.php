<?php
include 'db.php';

// Check orders table structure
$result = $conn->query("SHOW COLUMNS FROM orders");
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

// Get sample order data
$sample = $conn->query("SELECT * FROM orders LIMIT 1");
if ($sample && $sample->num_rows > 0) {
    echo "<h2>Sample Order Data:</h2>";
    echo "<pre>";
    print_r($sample->fetch_assoc());
    echo "</pre>";
}

$conn->close();
?>