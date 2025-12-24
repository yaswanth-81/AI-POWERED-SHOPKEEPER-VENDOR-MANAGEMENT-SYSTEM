<?php
// Script to add stock_quantity column to products table
require 'db.php';

echo "<h1>Adding Stock Quantity Column</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
</style>";

// Check if stock_quantity column already exists
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'stock_quantity'");

if ($check_column->num_rows > 0) {
    echo "<p class='success'>✓ Stock quantity column already exists!</p>";
} else {
    // Add stock_quantity column
    $sql = "ALTER TABLE products ADD COLUMN stock_quantity INT DEFAULT 0 AFTER price";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✓ Stock quantity column added successfully!</p>";
        
        // Update existing products to have a default stock value
        $update_sql = "UPDATE products SET stock_quantity = 10 WHERE stock_quantity IS NULL OR stock_quantity = 0";
        if ($conn->query($update_sql) === TRUE) {
            echo "<p class='success'>✓ Updated existing products with default stock quantity (10)</p>";
        } else {
            echo "<p class='error'>✗ Error updating existing products: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='error'>✗ Error adding stock quantity column: " . $conn->error . "</p>";
    }
}

// Show the current table structure
echo "<h2>Current Products Table Structure</h2>";
$result = $conn->query("DESCRIBE products");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<h2>Next Steps</h2>";
echo "<p>Now you can:</p>";
echo "<ul>";
echo "<li><a href='vendor_dashboard.php'>Go to Vendor Dashboard</a></li>";
echo "<li><a href='add_product.php'>Add New Product</a></li>";
echo "<li><a href='test_database_connection.php'>Test Database Connection</a></li>";
echo "</ul>";
?>