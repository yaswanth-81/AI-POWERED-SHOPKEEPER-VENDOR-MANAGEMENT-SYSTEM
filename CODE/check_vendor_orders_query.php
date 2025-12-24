<?php
include 'db.php';

// Set a test vendor ID
$vendor_id = 1;

// Test the query from vendor_orders.php
$sql = "SELECT o.*, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email, u.phone, u.address, u.city, u.state, u.postal_code, u.country, u.shop_name
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.vendor_id = ? 
        ORDER BY o.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<p>Query executed successfully!</p>";
    
    // Show the number of results
    echo "<p>Found " . $result->num_rows . " orders</p>";
    
    // Show the first result if available
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>Error executing query: " . $e->getMessage() . "</p>";
}

$conn->close();
?>