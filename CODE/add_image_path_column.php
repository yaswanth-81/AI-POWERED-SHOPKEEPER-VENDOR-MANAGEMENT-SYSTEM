<?php
// Script to add the missing image_path column to products table
require 'db.php';

echo "<h1>Adding image_path Column to Products Table</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
</style>";

// Check if image_path column already exists
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'image_path'");
if ($check_column->num_rows > 0) {
    echo "<p class='success'>✓ image_path column already exists!</p>";
    echo "<p>No action needed.</p>";
} else {
    // Add the image_path column
    $sql = "ALTER TABLE products ADD COLUMN image_path VARCHAR(255) AFTER price";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✓ Successfully added image_path column to products table!</p>";
        echo "<p>The column has been added with type VARCHAR(255).</p>";
    } else {
        echo "<p class='error'>✗ Error adding image_path column: " . $conn->error . "</p>";
    }
}

// Verify the column was added
echo "<h2>Verification</h2>";
$result = $conn->query("DESCRIBE products");
if ($result) {
    echo "<p>Current products table structure:</p>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f3f4f6;'>";
    echo "<th style='padding: 8px;'>Field</th>";
    echo "<th style='padding: 8px;'>Type</th>";
    echo "<th style='padding: 8px;'>Null</th>";
    echo "<th style='padding: 8px;'>Key</th>";
    echo "<th style='padding: 8px;'>Default</th>";
    echo "<th style='padding: 8px;'>Extra</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['Field'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['Type'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['Null'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['Key'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['Default'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<h2>Next Steps</h2>";
echo "<p>Now that image_path column is added:</p>";
echo "<ul>";
echo "<li><a href='vendor_dashboard.php'>Test Vendor Dashboard</a> - This should work now!</li>";
echo "<li><a href='add_product.php'>Add a Test Product</a> - To test the new column</li>";
echo "<li><a href='check_products_structure.php'>Verify All Columns</a> - Double-check everything</li>";
echo "</ul>";

echo "<h2>Manual SQL Command (if needed)</h2>";
echo "<p>If the script above doesn't work, run this in phpMyAdmin:</p>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<code>ALTER TABLE products ADD COLUMN image_path VARCHAR(255) AFTER price;</code>";
echo "</div>";
?>

