<?php
session_start();
include "db.php";

// Check if user is logged in as vendor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "vendor") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["order_id"]) && isset($_POST["status"])) {
    $order_id = intval($_POST["order_id"]);
    $new_status = strtolower($_POST["status"]);
    $vendor_id = $_SESSION["user_id"];
    
    // Check if vendor_id column exists
    $check_vendor_col = $conn->query("SHOW COLUMNS FROM orders LIKE 'vendor_id'");
    $has_vendor_id = $check_vendor_col && $check_vendor_col->num_rows > 0;
    
    // Get current order status and verify this order belongs to the vendor
    if ($has_vendor_id) {
    $check_sql = "SELECT id, status, user_id FROM orders WHERE id = ? AND vendor_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $order_id, $vendor_id);
    } else {
        // If no vendor_id column, verify by checking if order contains vendor's products
        $check_sql = "SELECT DISTINCT o.id, o.status, o.user_id 
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE o.id = ? AND p.vendor_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $order_id, $vendor_id);
    }
    
    if (!$check_stmt) {
        $_SESSION["error_message"] = "Error preparing query: " . $conn->error;
        header("Location: vendor_orders.php");
        exit();
    }
    
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result && $check_result->num_rows > 0) {
        $order_data = $check_result->fetch_assoc();
        $current_status = strtolower($order_data['status'] ?? 'pending'); // Default to pending if NULL
        $user_id = $order_data['user_id'];
        
        // If status is NULL, treat it as pending
        if (empty($current_status) || $current_status === 'null') {
            $current_status = 'pending';
        }
        
        // Define allowed status transitions
        // Business rule: 
        // - Can move forward step-by-step: pending → processing → shipped → delivered
        // - Can cancel only while order is NOT yet shipped or delivered (i.e. pending or processing)
        $allowed_transitions = [
            'pending'    => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped'    => ['delivered'], // cannot cancel once shipped
            'delivered'  => [],            // final state
            'cancelled'  => []             // final state
        ];
        
        // Special case: cancellation is only allowed from pending or processing
        $is_cancellation = ($new_status === 'cancelled');
        $can_cancel = in_array($current_status, ['pending', 'processing'], true);
        
        // Check if the transition is allowed
        $is_allowed = ($new_status === $current_status || 
                      (isset($allowed_transitions[$current_status]) && in_array($new_status, $allowed_transitions[$current_status])) || 
                      ($is_cancellation && $can_cancel));
        
        if ($is_allowed) {
            // Update order status - check if status column has ENUM constraint
            $check_status_col = $conn->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
            $status_col_info = $check_status_col ? $check_status_col->fetch_assoc() : null;
            $status_type = $status_col_info['Type'] ?? '';
            
            // If status is ENUM, ensure the value matches one of the allowed values
            if (stripos($status_type, 'enum') !== false) {
                // Extract ENUM values
                preg_match("/enum\((.*)\)/i", $status_type, $matches);
                if (isset($matches[1])) {
                    $enum_values_raw = array_map(function($v) {
                        return trim(str_replace("'", "", $v));
                    }, explode(',', $matches[1]));
                    
                    $enum_values_lower = array_map('strtolower', $enum_values_raw);
                    
                    // Check if new_status is in allowed ENUM values (case-insensitive)
                    if (!in_array(strtolower($new_status), $enum_values_lower)) {
                        // Try to find a matching value with different case
                        $matched_value = null;
                        foreach ($enum_values_raw as $enum_val) {
                            if (strtolower($enum_val) === strtolower($new_status)) {
                                $matched_value = $enum_val;
                                break;
                            }
                        }
                        
                        if ($matched_value) {
                            // Use the exact case from ENUM
                            $new_status = $matched_value;
                        } else {
                            // Check if we need to add 'processing' - if ENUM has 'confirmed' but not 'processing', map it
                            if (strtolower($new_status) === 'processing' && in_array('confirmed', $enum_values_lower)) {
                                // Some databases use 'confirmed' instead of 'processing'
                                $confirmed_index = array_search('confirmed', $enum_values_lower);
                                $new_status = $enum_values_raw[$confirmed_index];
                            } else {
                                $_SESSION["error_message"] = "Invalid status value '$new_status'. Allowed values: " . implode(', ', $enum_values_raw) . ". Updating ENUM column...";
                                // Try to alter the column to include the missing value
                                $all_values = array_unique(array_merge($enum_values_raw, ['pending', 'processing', 'shipped', 'delivered', 'cancelled']));
                                $enum_sql = "ALTER TABLE orders MODIFY COLUMN status ENUM('" . implode("','", $all_values) . "') DEFAULT 'pending'";
                                if ($conn->query($enum_sql)) {
                                    $_SESSION["success_message"] = "Status column updated. Please try again.";
                                } else {
                                    $_SESSION["error_message"] = "Invalid status value. Please contact administrator.";
                                }
                                header("Location: vendor_orders.php");
                                exit();
                            }
                        }
                    } else {
                        // Find the exact case match
                        $matched_index = array_search(strtolower($new_status), $enum_values_lower);
                        if ($matched_index !== false) {
                            $new_status = $enum_values_raw[$matched_index];
                        }
                    }
                }
            }
            
            // Update order status
            $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            if (!$update_stmt) {
                $_SESSION["error_message"] = "Error preparing update query: " . $conn->error;
                header("Location: vendor_orders.php");
                exit();
            }
            
            $update_stmt->bind_param("si", $new_status, $order_id);
            
            if ($update_stmt->execute()) {
                $affected_rows = $update_stmt->affected_rows;
                if ($affected_rows > 0) {
                    $_SESSION["success_message"] = "Order #$order_id status updated successfully from " . ucfirst($current_status) . " to " . ucfirst($new_status) . "!";
                } else {
                    $_SESSION["error_message"] = "No rows were updated. Order may not exist or status is already set to this value.";
                }
            } else {
                $_SESSION["error_message"] = "Error updating order status: " . $update_stmt->error . " (SQL Error: " . $conn->error . ")";
            }
            
            $update_stmt->close();
        } else {
            $_SESSION["error_message"] = "Invalid status transition from " . ucfirst($current_status) . " to " . ucfirst($new_status) . ".";
        }
    } else {
        $_SESSION["error_message"] = "You do not have permission to update this order.";
    }
} else {
    $_SESSION["error_message"] = "Invalid request.";
}

// Check if redirect_to parameter was provided in POST
if (isset($_POST['redirect_to']) && !empty($_POST['redirect_to'])) {
    header("Location: " . $_POST['redirect_to']);
    exit();
}

// Redirect back to the referring page or shopkeeper details
$referer = $_SERVER['HTTP_REFERER'] ?? 'vendor_orders.php';
if (strpos($referer, 'shopkeeper_details.php') !== false) {
    // Extract shopkeeper ID and order_id from referer URL
    $parsed_url = parse_url($referer);
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
        if (isset($query_params['id'])) {
            $redirect_url = "shopkeeper_details.php?id=" . $query_params['id'];
            // Preserve order_id if it was in the original URL
            if (isset($query_params['order_id']) && $query_params['order_id'] > 0) {
                $redirect_url .= "&order_id=" . $query_params['order_id'];
            }
            header("Location: " . $redirect_url);
            exit();
        }
    }
}

// Check if coming from view_order.php
if (strpos($referer, 'view_order.php') !== false) {
    $parsed_url = parse_url($referer);
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
        if (isset($query_params['id'])) {
            header("Location: view_order.php?id=" . $query_params['id']);
            exit();
        }
    }
}

header("Location: vendor_orders.php");
exit();
?>