<?php
include 'db.php';

// Check if checkout.php exists
if (file_exists('checkout.php')) {
    echo "<h2>Fixing checkout.php response handling</h2>";
    
    // Read the current file content
    $content = file_get_contents('checkout.php');
    
    // Create a backup
    file_put_contents('checkout.php.bak2', $content);
    echo "<p>Created backup at checkout.php.bak2</p>";
    
    // Fix 1: Make sure the orders array is properly populated in the response
    $pattern1 = '/\$created_orders\[\] = \[\s*\'order_id\' => \$order_id,\s*\'vendor_id\' => \$vendor_id,\s*\'total\' => \$vendor_order\[\'total\'\],\s*\'items\' => \$vendor_order\[\'items\'\]\s*\];/s';
    $replacement1 = '$created_orders[] = [\n            \'order_id\' => $order_id,\n            \'vendor_id\' => $vendor_id,\n            \'total\' => $vendor_order[\'total\'],\n            \'items\' => $vendor_order[\'items\']\n        ];';
    
    // Fix 2: Make sure the response includes the created orders
    $pattern2 = '/\$response = \[\s*\'success\' => true,\s*\'orders\' => \[\],/s';
    $replacement2 = '$response = [\n        \'success\' => true,\n        \'orders\' => $created_orders,';
    
    // Apply the replacements
    $updated_content = preg_replace($pattern1, $replacement1, $content);
    $updated_content = preg_replace($pattern2, $replacement2, $updated_content);
    
    // Check if any changes were made
    if ($updated_content !== $content) {
        file_put_contents('checkout.php', $updated_content);
        echo "<p>Successfully updated checkout.php</p>";
        
        // Show the changes
        echo "<h3>Changes made:</h3>";
        echo "<p>1. Fixed the created_orders array structure</p>";
        echo "<p>2. Updated the response to include the created orders</p>";
    } else {
        echo "<p>No changes needed or patterns not found. Let's check the actual response in the file.</p>";
        
        // Extract the response creation
        preg_match('/\$response = \[.*?\];/s', $content, $matches);
        echo "<pre>";
        print_r($matches);
        echo "</pre>";
    }
    
    // Additional fix: Check if the response is properly encoded as JSON
    if (strpos($content, 'header(\'Content-Type: application/json\');') === false) {
        echo "<p>Adding Content-Type header for JSON response</p>";
        $updated_content = str_replace('<?php', "<?php\nheader('Content-Type: application/json');", $updated_content);
        file_put_contents('checkout.php', $updated_content);
    }
    
    // Check if the response is properly encoded
    if (strpos($content, 'echo json_encode($response);') === false) {
        echo "<p>Fixing JSON encoding of response</p>";
        
        // Find the end of the try block where the response is sent
        $pattern = '/\$response = \[.*?\];\s*echo json_encode\(\$response\);/s';
        $replacement = '$response = [\n        \'success\' => true,\n        \'orders\' => $created_orders,\n        \'shopkeeper\' => $shopkeeper_info,\n        \'vendors\' => $vendor_info,\n        \'total_amount\' => $total,\n        \'message\' => "Order placed successfully!"\n    ];\n    echo json_encode($response);';
        
        $updated_content = preg_replace($pattern, $replacement, $updated_content);
        file_put_contents('checkout.php', $updated_content);
    }
} else {
    echo "<h2>checkout.php not found</h2>";
}

$conn->close();
?>