<?php
// Suppress error display and capture errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any accidental output
ob_start();

// Suppress any output from included files
@require 'db.php';
@session_start();

// Clear any output that might have been generated (warnings, notices, etc.)
$output = ob_get_clean();
if (!empty($output) && strpos($output, '{') === false) {
    // If output doesn't start with JSON, it's likely an error - log it
    error_log("Unexpected output before JSON: " . substr($output, 0, 200));
}
ob_start(); // Start fresh buffer for JSON response

header('Content-Type: application/json');

// Load PHPMailer if available (use fully qualified names to avoid use statement issues)
if (file_exists('vendor/autoload.php')) {
    @require_once 'vendor/autoload.php';
}

// Get cart data from request
$data = json_decode(file_get_contents('php://input'), true);
$cart = $data['cart'] ?? [];

// Get user ID from session (fallback to 1 for testing)
$user_id = $_SESSION['user_id'] ?? 1; // Replace with real user/session logic
$user_email = $_SESSION['email'] ?? ''; // Get user email from session

// Start transaction
$conn->begin_transaction();

try {
    // Group items by vendor
    $vendor_orders = [];
    
    // Calculate total and organize items by vendor
    $total = 0;
    foreach ($cart as $item) {
        $product_id = (int)$item['id'];
        $quantity = (int)$item['qty'];
        $price = (float)$item['price'];
        $vendor_id = isset($item['vendor_id']) ? (int)$item['vendor_id'] : 0;
        
        // Check if product exists before proceeding
        $check_product = "SELECT id, name, COALESCE(stock_quantity, 0) as stock, vendor_id FROM products WHERE id = ?";
        $check_stmt = $conn->prepare($check_product);
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $product_result = $check_stmt->get_result();
        
        if (!$product_result || $product_result->num_rows == 0) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => "Product with ID {$product_id} not found"
            ]);
            exit;
        }
        
        $product_data = $product_result->fetch_assoc();
        // Use the vendor_id from the database if not provided in the cart
        if ($vendor_id == 0 && !empty($product_data['vendor_id'])) {
            $vendor_id = (int)$product_data['vendor_id'];
        }
        
        $item_total = $price * $quantity;
        $total += $item_total;
        
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
            'price' => $price,
            'name' => $item['name']
        ];
        
        $vendor_orders[$vendor_id]['total'] += $item_total;
        
        // Check if product has enough stock
        $current_stock = (int)$product_data['stock'];
        
        if ($current_stock < $quantity) {
            // Return a user-friendly error
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => "Not enough stock for product {$item['name']}. Available: {$current_stock}, Requested: {$quantity}",
                'product_id' => $product_id,
                'available_stock' => $current_stock
            ]);
            exit;
        }
        
        // Update stock
        $new_stock = $current_stock - $quantity;
        $update_stock = "UPDATE products SET stock_quantity = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_stock);
        $update_stmt->bind_param("ii", $new_stock, $product_id);
        $update_stmt->execute();
    } // ✅ End of foreach loop

    // ✅ Removed extra closing brace here

    // Create orders for each vendor
    $created_orders = [];
    foreach ($vendor_orders as $vendor_id => $vendor_order) {
        // Check if vendor_id column exists in orders table
        $check_columns = $conn->query("SHOW COLUMNS FROM orders LIKE 'vendor_id'");
        $has_vendor_id = $check_columns && $check_columns->num_rows > 0;
        
        // Insert order - use vendor_id if column exists, otherwise omit it
        // Always set status to 'pending' for new orders
        if ($has_vendor_id) {
            $stmt = $conn->prepare("INSERT INTO orders (user_id, vendor_id, total_amount, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("iid", $user_id, $vendor_id, $vendor_order['total']);
        } else {
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, 'pending', NOW())");
            $stmt->bind_param("id", $user_id, $vendor_order['total']);
        }
        
        // Ensure status is never NULL - set default if needed
        if (!$stmt) {
            throw new Exception("Failed to prepare order insert: " . $conn->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create order: " . $stmt->error);
        }
        $order_id = $stmt->insert_id;
        
        $created_orders[] = [
            'order_id' => $order_id,
            'vendor_id' => $vendor_id,
            'total' => $vendor_order['total'],
            'items' => $vendor_order['items']
        ];

        // Insert order items
        foreach ($vendor_order['items'] as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
    }

    // Get shopkeeper information
    $shopkeeper_info = [];
    $res = $conn->prepare("SELECT email, first_name, last_name, phone, shop_name FROM users WHERE id = ?");
    $res->bind_param("i", $user_id);
    $res->execute();
    $result = $res->get_result();
    if ($row = $result->fetch_assoc()) {
        $shopkeeper_info = $row;
    }

    // Get vendor information - try vendors table first, then users table
    $vendor_info = [];
    if (!empty($vendor_orders)) {
        $vendor_ids = array_keys($vendor_orders);

        // Filter out zero/null vendor IDs
        $valid_vendor_ids = array_filter($vendor_ids, function($id) { return $id > 0; });

        if (count($valid_vendor_ids) > 0) {
            $placeholders = str_repeat('?,', count($valid_vendor_ids) - 1) . '?';
            
            // Try vendors table first
            $stmt = $conn->prepare("SELECT id, name, email, phone, address, city, state FROM vendors WHERE id IN ($placeholders)");
            if ($stmt) {
            $types = str_repeat('i', count($valid_vendor_ids));
            $stmt->bind_param($types, ...$valid_vendor_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $vendor_info[$row['id']] = $row;
                }
                $stmt->close();
            }
            
            // If vendors table doesn't have all vendors, try users table
            $missing_ids = array_diff($valid_vendor_ids, array_keys($vendor_info));
            if (count($missing_ids) > 0) {
                $placeholders2 = str_repeat('?,', count($missing_ids) - 1) . '?';
                $stmt2 = $conn->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name, email, phone, address, city, state FROM users WHERE id IN ($placeholders2) AND role = 'vendor'");
                if ($stmt2) {
                    $types2 = str_repeat('i', count($missing_ids));
                    $stmt2->bind_param($types2, ...$missing_ids);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    while ($row = $result2->fetch_assoc()) {
                        $vendor_info[$row['id']] = $row;
                    }
                    $stmt2->close();
                }
            }
        }
    }

    // Commit transaction
    $conn->commit();

    // Prepare response
    $response = [
        'success' => true,
        'orders' => $created_orders,
        'shopkeeper' => $shopkeeper_info,
        'vendors' => $vendor_info,
        'total_amount' => $total,
        'message' => "Order placed successfully!"
    ];
    
    // Clean any output and send JSON response
    ob_clean();
    echo json_encode($response);
    ob_end_flush();

    // Send notification emails using PHPMailer (best-effort; do not block response)
    // This runs after the response is sent
    
    $email_errors = [];
    try {
        // Check if PHPMailer is available
        if (!file_exists('vendor/autoload.php')) {
            error_log("PHPMailer not found. Emails will not be sent.");
        } else {
            // Load autoloader first
            @require_once 'vendor/autoload.php';
            
            // Check if class exists after loading
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                error_log("PHPMailer class not found after loading autoloader.");
            } else {
                // Use fully qualified class names
                $PHPMailer = 'PHPMailer\PHPMailer\PHPMailer';
                $Exception = 'PHPMailer\PHPMailer\Exception';
            
            // Load email configuration
            $email_config = [];
            if (file_exists('email_config.php')) {
                $email_config = require 'email_config.php';
            } else {
                // Default configuration (update these with your SMTP settings)
                $email_config = [
                    'smtp_host' => 'smtp.gmail.com',
                    'smtp_port' => 587,
                    'smtp_username' => 'your-email@gmail.com',  // UPDATE THIS
                    'smtp_password' => 'your-app-password',      // UPDATE THIS
                    'smtp_encryption' => 'tls',
                    'from_email' => 'noreply@marketplace.com',
                    'from_name' => 'Marketplace AI',
                ];
            }
            
            // Get base URL for links
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $base_url = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
            $base_url = rtrim($base_url, '/');
            
                // Send email to shopkeeper for each order
                if (!empty($shopkeeper_info['email']) && !empty($created_orders)) {
                    foreach ($created_orders as $order_data) {
                        $order_id = $order_data['order_id'];
                        $vendor_id = $order_data['vendor_id'];
                        $vendor = $vendor_info[$vendor_id] ?? null;
                        
                        if ($vendor) {
                            $mail = new $PHPMailer(true);
                            try {
                                // Server settings
                                $mail->isSMTP();
                                $mail->Host = $email_config['smtp_host'];
                                $mail->SMTPAuth = true;
                                $mail->Username = $email_config['smtp_username'];
                                $mail->Password = $email_config['smtp_password'];
                                // Use constant values directly
                                if ($email_config['smtp_encryption'] === 'ssl') {
                                    $mail->SMTPSecure = 2; // PHPMailer::ENCRYPTION_SMTPS
                                } else {
                                    $mail->SMTPSecure = 1; // PHPMailer::ENCRYPTION_STARTTLS
                                }
                        $mail->Port = $email_config['smtp_port'];
                        $mail->CharSet = 'UTF-8';
                        
                        // Recipients
                        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
                        $mail->addAddress($shopkeeper_info['email'], $shopkeeper_info['first_name'] . ' ' . $shopkeeper_info['last_name']);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Order #' . $order_id . ' Confirmation - Marketplace AI';
                        
                        // Build order items list
                        $items_html = '';
                        foreach ($order_data['items'] as $item) {
                            $items_html .= '<tr>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['name']) . '</td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: center;">' . $item['quantity'] . '</td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: right;">₹' . number_format($item['price'], 2) . '</td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: right;">₹' . number_format($item['price'] * $item['quantity'], 2) . '</td>
                            </tr>';
                        }
                        
                        $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                                .content { background-color: #f9f9f9; padding: 20px; }
                                .order-info { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                                .vendor-info { background-color: #e8f5e9; padding: 15px; margin: 15px 0; border-radius: 5px; }
                                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                                th { background-color: #4CAF50; color: white; padding: 10px; text-align: left; }
                                .button { display: inline-block; padding: 12px 24px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <div class="header">
                                    <h1>Order Confirmation</h1>
                                </div>
                                <div class="content">
                                    <p>Dear ' . htmlspecialchars($shopkeeper_info['first_name']) . ',</p>
                                    <p>Thank you for your order! Your order has been confirmed and will be processed shortly.</p>
                                    
                                    <div class="order-info">
                                        <h3>Order Details</h3>
                                        <p><strong>Order ID:</strong> #' . $order_id . '</p>
                                        <p><strong>Order Date:</strong> ' . date('F j, Y g:i A') . '</p>
                                        <p><strong>Total Amount:</strong> ₹' . number_format($order_data['total'], 2) . '</p>
                                        <p><strong>Status:</strong> Pending</p>
                                    </div>
                                    
                                    <div class="vendor-info">
                                        <h3>Vendor Information</h3>
                                        <p><strong>Vendor Name:</strong> ' . htmlspecialchars($vendor['name']) . '</p>
                                        <p><strong>Email:</strong> ' . htmlspecialchars($vendor['email']) . '</p>
                                        ' . (!empty($vendor['phone']) ? '<p><strong>Phone:</strong> ' . htmlspecialchars($vendor['phone']) . '</p>' : '') . '
                                        ' . (!empty($vendor['address']) ? '<p><strong>Address:</strong> ' . htmlspecialchars($vendor['address']) . '</p>' : '') . '
                                        ' . (!empty($vendor['city']) ? '<p><strong>City:</strong> ' . htmlspecialchars($vendor['city']) . '</p>' : '') . '
                                        ' . (!empty($vendor['state']) ? '<p><strong>State:</strong> ' . htmlspecialchars($vendor['state']) . '</p>' : '') . '
                                    </div>
                                    
                                    <h3>Order Items</h3>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th style="text-align: center;">Quantity</th>
                                                <th style="text-align: right;">Price</th>
                                                <th style="text-align: right;">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ' . $items_html . '
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" style="text-align: right; padding: 10px; font-weight: bold;">Total:</td>
                                                <td style="text-align: right; padding: 10px; font-weight: bold;">₹' . number_format($order_data['total'], 2) . '</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    
                                    <p style="text-align: center;">
                                        <a href="' . $base_url . '/view_order.php?id=' . $order_id . '" class="button">Track Your Order</a>
                                    </p>
                                    
                                    <p>If you have any questions, please contact our support team.</p>
                                    <p>Best regards,<br>Marketplace AI Team</p>
                                </div>
                            </div>
                        </body>
                        </html>';
                        
                                $mail->send();
                            } catch (Exception $e) {
                                $email_errors[] = "Shopkeeper email failed: " . $mail->ErrorInfo;
                            }
                        }
                    }
                }
                
                // Send email to each vendor
                foreach ($created_orders as $order_data) {
                    $order_id = $order_data['order_id'];
                    $vendor_id = $order_data['vendor_id'];
                    $vendor = $vendor_info[$vendor_id] ?? null;
                    
                    if ($vendor && !empty($vendor['email'])) {
                        $mail = new $PHPMailer(true);
                        try {
                            // Server settings
                            $mail->isSMTP();
                            $mail->Host = $email_config['smtp_host'];
                            $mail->SMTPAuth = true;
                            $mail->Username = $email_config['smtp_username'];
                            $mail->Password = $email_config['smtp_password'];
                            // Use constant values directly
                            if ($email_config['smtp_encryption'] === 'ssl') {
                                $mail->SMTPSecure = 2; // PHPMailer::ENCRYPTION_SMTPS
                            } else {
                                $mail->SMTPSecure = 1; // PHPMailer::ENCRYPTION_STARTTLS
                            }
                            $mail->Port = $email_config['smtp_port'];
                            $mail->CharSet = 'UTF-8';
                            
                            // Recipients
                            $mail->setFrom($email_config['from_email'], $email_config['from_name']);
                            $mail->addAddress($vendor['email'], $vendor['name']);
                            
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'New Order #' . $order_id . ' Received - Marketplace AI';
                            
                            // Build order items list
                            $items_html = '';
                            foreach ($order_data['items'] as $item) {
                                $items_html .= '<tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['name']) . '</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: center;">' . $item['quantity'] . '</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: right;">₹' . number_format($item['price'], 2) . '</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: right;">₹' . number_format($item['price'] * $item['quantity'], 2) . '</td>
                                </tr>';
                            }
                            
                            $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                            .content { background-color: #f9f9f9; padding: 20px; }
                            .order-info { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                            .shopkeeper-info { background-color: #e3f2fd; padding: 15px; margin: 15px 0; border-radius: 5px; }
                            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                            th { background-color: #4CAF50; color: white; padding: 10px; text-align: left; }
                            .button { display: inline-block; padding: 12px 24px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>New Order Received</h1>
                            </div>
                            <div class="content">
                                <p>Dear ' . htmlspecialchars($vendor['name']) . ',</p>
                                <p>You have received a new order! Please review the details below and process the order.</p>
                                
                                <div class="order-info">
                                    <h3>Order Details</h3>
                                    <p><strong>Order ID:</strong> #' . $order_id . '</p>
                                    <p><strong>Order Date:</strong> ' . date('F j, Y g:i A') . '</p>
                                    <p><strong>Total Amount:</strong> ₹' . number_format($order_data['total'], 2) . '</p>
                                    <p><strong>Status:</strong> Pending</p>
                                </div>
                                
                                <div class="shopkeeper-info">
                                    <h3>Shopkeeper Information</h3>
                                    <p><strong>Name:</strong> ' . htmlspecialchars($shopkeeper_info['first_name'] . ' ' . $shopkeeper_info['last_name']) . '</p>
                                    <p><strong>Business Name:</strong> ' . htmlspecialchars($shopkeeper_info['shop_name'] ?? 'N/A') . '</p>
                                    <p><strong>Email:</strong> ' . htmlspecialchars($shopkeeper_info['email']) . '</p>
                                    <p><strong>Phone:</strong> ' . htmlspecialchars($shopkeeper_info['phone'] ?? 'N/A') . '</p>
                                </div>
                                
                                <h3>Order Items</h3>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th style="text-align: center;">Quantity</th>
                                            <th style="text-align: right;">Price</th>
                                            <th style="text-align: right;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ' . $items_html . '
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" style="text-align: right; padding: 10px; font-weight: bold;">Total:</td>
                                            <td style="text-align: right; padding: 10px; font-weight: bold;">₹' . number_format($order_data['total'], 2) . '</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                
                                <p style="text-align: center;">
                                    <a href="' . $base_url . '/shopkeeper_details.php?id=' . $user_id . '&order_id=' . $order_id . '" class="button">View Order Details</a>
                                </p>
                                
                                <p>Please process this order as soon as possible.</p>
                                <p>Best regards,<br>Marketplace AI Team</p>
                            </div>
                        </div>
                    </body>
                    </html>';
                    
                            $mail->send();
                        } catch (Exception $e) {
                            $email_errors[] = "Vendor email failed for order #{$order_id}: " . $mail->ErrorInfo;
                        }
                    }
                }
                
                // Log email errors if any
                if (!empty($email_errors)) {
                    error_log("Email sending errors: " . implode("; ", $email_errors));
                }
            }
        }
    } catch (Exception $e) {
        // Log error but don't fail the order
        error_log("Email system error: " . $e->getMessage());
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Return error response with more details
    $error_message = $e->getMessage();
    $error_trace = $e->getTraceAsString();

    // Log the error
    error_log("Checkout Error: " . $error_message . "\n" . $error_trace);

    // Clean any output and send JSON error response
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => "An error occurred during checkout. Please try again.",
        'error_details' => $error_message,
        'sql_error' => $conn->error ?? 'No SQL error'
    ]);
    ob_end_flush();
}

$conn->close();
?>

