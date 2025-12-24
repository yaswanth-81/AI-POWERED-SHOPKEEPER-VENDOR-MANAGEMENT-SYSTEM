<?php
include 'db.php';

echo "<h1>Update Stock Values for Products</h1>";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['stock'] as $product_id => $stock_value) {
        $stock = intval($stock_value);
        $id = intval($product_id);
        
        $sql = "UPDATE products SET stock = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $stock, $id);
        
        if ($stmt->execute()) {
            echo "<p>Updated stock for product ID $id to $stock</p>";
        } else {
            echo "<p>Error updating stock for product ID $id: " . $conn->error . "</p>";
        }
    }
    
    echo "<p>Stock values updated successfully!</p>";
}

// Display current products with form to update stock
$result = $conn->query("SELECT id, name, stock, category FROM products ORDER BY name");

if ($result->num_rows > 0) {
    echo "<form method='post' action=''>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Current Stock</th><th>New Stock</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . $row['stock'] . "</td>";
        echo "<td><input type='number' name='stock[" . $row['id'] . "]' value='" . $row['stock'] . "' min='0'></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><input type='submit' value='Update Stock Values'></p>";
    echo "</form>";
    
echo "<p><a href='vendor_dashboard.php'>Return to Vendor Dashboard</a></p>";
} else {
    echo "<p>No products found.</p>";
}

$conn->close();
?>