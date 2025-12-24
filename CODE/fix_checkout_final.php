<?php
include 'db.php';

// Check if checkout.php exists
if (file_exists('checkout.php')) {
    echo "<h2>Final Fix for checkout.php</h2>";
    
    // Read the current file content
    $content = file_get_contents('checkout.php');
    
    // Create a backup
    file_put_contents('checkout.php.bak_final', $content);
    echo "<p>Created backup at checkout.php.bak_final</p>";
    
    // Fix 1: Add better error handling for stock checks
    $pattern1 = '/if \(\$current_stock < \$quantity\) {\s*throw new Exception\("Not enough stock for product {\$item\[\'name\'\]}\. Available: {\$current_stock}, Requested: {\$quantity}"\);\s*}/s';
    $replacement1 = "if (\$current_stock < \$quantity) {\n                // Instead of throwing an exception, return a more user-friendly error\n                echo json_encode([\n                    'success' => false,\n                    'message' => \"Not enough stock for product {\$item['name']}. Available: {\$current_stock}, Requested: {\$quantity}\",\n                    'product_id' => \$product_id,\n                    'available_stock' => \$current_stock\n                ]);\n                exit;\n            }";
    
    // Fix 2: Add better error handling for product not found
    $pattern2 = '/} else {\s*throw new Exception\("Product {\$item\[\'name\'\]} not found"\);\s*}/s';
    $replacement2 = "} else {\n            // Instead of throwing an exception, return a more user-friendly error\n            echo json_encode([\n                'success' => false,\n                'message' => \"Product {\$item['name']} not found\",\n                'product_id' => \$product_id\n            ]);\n            exit;\n        }";
    
    // Fix 3: Add better error handling for vendor information
    $pattern3 = '/\/\/ Get vendor information\s*\$vendor_info = \[\];\s*if \(!empty\(\$vendor_orders\)\) {\s*\$vendor_ids = array_keys\(\$vendor_orders\);\s*\$placeholders = str_repeat\(\'\?,\', count\(\$vendor_ids\) - 1\) \. \'\?\';\s*\$stmt = \$conn->prepare\("SELECT id, name, email FROM vendors WHERE id IN \(\$placeholders\)"\);\s*\$types = str_repeat\(\'i\', count\(\$vendor_ids\)\);\s*\$stmt->bind_param\(\$types, \.\.\.\$vendor_ids\);/s';
    $replacement3 = "// Get vendor information\n    \$vendor_info = [];\n    if (!empty(\$vendor_orders)) {\n        \$vendor_ids = array_keys(\$vendor_orders);\n        \n        // Check if there are any vendors\n        if (count(\$vendor_ids) > 0) {\n            \$placeholders = str_repeat('?,', count(\$vendor_ids) - 1) . '?';\n            \$stmt = \$conn->prepare(\"SELECT id, name, email FROM vendors WHERE id IN (\$placeholders)\");\n            \$types = str_repeat('i', count(\$vendor_ids));\n            \$stmt->bind_param(\$types, ...\$vendor_ids);";
    
    // Apply the replacements
    $updated_content = preg_replace($pattern1, $replacement1, $content);
    $updated_content = preg_replace($pattern2, $replacement2, $updated_content);
    $updated_content = preg_replace($pattern3, $replacement3, $updated_content);
    
    // Fix 4: Add closing bracket for vendor information check
    $pattern4 = '/while \(\$row = \$result->fetch_assoc\(\)\) {\s*\$vendor_info\[\$row\[\'id\'\]\] = \$row;\s*}\s*}/s';
    $replacement4 = "while (\$row = \$result->fetch_assoc()) {\n                \$vendor_info[\$row['id']] = \$row;\n            }\n        }\n    }";
    
    $updated_content = preg_replace($pattern4, $replacement4, $updated_content);
    
    // Check if any changes were made
    if ($updated_content !== $content) {
        file_put_contents('checkout.php', $updated_content);
        echo "<p>Successfully updated checkout.php</p>";
        
        // Show the changes
        echo "<h3>Changes made:</h3>";
        echo "<p>1. Improved error handling for stock checks</p>";
        echo "<p>2. Improved error handling for product not found</p>";
        echo "<p>3. Added check for empty vendor IDs</p>";
        echo "<p>4. Fixed vendor information handling</p>";
    } else {
        echo "<p>No changes needed or patterns not found.</p>";
    }
} else {
    echo "<h2>checkout.php not found</h2>";
}

$conn->close();
?>