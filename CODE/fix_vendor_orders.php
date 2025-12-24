<?php
// This script will fix the vendor_orders.php file by removing any references to u.username

// Read the vendor_orders.php file
$file_path = __DIR__ . '/vendor_orders.php';
$content = file_get_contents($file_path);

// Make a backup of the original file
$backup_path = __DIR__ . '/vendor_orders.php.bak';
file_put_contents($backup_path, $content);
echo "<p>Created backup at: {$backup_path}</p>";

// Check for any references to u.username in the file
$has_username_reference = (strpos($content, 'u.username') !== false);

if ($has_username_reference) {
    echo "<p>Found reference to 'u.username' in the file</p>";
    
    // Replace u.username with u.first_name
    $content = str_replace('u.username', 'u.first_name', $content);
    
    // Save the modified file
    file_put_contents($file_path, $content);
    echo "<p>Replaced 'u.username' with 'u.first_name' in the file</p>";
} else {
    echo "<p>No direct reference to 'u.username' found in the file</p>";
    
    // Try to fix the issue by rewriting the SQL query
    $sql_pattern = '/\$sql\s*=\s*"([^"]+)"/s';
    if (preg_match($sql_pattern, $content, $matches)) {
        $original_sql = $matches[0];
        $sql_query = $matches[1];
        
        echo "<p>Found SQL query: " . htmlspecialchars($sql_query) . "</p>";
        
        // Create a new SQL query with the same fields but explicitly named
        $new_sql = 'SELECT o.id, o.user_id, o.vendor_id, o.total, o.status, o.created_at, ' .
                  'u.first_name, u.last_name, CONCAT(u.first_name, \' \', u.last_name) as customer_name, ' .
                  'u.email, u.phone, u.address, u.city, u.state, u.postal_code, u.country, u.shop_name ' .
                  'FROM orders o ' .
                  'LEFT JOIN users u ON o.user_id = u.id ' .
                  'WHERE o.vendor_id = ? ' .
                  'ORDER BY o.created_at DESC';
        
        $new_sql_assignment = '$sql = "' . $new_sql . '";';
        
        // Replace the SQL query in the file
        $content = str_replace($original_sql, $new_sql_assignment, $content);
        
        // Save the modified file
        file_put_contents($file_path, $content);
        echo "<p>Replaced SQL query in the file</p>";
    } else {
        echo "<p>Could not find SQL query pattern in the file</p>";
    }
}

echo "<p>Done. Please check if the issue is resolved.</p>";
?>