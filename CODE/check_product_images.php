<?php
include 'db.php';

// Get all products with their images
$sql = "SELECT id, name, image_url, vendor_id FROM products ORDER BY id";
$result = $conn->query($sql);

echo "<h2>All Products with Images:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Vendor ID</th><th>Image URL</th><th>Image</th></tr>";

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["id"] . "</td>";
        echo "<td>" . $row["name"] . "</td>";
        echo "<td>" . $row["vendor_id"] . "</td>";
        echo "<td>" . $row["image_url"] . "</td>";
        echo "<td>";
        if (!empty($row["image_url"])) {
            echo "<img src='" . $row["image_url"] . "' style='max-width: 200px; max-height: 200px;'>";
        } else {
            echo "No image";
        }
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No products found</td></tr>";
}
echo "</table>";

// Check if the uploads/products directory exists
$upload_dir = 'uploads/products/';
echo "<h2>Directory Check:</h2>";
if (file_exists($upload_dir)) {
    echo "<p>Directory '{$upload_dir}' exists.</p>";
    
    // List files in the directory
    echo "<h3>Files in {$upload_dir}:</h3>";
    echo "<ul>";
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>{$file} - " . filesize($upload_dir . $file) . " bytes</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>Directory '{$upload_dir}' does not exist.</p>";
}

// Check for any hardcoded image paths in vendor_dashboard.php
echo "<h2>Checking for Hardcoded Image Paths:</h2>";
$dashboard_file = file_get_contents('vendor_dashboard.php');

// Look for image URLs
$pattern = '/img src="([^"]+)"/i';
preg_match_all($pattern, $dashboard_file, $matches);

if (!empty($matches[1])) {
    echo "<p>Found " . count($matches[1]) . " image references in vendor_dashboard.php:</p>";
    echo "<ul>";
    foreach ($matches[1] as $match) {
        echo "<li>{$match}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No hardcoded image paths found in vendor_dashboard.php</p>";
}

$conn->close();
?>