<?php
include 'db.php';

// Check if order_items table exists
$result = $conn->query("SHOW TABLES LIKE 'order_items'");

if ($result->num_rows > 0) {
    echo "<h2>order_items table exists</h2>";
    
    // Get table structure
    $structure = $conn->query("DESCRIBE order_items");
    
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $structure->fetch_assoc()) {
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
    
    // Get sample data
    $data = $conn->query("SELECT * FROM order_items LIMIT 5");
    
    echo "<h3>Sample Data:</h3>";
    if ($data->num_rows > 0) {
        echo "<pre>";
        while ($row = $data->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "<p>No data found in order_items table</p>";
    }
} else {
    echo "<h2>order_items table does not exist</h2>";
    
    // Create the table
    echo "<p>Creating order_items table...</p>";
    
    $create_table = "CREATE TABLE order_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        order_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (id)
    )";
    
    if ($conn->query($create_table) === TRUE) {
        echo "<p>order_items table created successfully</p>";
    } else {
        echo "<p>Error creating order_items table: " . $conn->error . "</p>";
    }
}

$conn->close();
?>