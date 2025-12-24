<?php
require 'db.php';

// Check if vendors table exists and has data
$check_vendors = "SELECT id FROM vendors LIMIT 1";
$vendor_result = $conn->query($check_vendors);

if ($vendor_result->num_rows == 0) {
    echo "<p>No vendors found in the database. Please run check_vendors_table.php first.</p>";
    echo "<p><a href='check_vendors_table.php'>Setup Vendors Table</a></p>";
    exit;
}

// Get a list of vendor IDs
$vendor_ids = [];
$vendors_sql = "SELECT id FROM vendors";
$vendors_result = $conn->query($vendors_sql);
while ($vendor = $vendors_result->fetch_assoc()) {
    $vendor_ids[] = $vendor['id'];
}

// Define diverse products by category
$products = [
    // Fresh Produce
    ['name' => 'Organic Apples', 'category' => 'Fresh Produce', 'description' => 'Fresh organic apples from local farms', 'price' => 3.99, 'stock' => rand(10, 100)],
    ['name' => 'Bananas', 'category' => 'Fresh Produce', 'description' => 'Ripe yellow bananas', 'price' => 1.49, 'stock' => rand(10, 100)],
    ['name' => 'Carrots', 'category' => 'Fresh Produce', 'description' => 'Organic carrots, perfect for salads', 'price' => 1.99, 'stock' => rand(10, 100)],
    ['name' => 'Spinach', 'category' => 'Fresh Produce', 'description' => 'Fresh leafy spinach', 'price' => 2.49, 'stock' => rand(10, 100)],
    ['name' => 'Bell Peppers', 'category' => 'Fresh Produce', 'description' => 'Colorful bell peppers', 'price' => 2.99, 'stock' => rand(10, 100)],
    
    // Dairy Products
    ['name' => 'Greek Yogurt', 'category' => 'Dairy Products', 'description' => 'Creamy Greek yogurt', 'price' => 3.49, 'stock' => rand(10, 100)],
    ['name' => 'Cheddar Cheese', 'category' => 'Dairy Products', 'description' => 'Sharp cheddar cheese', 'price' => 4.99, 'stock' => rand(10, 100)],
    ['name' => 'Butter', 'category' => 'Dairy Products', 'description' => 'Pure unsalted butter', 'price' => 3.29, 'stock' => rand(10, 100)],
    ['name' => 'Cream', 'category' => 'Dairy Products', 'description' => 'Fresh heavy cream', 'price' => 2.79, 'stock' => rand(10, 100)],
    
    // Dry Goods
    ['name' => 'Rice', 'category' => 'Dry Goods', 'description' => 'Premium long grain rice', 'price' => 5.99, 'stock' => rand(10, 100)],
    ['name' => 'Pasta', 'category' => 'Dry Goods', 'description' => 'Italian pasta', 'price' => 2.49, 'stock' => rand(10, 100)],
    ['name' => 'Sugar', 'category' => 'Dry Goods', 'description' => 'Refined white sugar', 'price' => 3.99, 'stock' => rand(10, 100)],
    ['name' => 'Tea', 'category' => 'Dry Goods', 'description' => 'Premium black tea', 'price' => 4.49, 'stock' => rand(10, 100)],
    ['name' => 'Coffee Beans', 'category' => 'Dry Goods', 'description' => 'Freshly roasted coffee beans', 'price' => 7.99, 'stock' => rand(10, 100)],
    
    // Meat & Poultry
    ['name' => 'Chicken Breast', 'category' => 'Meat & Poultry', 'description' => 'Boneless chicken breast', 'price' => 6.99, 'stock' => rand(10, 100)],
    ['name' => 'Ground Beef', 'category' => 'Meat & Poultry', 'description' => 'Lean ground beef', 'price' => 5.99, 'stock' => rand(10, 100)],
    ['name' => 'Pork Chops', 'category' => 'Meat & Poultry', 'description' => 'Tender pork chops', 'price' => 7.49, 'stock' => rand(10, 100)],
    ['name' => 'Lamb', 'category' => 'Meat & Poultry', 'description' => 'Premium lamb cuts', 'price' => 9.99, 'stock' => rand(10, 100)],
    
    // Bakery
    ['name' => 'Bread', 'category' => 'Bakery', 'description' => 'Freshly baked bread', 'price' => 2.99, 'stock' => rand(10, 100)],
    ['name' => 'Croissants', 'category' => 'Bakery', 'description' => 'Buttery croissants', 'price' => 3.49, 'stock' => rand(10, 100)],
    ['name' => 'Muffins', 'category' => 'Bakery', 'description' => 'Assorted muffins', 'price' => 4.99, 'stock' => rand(10, 100)]
];

// Insert products into the database
$inserted_count = 0;
$skipped_count = 0;

foreach ($products as $product) {
    // Check if product already exists
    $check_sql = "SELECT id FROM products WHERE name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $product['name']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo "<p>Product '{$product['name']}' already exists, skipping.</p>";
        $skipped_count++;
        continue;
    }
    
    // Assign a random vendor ID
    $vendor_id = $vendor_ids[array_rand($vendor_ids)];
    
    // Insert the product
    $insert_sql = "INSERT INTO products (name, category, description, price, stock, vendor_id) VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("sssdii", 
        $product['name'], 
        $product['category'], 
        $product['description'], 
        $product['price'], 
        $product['stock'],
        $vendor_id
    );
    
    if ($insert_stmt->execute()) {
        echo "<p>Added product: {$product['name']} ({$product['category']}) - \${$product['price']} - Stock: {$product['stock']} - Vendor ID: {$vendor_id}</p>";
        $inserted_count++;
    } else {
        echo "<p>Error adding product {$product['name']}: " . $conn->error . "</p>";
    }
}

echo "<h2>Product Addition Summary</h2>";
echo "<p>Added $inserted_count new products.</p>";
echo "<p>Skipped $skipped_count existing products.</p>";

// Show all products
$sql = "SELECT p.*, v.name as vendor_name FROM products p LEFT JOIN vendors v ON p.vendor_id = v.id ORDER BY p.category, p.name";
$result = $conn->query($sql);

echo "<h3>All Products:</h3>";
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
        echo "<td>{$row['vendor_name']} (ID: {$row['vendor_id']})</td>";
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