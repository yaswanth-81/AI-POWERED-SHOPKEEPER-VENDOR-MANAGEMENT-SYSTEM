<?php
// This script directly tests the checkout.php file with a missing vendor_id

// Include database connection
require 'db.php';

// Create a test cart with missing vendor_id
$cart = [
    [
        'id' => 1, // Product ID
        'name' => 'Fresh Tomatoes',
        'price' => 1.99,
        'qty' => 1
        // No vendor_id provided
    ]
];

// Set up curl to make a request to the local checkout.php
$ch = curl_init('http://localhost:8000/checkout.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['cart' => $cart]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

// Execute the request
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Display the results
echo "<h1>Direct Test of Checkout.php (Missing Vendor ID)</h1>";
echo "<h2>Response:</h2>";
echo "<p>Status Code: $status</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Parse the JSON response
$data = json_decode($response, true);

if ($data) {
    echo "<h3>Parsed Response:</h3>";
    echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
    
    // Check if the order was successful
    if (isset($data['success']) && $data['success']) {
        echo "<div style='color: green; font-weight: bold;'>Order placed successfully!</div>";
        
        // Check the database for the order
        echo "<h3>Database Check:</h3>";
        
        // Get the latest order
        $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 1");
        
        if ($result && $result->num_rows > 0) {
            $order = $result->fetch_assoc();
            echo "<p>Latest order found in database:</p>";
            echo "<pre>" . htmlspecialchars(print_r($order, true)) . "</pre>";
            
            // Check order items
            $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->bind_param("i", $order['id']);
            $stmt->execute();
            $items_result = $stmt->get_result();
            
            if ($items_result && $items_result->num_rows > 0) {
                echo "<p>Order items found:</p>";
                $items = [];
                while ($item = $items_result->fetch_assoc()) {
                    $items[] = $item;
                }
                echo "<pre>" . htmlspecialchars(print_r($items, true)) . "</pre>";
            } else {
                echo "<p>No order items found for this order.</p>";
            }
        } else {
            echo "<p>No order found in database.</p>";
        }
    } else {
        echo "<div style='color: red; font-weight: bold;'>Error: " . ($data['message'] ?? 'Unknown error') . "</div>";
    }
} else {
    echo "<div style='color: red; font-weight: bold;'>Error: Invalid JSON response</div>";
}

$conn->close();
?>