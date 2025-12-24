<?php
// This script will fix the checkout.php file to handle errors properly

// First, make a backup of the original file
$original_file = 'checkout.php';
$backup_file = 'checkout.php.bak_error_fix';

if (!file_exists($backup_file)) {
    copy($original_file, $backup_file);
    echo "Backup created: $backup_file<br>";
}

// Read the original file
$content = file_get_contents($original_file);

// Fix 1: Improve error handling for vendor_id
// The issue might be that some products don't have a vendor_id
$fix1 = <<<'EOD'
        $product_id = (int)$item['id'];
        $quantity = (int)$item['qty'];
        $price = (float)$item['price'];
        $vendor_id = isset($item['vendor_id']) ? (int)$item['vendor_id'] : 0;
        
        // Check if product exists before proceeding
        $check_product = "SELECT id, name, stock, vendor_id FROM products WHERE id = ?";
        $check_stmt = $conn->prepare($check_product);
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $product_result = $check_stmt->get_result();
        
        if (!$product_result || $product_result->num_rows == 0) {
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
        $current_stock = $product_data['stock'];
        
        if ($current_stock < $quantity) {
            // Return a user-friendly error
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
        $update_stock = "UPDATE products SET stock = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_stock);
        $update_stmt->bind_param("ii", $new_stock, $product_id);
        $update_stmt->execute();
EOD;

// Find and replace the code block for processing cart items
$pattern = '/\$product_id = \(int\)\$item\[\'id\'\];\s*\$quantity = \(int\)\$item\[\'qty\'\];\s*\$price = \(float\)\$item\[\'price\'\];\s*\$vendor_id = \(int\)\$item\[\'vendor_id\'\];\s*\$item_total = \$price \* \$quantity;\s*\$total \+= \$item_total;\s*\/\/ Group items by vendor.*?\$update_stmt->execute\(\);/s';

$content = preg_replace($pattern, $fix1, $content);

// Fix 2: Improve error handling for vendor information
$fix2 = <<<'EOD'
    // Get vendor information
    $vendor_info = [];
    if (!empty($vendor_orders)) {
        $vendor_ids = array_keys($vendor_orders);
        
        // Check if there are any vendors and filter out zero/null vendor IDs
        $valid_vendor_ids = array_filter($vendor_ids, function($id) { return $id > 0; });
        
        if (count($valid_vendor_ids) > 0) {
            $placeholders = str_repeat('?,', count($valid_vendor_ids) - 1) . '?';
            $stmt = $conn->prepare("SELECT id, name, email FROM vendors WHERE id IN ($placeholders)");
            $types = str_repeat('i', count($valid_vendor_ids));
            $stmt->bind_param($types, ...$valid_vendor_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $vendor_info[$row['id']] = $row;
            }
        }
    }
EOD;

// Find and replace the vendor information code block
$pattern2 = '/\/\/ Get vendor information\s*\$vendor_info = \[\];\s*if \(!empty\(\$vendor_orders\)\) \{\s*\$vendor_ids = array_keys\(\$vendor_orders\);\s*\/\/ Check if there are any vendors.*?\}\s*\}/s';

$content = preg_replace($pattern2, $fix2, $content);

// Fix 3: Improve error handling in the catch block
$fix3 = <<<'EOD'
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Return error response with more details
    $error_message = $e->getMessage();
    $error_trace = $e->getTraceAsString();
    
    // Log the error for debugging (optional)
    error_log("Checkout Error: " . $error_message . "\n" . $error_trace);
    
    echo json_encode([
        'success' => false,
        'message' => "An error occurred during checkout. Please try again.",
        'error_details' => $error_message
    ]);
}
EOD;

// Find and replace the catch block
$pattern3 = '/} catch \(Exception \$e\) \{\s*\/\/ Rollback transaction on error\s*\$conn->rollback\(\);\s*\/\/ Return error response\s*echo json_encode\(\[\s*\'success\' => false,\s*\'message\' => \$e->getMessage\(\)\s*\]\);\s*\}/s';

$content = preg_replace($pattern3, $fix3, $content);

// Write the updated content back to the file
file_put_contents($original_file, $content);

echo "Checkout.php has been updated with improved error handling.<br>";
echo "The following fixes were applied:<br>";
echo "1. Better handling of vendor_id (using product's vendor_id if not provided in cart)<br>";
echo "2. Improved product existence and stock checking<br>";
echo "3. Better handling of vendor information with filtering of invalid vendor IDs<br>";
echo "4. Enhanced error reporting in the catch block<br>";
?>