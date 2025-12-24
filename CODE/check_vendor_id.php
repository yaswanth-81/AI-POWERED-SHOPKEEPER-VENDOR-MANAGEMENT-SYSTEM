<?php
include 'db.php';

// Check if vendor_id column exists in orders table
$result = $conn->query("SHOW COLUMNS FROM orders LIKE 'vendor_id'");

if ($result && $result->num_rows > 0) {
    echo "<p>vendor_id column exists in orders table</p>";
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<p>vendor_id column does NOT exist in orders table</p>";
    
    // Check if we need to add the column
    echo "<p>Adding vendor_id column to orders table...</p>";
    $alter_sql = "ALTER TABLE orders ADD COLUMN vendor_id INT(11) AFTER user_id";
    
    if ($conn->query($alter_sql) === TRUE) {
        echo "<p>Successfully added vendor_id column</p>";
    } else {
        echo "<p>Error adding vendor_id column: " . $conn->error . "</p>";
    }
}

$conn->close();
?>