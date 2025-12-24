<?php
session_start();
include 'db.php';

// Simulate a logged-in vendor user
$_SESSION['user_id'] = 2; // Assuming user ID 2 is a vendor
$_SESSION['role'] = 'vendor';

// Product data
$product_name = 'Test Product ' . time();
$category = 'Test Category';
$price = 19.99;
$description = 'This is a test product added via script';
$image_url = 'images/default.jpg';

// Insert the product
$vendor_id = $_SESSION['user_id'];
$sql = "INSERT INTO products (vendor_id, name, category, description, price, image_url, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssds", $vendor_id, $product_name, $category, $description, $price, $image_url);

if ($stmt->execute()) {
    echo "<h1>Success!</h1>";
    echo "<p>Product '{$product_name}' added successfully with vendor_id {$vendor_id}</p>";
    
    // Verify by retrieving the product
    $product_id = $conn->insert_id;
    $verify_sql = "SELECT * FROM products WHERE id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("i", $product_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "<h2>Product Details:</h2>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
} else {
    echo "<h1>Error!</h1>";
    echo "<p>Failed to add product: " . $conn->error . "</p>";
}

// Clear the session to avoid affecting other tests
unset($_SESSION['user_id']);
unset($_SESSION['role']);

echo "<p><a href='vendor_dashboard_new.php'>Go to Vendor Dashboard</a></p>";
?>