<?php
require 'db.php';

// List of default test product names to remove
$test_products = [
    'Test Product 1754115957',
    'Test Product 1754115989',
    'Test Product 1754116030',
    'Sample Product',
    'Demo Product',
    'Example Product'
];

// Also remove products with 'Test' in the category
$sql = "DELETE FROM products WHERE category LIKE '%Test%'";
$conn->query($sql);
$test_category_affected = $conn->affected_rows;
echo "<p>Removed $test_category_affected products with 'Test' in the category.</p>";

// Build the SQL query to delete test products
$placeholders = implode(',', array_fill(0, count($test_products), '?'));
$sql = "DELETE FROM products WHERE name IN ($placeholders)";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);

// Bind parameters
$types = str_repeat('s', count($test_products));
$stmt->bind_param($types, ...$test_products);

// Execute the statement
$stmt->execute();

// Check how many rows were affected
$affected_rows = $stmt->affected_rows;

echo "<h2>Remove Test Products</h2>";
echo "<p>Removed $affected_rows test products from the database.</p>";

// Also remove products with empty or NULL names
$sql = "DELETE FROM products WHERE name IS NULL OR name = ''";
$conn->query($sql);
$empty_affected = $conn->affected_rows;
echo "<p>Removed $empty_affected products with empty names.</p>";

// Also remove products with price 0 or NULL
$sql = "DELETE FROM products WHERE price IS NULL OR price = 0";
$conn->query($sql);
$price_affected = $conn->affected_rows;
echo "<p>Removed $price_affected products with zero or NULL prices.</p>";

// Show remaining products
$sql = "SELECT * FROM products ORDER BY id";
$result = $conn->query($sql);

echo "<h3>Remaining Products:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Vendor</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['category']}</td>";
        echo "<td>\${$row['price']}</td>";
        echo "<td>{$row['stock']}</td>";
        echo "<td>{$row['vendor_id']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No products found in the database.</p>";
}

echo "<p><a href='shopkeeper_dashboard.php'>Go to Shopkeeper Dashboard</a></p>";
echo "<p><a href='vendor_dashboard_new.php'>Go to Vendor Dashboard</a></p>";

$conn->close();
?>