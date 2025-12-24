<?php
session_start();
include 'db.php';

// Check if user is logged in as vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    // For testing purposes, we'll simulate a vendor login
    $_SESSION['user_id'] = 1; // Assuming vendor ID 1 exists
    $_SESSION['role'] = 'vendor';
    echo "<p>Simulated vendor login for testing.</p>";
}

$vendor_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'upload_image') {
        // Handle image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $upload_dir = 'uploads/products/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['product_image']['name']);
            $target_file = $upload_dir . $file_name;
            
            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['product_image']['type'], $allowed_types)) {
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                    echo "<div style='color:green'>Image uploaded successfully to: " . htmlspecialchars($target_file) . "</div>";
                    
                    // Update product in database
                    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
                        $product_id = intval($_POST['product_id']);
                        
                        // Update the image_url for this product
                        $sql = "UPDATE products SET image_url = ? WHERE id = ? AND vendor_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sii", $target_file, $product_id, $vendor_id);
                        
                        if ($stmt->execute()) {
                            echo "<div style='color:green'>Product image updated in database.</div>";
                        } else {
                            echo "<div style='color:red'>Error updating product: " . $conn->error . "</div>";
                        }
                    }
                } else {
                    echo "<div style='color:red'>Failed to move uploaded file.</div>";
                }
            } else {
                echo "<div style='color:red'>Invalid file type. Allowed types: JPEG, PNG, GIF</div>";
            }
        } else {
            echo "<div style='color:red'>Error uploading file: " . $_FILES['product_image']['error'] . "</div>";
        }
    }
}

// Get all products for this vendor
$sql = "SELECT * FROM products WHERE vendor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

// Display debug information
echo "<h2>Debug Information</h2>";
echo "<p>Vendor ID: " . $vendor_id . "</p>";

// Display form for uploading images
echo "<h2>Upload Product Image</h2>";
echo "<form method='post' enctype='multipart/form-data'>";
echo "<input type='hidden' name='action' value='upload_image'>";
echo "<label for='product_id'>Select Product:</label>";
echo "<select name='product_id' id='product_id' required>";

if ($result && $result->num_rows > 0) {
    while ($product = $result->fetch_assoc()) {
        echo "<option value='" . $product['id'] . "'>" . htmlspecialchars($product['name'] ?? 'Unknown Product') . "</option>";
    }
    // Reset result pointer
    $result->data_seek(0);
} else {
    echo "<option value=''>No products found</option>";
}

echo "</select><br><br>";
echo "<label for='product_image'>Select Image:</label>";
echo "<input type='file' name='product_image' id='product_image' required><br><br>";
echo "<button type='submit'>Upload Image</button>";
echo "</form>";

// Display current products with images
echo "<h2>Current Products</h2>";

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "<th>Image Path</th>";
    echo "<th>Image Preview</th>";
    echo "</tr>";
    
    while ($product = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['name'] ?? 'Unknown Product') . "</td>";
        echo "<td>" . htmlspecialchars($product['image_url'] ?? 'No image') . "</td>";
        echo "<td>";
        if (!empty($product['image_url']) && file_exists($product['image_url'])) {
            echo "<img src='" . htmlspecialchars($product['image_url']) . "' alt='Product Image' style='max-width:100px; max-height:100px;'>";
        } else {
            echo "Image not found or invalid path";
        }
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No products found for this vendor.</p>";
}

// Check uploads directory
echo "<h2>Uploads Directory Check</h2>";
$upload_dir = 'uploads/products/';
if (file_exists($upload_dir)) {
    echo "<p>Uploads directory exists: " . realpath($upload_dir) . "</p>";
    
    // List files in the directory
    $files = scandir($upload_dir);
    echo "<p>Files in uploads directory:</p>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . htmlspecialchars($file) . " (" . filesize($upload_dir . $file) . " bytes)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>Uploads directory does not exist.</p>";
}

$conn->close();
?>