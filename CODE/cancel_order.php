<?php
session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login page.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["order_id"])) {
    $order_id = intval($_POST["order_id"]);
    $user_id = $_SESSION["user_id"];
    $user_role = $_SESSION["role"] ?? '';
    
    // Get current order status and verify ownership
    $check_sql = "SELECT id, status, user_id, vendor_id FROM orders WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        $_SESSION["error_message"] = "Error preparing query: " . $conn->error;
        header("Location: " . ($user_role === 'vendor' ? 'vendor_orders.php' : 'shopkeeper_orders.php'));
        exit();
    }
    
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result && $check_result->num_rows > 0) {
        $order_data = $check_result->fetch_assoc();
        $current_status = strtolower($order_data['status'] ?? 'pending');
        $order_user_id = $order_data['user_id'];
        $order_vendor_id = $order_data['vendor_id'] ?? null;
        
        // Verify ownership: shopkeeper can cancel their own orders, vendor can cancel orders assigned to them
        $is_owner = false;
        if ($user_role === 'shopkeeper' && $order_user_id == $user_id) {
            $is_owner = true;
        } elseif ($user_role === 'vendor') {
            // Check if vendor_id column exists and matches, or check via products
            if ($order_vendor_id && $order_vendor_id == $user_id) {
                $is_owner = true;
            } else {
                // Check via order_items and products
                $vendor_check_sql = "SELECT COUNT(*) as count 
                                    FROM order_items oi 
                                    JOIN products p ON oi.product_id = p.id 
                                    WHERE oi.order_id = ? AND p.vendor_id = ?";
                $vendor_check_stmt = $conn->prepare($vendor_check_sql);
                if ($vendor_check_stmt) {
                    $vendor_check_stmt->bind_param("ii", $order_id, $user_id);
                    $vendor_check_stmt->execute();
                    $vendor_check_result = $vendor_check_stmt->get_result();
                    if ($vendor_check_result && $vendor_check_result->num_rows > 0) {
                        $vendor_count = $vendor_check_result->fetch_assoc()['count'];
                        $is_owner = ($vendor_count > 0);
                    }
                    $vendor_check_stmt->close();
                }
            }
        }
        
        if (!$is_owner) {
            $_SESSION["error_message"] = "You do not have permission to cancel this order.";
            header("Location: " . ($user_role === 'vendor' ? 'vendor_orders.php' : 'shopkeeper_orders.php'));
            exit();
        }
        
        // Check if order can be cancelled (not shipped or delivered)
        if (in_array($current_status, ['shipped', 'delivered', 'cancelled'], true)) {
            $_SESSION["error_message"] = "Order cannot be cancelled. Current status: " . ucfirst($current_status) . ". Only pending or processing orders can be cancelled.";
            header("Location: view_order.php?id=" . $order_id);
            exit();
        }
        
        // Update order status to cancelled
        // Check if status column has ENUM constraint
        $check_status_col = $conn->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
        $status_col_info = $check_status_col ? $check_status_col->fetch_assoc() : null;
        $status_type = $status_col_info['Type'] ?? '';
        
        $new_status = 'cancelled';
        
        // If status is ENUM, ensure the value matches
        if (stripos($status_type, 'enum') !== false) {
            preg_match("/enum\((.*)\)/i", $status_type, $matches);
            if (isset($matches[1])) {
                $enum_values_raw = array_map(function($v) {
                    return trim(str_replace("'", "", $v));
                }, explode(',', $matches[1]));
                
                $enum_values_lower = array_map('strtolower', $enum_values_raw);
                
                // Find matching enum value (case-insensitive)
                $matched_value = null;
                foreach ($enum_values_raw as $enum_val) {
                    if (strtolower($enum_val) === 'cancelled') {
                        $matched_value = $enum_val;
                        break;
                    }
                }
                
                if ($matched_value) {
                    $new_status = $matched_value;
                } else {
                    // Try to add 'cancelled' to ENUM if missing
                    $all_values = array_unique(array_merge($enum_values_raw, ['pending', 'processing', 'shipped', 'delivered', 'cancelled']));
                    $enum_sql = "ALTER TABLE orders MODIFY COLUMN status ENUM('" . implode("','", $all_values) . "') DEFAULT 'pending'";
                    if ($conn->query($enum_sql)) {
                        $new_status = 'cancelled';
                    } else {
                        $_SESSION["error_message"] = "Error updating status column. Please contact administrator.";
                        header("Location: view_order.php?id=" . $order_id);
                        exit();
                    }
                }
            }
        }
        
        // Update order status
        $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if (!$update_stmt) {
            $_SESSION["error_message"] = "Error preparing update query: " . $conn->error;
            header("Location: view_order.php?id=" . $order_id);
            exit();
        }
        
        $update_stmt->bind_param("si", $new_status, $order_id);
        
        if ($update_stmt->execute()) {
            $affected_rows = $update_stmt->affected_rows;
            if ($affected_rows > 0) {
                $_SESSION["success_message"] = "Order #$order_id has been cancelled successfully.";
                
                // Restore stock for cancelled order items
                $restore_stock_sql = "UPDATE products p 
                                    INNER JOIN order_items oi ON p.id = oi.product_id 
                                    SET p.stock_quantity = p.stock_quantity + oi.quantity 
                                    WHERE oi.order_id = ?";
                $restore_stmt = $conn->prepare($restore_stock_sql);
                if ($restore_stmt) {
                    $restore_stmt->bind_param("i", $order_id);
                    $restore_stmt->execute();
                    $restore_stmt->close();
                }
            } else {
                $_SESSION["error_message"] = "No rows were updated. Order may not exist or status is already cancelled.";
            }
        } else {
            $_SESSION["error_message"] = "Error cancelling order: " . $update_stmt->error;
        }
        
        $update_stmt->close();
        $check_stmt->close();
        
        // Redirect back to the referring page
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'shopkeeper_details.php') !== false) {
            // Extract shopkeeper ID and order_id from referer URL
            $parsed_url = parse_url($referer);
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);
                if (isset($query_params['id'])) {
                    $redirect_url = "shopkeeper_details.php?id=" . $query_params['id'] . "&order_id=" . $order_id;
                    header("Location: " . $redirect_url);
                    exit();
                }
            }
        }
        
        // Default: redirect to view_order.php
        header("Location: view_order.php?id=" . $order_id);
        exit();
    } else {
        $_SESSION["error_message"] = "Order not found.";
        header("Location: " . ($user_role === 'vendor' ? 'vendor_orders.php' : 'shopkeeper_orders.php'));
        exit();
    }
} else {
    $_SESSION["error_message"] = "Invalid request.";
    $user_role = $_SESSION["role"] ?? '';
    header("Location: " . ($user_role === 'vendor' ? 'vendor_orders.php' : 'shopkeeper_orders.php'));
    exit();
}

$conn->close();
?>

