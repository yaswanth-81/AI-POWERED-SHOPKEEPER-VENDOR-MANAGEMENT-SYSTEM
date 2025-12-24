<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Check if user is logged in as shopkeeper
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shopkeeper') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the JSON data from the request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data || !isset($data['cart']) || empty($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    $shopkeeper_id = $_SESSION['user_id'];
    $total_amount = 0;
    $cart_items = $data['cart'];
    $vendor_orders = [];
    
    // Calculate total amount and organize items by vendor
    foreach ($cart_items as $item) {
        $product_id = (int)$item['id'];
        $quantity = (int)$item['qty'];
        $price = (float)$item['price'];
        $vendor_id = (int)$item['vendor_id'];
        
        $item_total = $price * $quantity;
        $total_amount += $item_total;
        
        // Group items by vendor
        if (!isset($vendor_orders[$vendor_id])) {
            $vendor_orders[$vendor_id] = [
                'items' => [],
                'total' => 0
            ];
        }
        
        $vendor_orders[$vendor_id]['items'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $price
        ];
        
        $vendor_orders[$vendor_id]['total'] += $item_total;
        
        // Check if product has enough stock
        $check_stock = "SELECT COALESCE(stock_quantity, 0) as stock FROM products WHERE id = {$product_id}";
        $stock_result = $conn->query($check_stock);
        
        if ($stock_result && $stock_result->num_rows > 0) {
            $current_stock = (int)$stock_result->fetch_assoc()['stock'];
            
            if ($current_stock < $quantity) {
                throw new Exception("Not enough stock for product ID {$product_id}. Available: {$current_stock}, Requested: {$quantity}");
            }
            
            // Update stock
            $new_stock = $current_stock - $quantity;
            $update_stock = "UPDATE products SET stock_quantity = {$new_stock} WHERE id = {$product_id}";
            $conn->query($update_stock);
        } else {
            throw new Exception("Product ID {$product_id} not found");
        }
    }
    
    // Create orders for each vendor
    foreach ($vendor_orders as $vendor_id => $vendor_order) {
        // Create order
        $insert_order = "INSERT INTO orders (user_id, vendor_id, total_amount, status, created_at) 
                         VALUES ({$shopkeeper_id}, {$vendor_id}, {$vendor_order['total']}, 'pending', NOW())";
        $conn->query($insert_order);
        $order_id = $conn->insert_id;
        
        // Insert order items
        foreach ($vendor_order['items'] as $item) {
            $insert_item = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                            VALUES ({$order_id}, {$item['product_id']}, {$item['quantity']}, {$item['price']})";
            $conn->query($insert_item);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully', 
        'order_count' => count($vendor_orders),
        'total_amount' => $total_amount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>