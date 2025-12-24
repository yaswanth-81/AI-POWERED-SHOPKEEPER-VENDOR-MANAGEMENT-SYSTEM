<?php
// Create sample images directory if it doesn't exist
$images_dir = 'images/';
if (!file_exists($images_dir)) {
    mkdir($images_dir, 0777, true);
    echo "<p>Created directory: {$images_dir}</p>";
}

// Function to copy sample images from a URL
function downloadSampleImage($filename, $url) {
    global $images_dir;
    $filepath = $images_dir . $filename;
    
    // Use file_get_contents to download the image
    $image_data = file_get_contents($url);
    if ($image_data !== false) {
        // Save the image to the local file
        if (file_put_contents($filepath, $image_data) !== false) {
            echo "<p>Downloaded image: {$filepath}</p>";
            echo "<img src='{$filepath}' style='max-width: 200px; border: 1px solid #ccc; margin: 5px;'>";
            return true;
        } else {
            echo "<p>Error saving image to {$filepath}</p>";
            return false;
        }
    } else {
        echo "<p>Error downloading image from {$url}</p>";
        return false;
    }
}

// Sample image URLs (using placeholder images)
$images = [
    'tomatoes.jpg' => 'https://via.placeholder.com/400x300/ff6666/ffffff?text=Tomatoes',
    'potatoes.jpg' => 'https://via.placeholder.com/400x300/c89664/ffffff?text=Potatoes',
    'milk.jpg' => 'https://via.placeholder.com/400x300/f0f0ff/000000?text=Milk',
    'default.jpg' => 'https://via.placeholder.com/400x300/cccccc/000000?text=Default+Product'
];

// Download each image
echo "<h1>Creating Sample Images</h1>";
$success = true;
foreach ($images as $filename => $url) {
    if (!downloadSampleImage($filename, $url)) {
        $success = false;
    }
}

// If image download failed, create empty files as fallback
if (!$success) {
    echo "<h2>Creating empty image files as fallback:</h2>";
    foreach ($images as $filename => $url) {
        $filepath = $images_dir . $filename;
        if (!file_exists($filepath)) {
            // Create an empty file
            file_put_contents($filepath, '');
            echo "<p>Created empty file: {$filepath}</p>";
        }
    }
}

// Update database with correct image paths
include 'db.php';

// Get all products
$sql = "SELECT id, name FROM products";
$result = $conn->query($sql);

echo "<h2>Updating Product Images in Database:</h2>";
echo "<ul>";

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $image_url = '';
        $name = strtolower($row['name']);
        
        // Assign appropriate image based on product name
        if (strpos($name, 'tomato') !== false) {
            $image_url = 'images/tomatoes.jpg';
        } elseif (strpos($name, 'potato') !== false) {
            $image_url = 'images/potatoes.jpg';
        } elseif (strpos($name, 'milk') !== false) {
            $image_url = 'images/milk.jpg';
        } else {
            $image_url = 'images/default.jpg';
        }
        
        // Update the product
        $update_sql = "UPDATE products SET image_url = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $image_url, $row['id']);
        
        if ($stmt->execute()) {
            echo "<li>Updated product ID {$row['id']} ({$row['name']}) with image: {$image_url}</li>";
        } else {
            echo "<li>Error updating product ID {$row['id']}: {$conn->error}</li>";
        }
    }
} else {
    echo "<li>No products found to update</li>";
}

echo "</ul>";
echo "<p><a href='vendor_dashboard_new.php'>Go to Vendor Dashboard</a></p>";
echo "<p><a href='check_product_images.php'>Check Product Images</a></p>";

$conn->close();
?>