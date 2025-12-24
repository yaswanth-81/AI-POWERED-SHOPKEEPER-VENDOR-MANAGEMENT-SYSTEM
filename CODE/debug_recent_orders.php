<?php
// Debug script to check why recent orders are not showing
require 'db.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    echo "<p>Please log in as a vendor first.</p>";
    exit();
}

$vendor_id = $_SESSION['user_id'];

echo "<h1>Recent Orders Debug</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
</style>";

echo "<h2>1. Vendor Information</h2>";
echo "<p><strong>Vendor ID:</strong> $vendor_id</p>";
echo "<p><strong>Vendor Name:</strong> " . $_SESSION['first_name'] . " " . $_SESSION['last_name'] . "</p>";

// Check if vendor has products
echo "<h2>2. Vendor's Products</h2>";
$products_query = "SELECT id, name, price FROM products WHERE vendor_id = ?";
$stmt = $conn->prepare($products_query);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$products_result = $stmt->get_result();

if ($products_result->num_rows > 0) {
    echo "<p class='success'>✓ Vendor has " . $products_result->num_rows . " products</p>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f3f4f6;'>";
    echo "<th style='padding: 8px;'>ID</th>";
    echo "<th style='padding: 8px;'>Name</th>";
    echo "<th style='padding: 8px;'>Price</th>";
    echo "</tr>";
    
    while ($product = $products_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $product['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $product['name'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>₹" . $product['price'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ Vendor has no products</p>";
    echo "<p>This is why no recent orders are showing. Orders are only shown for vendors who have products.</p>";
}

$stmt->close();

// Check all orders in system
echo "<h2>3. All Orders in System</h2>";
$all_orders = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $all_orders->fetch_assoc()['total_orders'];

if ($total_orders > 0) {
    echo "<p class='info'>ℹ Total orders in system: $total_orders</p>";
    
    // Show sample orders
    $sample_orders = $conn->query("SELECT o.id, o.total_amount, o.status, o.created_at, u.first_name, u.last_name 
                                   FROM orders o 
                                   JOIN users u ON o.user_id = u.id 
                                   ORDER BY o.created_at DESC 
                                   LIMIT 5");
    
    echo "<p>Sample orders:</p>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f3f4f6;'>";
    echo "<th style='padding: 8px;'>Order ID</th>";
    echo "<th style='padding: 8px;'>Customer</th>";
    echo "<th style='padding: 8px;'>Amount</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "<th style='padding: 8px;'>Date</th>";
    echo "</tr>";
    
    while ($order = $sample_orders->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $order['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $order['first_name'] . " " . $order['last_name'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>₹" . $order['total_amount'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $order['status'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $order['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No orders found in the system</p>";
}

// Check order_items
echo "<h2>4. Order Items Analysis</h2>";
$order_items_count = $conn->query("SELECT COUNT(*) as total_items FROM order_items");
$total_items = $order_items_count->fetch_assoc()['total_items'];

if ($total_items > 0) {
    echo "<p class='info'>ℹ Total order items: $total_items</p>";
    
    // Check if any order items are for this vendor's products
    $vendor_order_items = $conn->prepare("SELECT COUNT(*) as vendor_items 
                                         FROM order_items oi 
                                         JOIN products p ON oi.product_id = p.id 
                                         WHERE p.vendor_id = ?");
    $vendor_order_items->bind_param("i", $vendor_id);
    $vendor_order_items->execute();
    $vendor_items_result = $vendor_order_items->get_result();
    $vendor_items_count = $vendor_items_result->fetch_assoc()['vendor_items'];
    
    if ($vendor_items_count > 0) {
        echo "<p class='success'>✓ Found $vendor_items_count order items for this vendor's products</p>";
        
        // Show the actual orders for this vendor
        echo "<h2>5. Orders for This Vendor's Products</h2>";
        $vendor_orders = $conn->prepare("SELECT DISTINCT o.id, o.total_amount, o.status, o.created_at, u.first_name, u.last_name, u.email
                                        FROM orders o
                                        JOIN users u ON o.user_id = u.id
                                        JOIN order_items oi ON o.id = oi.order_id
                                        JOIN products p ON oi.product_id = p.id
                                        WHERE p.vendor_id = ?
                                        ORDER BY o.created_at DESC");
        $vendor_orders->bind_param("i", $vendor_id);
        $vendor_orders->execute();
        $vendor_orders_result = $vendor_orders->get_result();
        
        if ($vendor_orders_result->num_rows > 0) {
            echo "<p class='success'>✓ Found " . $vendor_orders_result->num_rows . " orders for this vendor</p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background-color: #f3f4f6;'>";
            echo "<th style='padding: 8px;'>Order ID</th>";
            echo "<th style='padding: 8px;'>Customer</th>";
            echo "<th style='padding: 8px;'>Amount</th>";
            echo "<th style='padding: 8px;'>Status</th>";
            echo "<th style='padding: 8px;'>Date</th>";
            echo "</tr>";
            
            while ($order = $vendor_orders_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $order['id'] . "</td>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $order['first_name'] . " " . $order['last_name'] . "</td>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>$" . $order['total_amount'] . "</td>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $order['status'] . "</td>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $order['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ No orders found for this vendor's products</p>";
        }
    } else {
        echo "<p class='warning'>⚠ No order items found for this vendor's products</p>";
    }
} else {
    echo "<p class='warning'>⚠ No order items found in the system</p>";
}

$conn->close();

echo "<h2>6. Summary</h2>";
echo "<p>The recent orders section will show orders only if:</p>";
echo "<ul>";
echo "<li>✓ The vendor has products in the database</li>";
echo "<li>✓ There are orders in the system</li>";
echo "<li>✓ Those orders contain items from the vendor's products</li>";
echo "</ul>";

echo "<h2>7. Next Steps</h2>";
echo "<ul>";
echo "<li><a href='vendor_dashboard.php'>Test Vendor Dashboard</a></li>";
echo "<li><a href='add_product.php'>Add Products</a> (if vendor has no products)</li>";
echo "<li><a href='create_test_order.php'>Create Test Order</a> (if no orders exist)</li>";
echo "</ul>";
?>
