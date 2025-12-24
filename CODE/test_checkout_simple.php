<?php
// Include database connection
require 'db.php';

// Create a simple form to test the checkout process
echo "<h1>Test Checkout Process</h1>";

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a test cart
    $cart = [
        [
            'id' => 1, // Product ID
            'name' => 'Test Product 1',
            'price' => 1.99,
            'qty' => 1,
            'vendor_id' => 1 // Vendor ID
        ],
        [
            'id' => 2, // Product ID
            'name' => 'Test Product 2',
            'price' => 2.49,
            'qty' => 1,
            'vendor_id' => 1 // Same vendor
        ]
    ];
    
    // Set up session
    session_start();
    $_SESSION['user_id'] = 5; // Use an existing user ID
    $_SESSION['email'] = 'test@example.com';
    
    // Make a POST request to checkout.php
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
    echo "<h2>Checkout Response</h2>";
    echo "<p>Status Code: $status</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Parse the JSON response
    $data = json_decode($response, true);
    
    if ($data) {
        echo "<h3>Parsed Response:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
        
        // Check if the order was successful
        if ($data['success']) {
            echo "<div style='color: green; font-weight: bold;'>Order placed successfully!</div>";
            
            // Check the database for the order
            echo "<h3>Database Check:</h3>";
            
            // Get the latest order for this user
            $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $order = $result->fetch_assoc();
                echo "<p>Order found in database:</p>";
                echo "<pre>" . htmlspecialchars(print_r($order, true)) . "</pre>";
                
                // Check order items
                $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $stmt->bind_param("i", $order['id']);
                $stmt->execute();
                $items_result = $stmt->get_result();
                
                if ($items_result->num_rows > 0) {
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
                echo "<p>No order found in database for user ID {$_SESSION['user_id']}.</p>";
            }
        } else {
            echo "<div style='color: red; font-weight: bold;'>Error: {$data['message']}</div>";
        }
    } else {
        echo "<div style='color: red; font-weight: bold;'>Error: Invalid JSON response</div>";
    }
} else {
    // Display the form
    echo "<form method='post' action=''>";
    echo "<p>Click the button below to test the checkout process:</p>";
    echo "<button type='submit' style='padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer;'>Test Checkout</button>";
    echo "</form>";
}

$conn->close();
?>