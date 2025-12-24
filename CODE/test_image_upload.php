<?php
session_start();
include 'db.php';

// Simulate a logged-in vendor user
$_SESSION['user_id'] = 2; // Assuming user ID 2 is a vendor
$_SESSION['role'] = 'vendor';

// Check if the uploads/products directory exists, create if not
$upload_dir = 'uploads/products/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "<p>Created directory: {$upload_dir}</p>";
}

// Display the form for image upload
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<h1>Test Image Upload</h1>";
    echo "<form action=\"test_image_upload.php\" method=\"post\" enctype=\"multipart/form-data\">";
    echo "<p>Product Name: <input type=\"text\" name=\"product_name\" value=\"Test Product\"></p>";
    echo "<p>Image: <input type=\"file\" name=\"product_image\"></p>";
    echo "<p><input type=\"submit\" value=\"Upload\"></p>";
    echo "</form>";
    
    // Display existing products with images
    $vendor_id = $_SESSION['user_id'];
    $sql = "SELECT id, name, image_url FROM products WHERE vendor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<h2>Existing Products:</h2>";
    echo "<div style='display: flex; flex-wrap: wrap;'>";
    while ($row = $result->fetch_assoc()) {
        echo "<div style='margin: 10px; border: 1px solid #ccc; padding: 10px;'>";
        echo "<p>ID: {$row['id']}</p>";
        echo "<p>Name: {$row['name']}</p>";
        echo "<p>Image URL: {$row['image_url']}</p>";
        if (!empty($row['image_url'])) {
            echo "<img src=\"{$row['image_url']}\" style='max-width: 200px; max-height: 200px;'>";
        } else {
            echo "<p>No image</p>";
        }
        echo "</div>";
    }
    echo "</div>";
    
    exit();
}

// Process the form submission
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
    $product_name = $_POST['product_name'];
    
    // Get file info
    $file_name = basename($_FILES['product_image']['name']);
    $file_type = $_FILES['product_image']['type'];
    $file_size = $_FILES['product_image']['size'];
    $tmp_name = $_FILES['product_image']['tmp_name'];
    
    echo "<h2>File Information:</h2>";
    echo "<p>Original Name: {$file_name}</p>";
    echo "<p>Type: {$file_type}</p>";
    echo "<p>Size: {$file_size} bytes</p>";
    
    // Generate unique filename
    $new_file_name = time() . '_' . $file_name;
    $target_file = $upload_dir . $new_file_name;
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($file_type, $allowed_types)) {
        if (move_uploaded_file($tmp_name, $target_file)) {
            echo "<p>File uploaded successfully to: {$target_file}</p>";
            
            // Insert into database
            $vendor_id = $_SESSION['user_id'];
            $sql = "INSERT INTO products (vendor_id, name, image_url, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $vendor_id, $product_name, $target_file);
            
            if ($stmt->execute()) {
                $product_id = $conn->insert_id;
                echo "<p>Product added to database with ID: {$product_id}</p>";
                echo "<p><a href='vendor_dashboard.php'>Go to Dashboard</a></p>";
                
                // Display the uploaded image
                echo "<h2>Uploaded Image:</h2>";
                echo "<img src=\"{$target_file}\" style='max-width: 400px;'>";
            } else {
                echo "<p>Error adding to database: {$conn->error}</p>";
            }
        } else {
            echo "<p>Error moving uploaded file!</p>";
        }
    } else {
        echo "<p>Invalid file type. Allowed types: JPEG, PNG, GIF</p>";
    }
} else {
    echo "<h1>Error!</h1>";
    echo "<p>No file uploaded or upload error occurred.</p>";
    if (isset($_FILES['product_image'])) {
        echo "<p>Error code: {$_FILES['product_image']['error']}</p>";
    }
}

echo "<p><a href='test_image_upload.php'>Back to form</a></p>";
?>