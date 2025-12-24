<?php
include 'db.php';

// Define the image mapping based on product names
$image_mapping = [
    'tomato' => 'images/tomatoes.jpg',
    'tomatoes' => 'images/tomatoes.jpg',
    'potato' => 'images/potatoes.jpg',
    'potatoes' => 'images/potatoes.jpg',
    'milk' => 'images/milk.jpg',
    'default' => 'images/default.jpg'
];

// Get all products
$sql = "SELECT id, name, image_url FROM products";
$result = $conn->query($sql);

echo "<h2>Updating Product Images</h2>";

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Old Image URL</th><th>New Image URL</th><th>Status</th></tr>";
    
    while ($product = $result->fetch_assoc()) {
        $id = $product['id'];
        $name = strtolower($product['name'] ?? '');
        $old_image_url = $product['image_url'];
        
        // Determine the appropriate image based on product name
        $new_image_url = $image_mapping['default']; // Default image
        
        foreach ($image_mapping as $keyword => $image) {
            if (strpos($name, $keyword) !== false) {
                $new_image_url = $image;
                break;
            }
        }
        
        // Update the product's image_url in the database
        $update_sql = "UPDATE products SET image_url = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_image_url, $id);
        
        if ($stmt->execute()) {
            $status = "Updated successfully";
        } else {
            $status = "Error: " . $conn->error;
        }
        
        echo "<tr>";
        echo "<td>" . $id . "</td>";
        echo "<td>" . htmlspecialchars($name) . "</td>";
        echo "<td>" . htmlspecialchars($old_image_url) . "</td>";
        echo "<td>" . htmlspecialchars($new_image_url) . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No products found in the database.</p>";
}

// Check if the images directory exists
echo "<h2>Images Directory Check</h2>";
$images_dir = 'images/';
if (file_exists($images_dir)) {
    echo "<p>Images directory exists: " . realpath($images_dir) . "</p>";
    
    // List files in the directory
    $files = scandir($images_dir);
    echo "<p>Files in images directory:</p>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . htmlspecialchars($file) . " (" . filesize($images_dir . $file) . " bytes)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>Images directory does not exist.</p>";
}

echo "<p><a href='vendor_dashboard.php'>Go to Vendor Dashboard</a></p>";

$conn->close();
?>