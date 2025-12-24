<?php
include 'db.php';

// Check if orders table exists
$result = $conn->query("SHOW TABLES LIKE 'orders'");

if ($result->num_rows == 0) {
    echo "<p>Orders table does not exist. Creating it now...</p>";
    
    // Create orders table
    $sql = "CREATE TABLE orders (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        vendor_id INT(11) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Orders table created successfully!</p>";
    } else {
        echo "<p>Error creating orders table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Orders table already exists.</p>";
}

// Check if order_items table exists
$result = $conn->query("SHOW TABLES LIKE 'order_items'");

if ($result->num_rows == 0) {
    echo "<p>Order items table does not exist. Creating it now...</p>";
    
    // Create order_items table
    $sql = "CREATE TABLE order_items (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        order_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Order items table created successfully!</p>";
    } else {
        echo "<p>Error creating order items table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Order items table already exists.</p>";
}

// Insert sample order data if no orders exist
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo "<p>No orders found. Adding sample orders...</p>";
    
    // Get a sample user (customer)
    $user_result = $conn->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1");
    if ($user_result->num_rows == 0) {
        echo "<p>No customers found. Please create a customer account first.</p>";
    } else {
        $user = $user_result->fetch_assoc();
        $user_id = $user['id'];
        
        // Get a sample vendor
        $vendor_result = $conn->query("SELECT id FROM users WHERE role = 'vendor' LIMIT 1");
        if ($vendor_result->num_rows == 0) {
            echo "<p>No vendors found. Please create a vendor account first.</p>";
        } else {
            $vendor = $vendor_result->fetch_assoc();
            $vendor_id = $vendor['id'];
            
            // Get sample products from this vendor
            $products_result = $conn->query("SELECT id, price FROM products LIMIT 3");
            if ($products_result->num_rows == 0) {
                echo "<p>No products found. Please add products first.</p>";
            } else {
                // Create a sample order
                $total_amount = 0;
                $products = [];
                
                while ($product = $products_result->fetch_assoc()) {
                    $quantity = rand(1, 5);
                    $price = $product['price'];
                    $total_amount += $price * $quantity;
                    
                    $products[] = [
                        'id' => $product['id'],
                        'price' => $price,
                        'quantity' => $quantity
                    ];
                }
                
                // Insert order
                $order_sql = "INSERT INTO orders (user_id, vendor_id, total_amount, status) VALUES (?, ?, ?, 'Pending')";
                $order_stmt = $conn->prepare($order_sql);
                $order_stmt->bind_param("iid", $user_id, $vendor_id, $total_amount);
                
                if ($order_stmt->execute()) {
                    $order_id = $conn->insert_id;
                    echo "<p>Sample order created with ID: $order_id</p>";
                    
                    // Insert order items
                    foreach ($products as $product) {
                        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                        $item_stmt = $conn->prepare($item_sql);
                        $item_stmt->bind_param("iiid", $order_id, $product['id'], $product['quantity'], $product['price']);
                        $item_stmt->execute();
                    }
                    
                    echo "<p>Sample order items added.</p>";
                } else {
                    echo "<p>Error creating sample order: " . $conn->error . "</p>";
                }
            }
        }
    }
} else {
    echo "<p>Orders already exist in the database.</p>";
}

echo "<p><a href='vendor_dashboard.php'>Return to Vendor Dashboard</a></p>";

$conn->close();
?>