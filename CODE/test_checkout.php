<?php
require 'db.php';
session_start();

// Simulate a user session
$_SESSION['user_id'] = 5; // Use an existing user ID
$_SESSION['email'] = 'test@example.com';

// Simulate a cart with test data
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

// Create a test request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Capture output
ob_start();

// Mock the input stream
$input = json_encode(['cart' => $cart]);

// Create a temporary file to simulate php://input
$tempfile = tempnam(sys_get_temp_dir(), 'checkout_test');
file_put_contents($tempfile, $input);

// Override php://input with our test data
define('STDIN', fopen($tempfile, 'r'));

// Include the checkout script but capture its output without executing it directly
$checkout_content = file_get_contents('checkout.php');

// Modify the checkout content to prevent it from closing the database connection
$modified_checkout = str_replace('$conn->close();', '// $conn->close(); // Commented out to keep connection open', $checkout_content);

// Save to a temporary file
$temp_checkout = tempnam(sys_get_temp_dir(), 'modified_checkout');
file_put_contents($temp_checkout, $modified_checkout);

// Include the modified checkout script
include $temp_checkout;

// Get the output
$output = ob_get_clean();

// Clean up
unlink($tempfile);
unlink($temp_checkout);

// Display the results
echo "<h2>Test Checkout Results</h2>";
echo "<h3>Input Data:</h3>";
echo "<pre>" . htmlspecialchars(print_r($cart, true)) . "</pre>";

echo "<h3>Output Response:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Parse the JSON response
$response = json_decode($output, true);

echo "<h3>Parsed Response:</h3>";
echo "<pre>" . htmlspecialchars(print_r($response, true)) . "</pre>";

// Check if the order was created in the database
echo "<h3>Database Check:</h3>";

// Create a new database connection for our tests
$test_conn = new mysqli($host, $user, $pass, $db);

// Get the latest order for this user
$stmt = $test_conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo "<p>Order found in database:</p>";
    echo "<pre>" . htmlspecialchars(print_r($order, true)) . "</pre>";
    
    // Check order items
    $stmt = $test_conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
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

$test_conn->close();
?>