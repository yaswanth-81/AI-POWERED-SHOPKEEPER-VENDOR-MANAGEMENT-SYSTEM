<?php
include 'db.php';

// Check if status column exists in orders table
$result = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");

if ($result && $result->num_rows > 0) {
    echo "<p>status column exists in orders table</p>";
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<p>status column does NOT exist in orders table</p>";
    
    // Check if we need to add the column
    echo "<p>Adding status column to orders table...</p>";
    $alter_sql = "ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'pending' AFTER total";
    
    if ($conn->query($alter_sql) === TRUE) {
        echo "<p>Successfully added status column</p>";
    } else {
        echo "<p>Error adding status column: " . $conn->error . "</p>";
    }
}

$conn->close();
?>