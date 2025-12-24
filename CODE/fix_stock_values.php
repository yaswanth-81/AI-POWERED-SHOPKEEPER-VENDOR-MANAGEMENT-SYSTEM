<?php
require 'db.php';

// Check if the stock field exists in the products table
$check_field = "SHOW COLUMNS FROM products LIKE 'stock'";
$field_result = $conn->query($check_field);

if ($field_result->num_rows == 0) {
    // Add stock field if it doesn't exist
    $add_field = "ALTER TABLE products ADD COLUMN stock INT(11) NOT NULL DEFAULT 0";
    
    if ($conn->query($add_field) === TRUE) {
        echo "<p>Stock field added to products table successfully</p>";
    } else {
        echo "<p>Error adding stock field: " . $conn->error . "</p>";
        exit;
    }
} else {
    echo "<p>Stock field already exists in products table</p>";
}

// Process form submission to update stock values
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    foreach ($_POST['stock'] as $product_id => $stock_value) {
        $product_id = (int)$product_id;
        $stock_value = (int)$stock_value;
        
        $update_stock = "UPDATE products SET stock = {$stock_value} WHERE id = {$product_id}";
        
        if ($conn->query($update_stock) === TRUE) {
            echo "<p>Updated stock for product ID {$product_id} to {$stock_value}</p>";
        } else {
            echo "<p>Error updating stock for product ID {$product_id}: " . $conn->error . "</p>";
        }
    }
    
    echo "<p>Stock values updated successfully!</p>";
}

// Get all products
$get_products = "SELECT * FROM products";
$products_result = $conn->query($get_products);

// Check if there are any products with zero stock and update them with random values
$zero_stock = "SELECT COUNT(*) as count FROM products WHERE stock = 0 OR stock IS NULL";
$zero_result = $conn->query($zero_stock);
$zero_count = $zero_result->fetch_assoc()['count'];

if ($zero_count > 0 && !isset($_POST['update_stock'])) {
    echo "<p>Found {$zero_count} products with zero or NULL stock values.</p>";
    echo "<p>Would you like to update them with random stock values?</p>";
    echo "<form method='post' action=''>";
    echo "<input type='submit' name='random_stock' value='Yes, update with random values' class='btn btn-primary'>";
    echo "</form>";
    
    if (isset($_POST['random_stock'])) {
        $update_random = "UPDATE products SET stock = FLOOR(RAND() * 100) + 1 WHERE stock = 0 OR stock IS NULL";
        
        if ($conn->query($update_random) === TRUE) {
            echo "<p>Updated all zero/NULL stock values with random values between 1 and 100</p>";
            // Refresh the page to show updated values
            echo "<script>window.location.href = 'fix_stock_values.php';</script>";
            exit;
        } else {
            echo "<p>Error updating stock values: " . $conn->error . "</p>";
        }
    }
}

// Display current products and their stock values
echo "<h2>Current Product Stock Values</h2>";

if ($products_result->num_rows > 0) {
    echo "<form method='post' action=''>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Current Stock</th><th>New Stock</th></tr>";
    
    while ($product = $products_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>{$product['name']}</td>";
        echo "<td>{$product['category']}</td>";
        echo "<td>\${$product['price']}</td>";
        echo "<td>{$product['stock']}</td>";
        echo "<td><input type='number' name='stock[{$product['id']}]' value='{$product['stock']}' min='0'></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<br><input type='submit' name='update_stock' value='Update Stock Values'>";
    echo "</form>";
} else {
    echo "<p>No products found</p>";
}

echo "<p><a href='vendor_dashboard_new.php'>Return to Vendor Dashboard</a></p>";
echo "<p><a href='shopkeeper_dashboard.php'>Go to Shopkeeper Dashboard</a></p>";

$conn->close();
?>