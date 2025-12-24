<?php
require 'db.php';

// Check order_items table structure
$result = $conn->query('SHOW COLUMNS FROM order_items');

if ($result) {
    echo "<h2>Order Items Table Structure</h2>";
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
    
    // Check for sample data
    $data_result = $conn->query('SELECT * FROM order_items LIMIT 5');
    
    if ($data_result && $data_result->num_rows > 0) {
        echo "<h2>Sample Order Items Data</h2>";
        echo "<table border='1'>";
        
        // Get field names
        $fields = $data_result->fetch_fields();
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>{$field->name}</th>";
        }
        echo "</tr>";
        
        // Reset pointer
        $data_result->data_seek(0);
        
        // Get data
        while ($row = $data_result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No sample data found in order_items table.</p>";
    }
} else {
    echo "<p>Error: " . $conn->error . "</p>";
}

$conn->close();
?>