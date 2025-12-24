<?php
include 'db.php';

// Check if checkout.php exists
if (file_exists('checkout.php')) {
    echo "<h2>Fixing checkout.php</h2>";
    
    // Read the current file content
    $content = file_get_contents('checkout.php');
    
    // Create a backup
    file_put_contents('checkout.php.bak', $content);
    echo "<p>Created backup at checkout.php.bak</p>";
    
    // Fix the INSERT INTO orders statement
    // The issue is that we're using total_amount in the SQL but vendor_order['total'] in the bind_param
    // We need to make sure these match
    
    // Original line (around line 75):
    // $stmt = $conn->prepare("INSERT INTO orders (user_id, vendor_id, total_amount, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    
    // Check if we need to update total to total_amount in the table
    $pattern1 = '/INSERT INTO orders \(user_id, vendor_id, total_amount, status, created_at\) VALUES \(\?, \?, \?, \'pending\', NOW\(\)\)/i';
    $replacement1 = "INSERT INTO orders (user_id, vendor_id, total, total_amount, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())";
    
    // Update the bind_param to include both total and total_amount
    $pattern2 = '/\$stmt->bind_param\("iid", \$user_id, \$vendor_id, \$vendor_order\[\'total\'\]\);/i';
    $replacement2 = "\$stmt->bind_param(\"iidd\", \$user_id, \$vendor_id, \$vendor_order['total'], \$vendor_order['total']);"; // Use the same value for both total and total_amount
    
    // Apply the replacements
    $updated_content = preg_replace($pattern1, $replacement1, $content);
    $updated_content = preg_replace($pattern2, $replacement2, $updated_content);
    
    // Check if any changes were made
    if ($updated_content !== $content) {
        file_put_contents('checkout.php', $updated_content);
        echo "<p>Successfully updated checkout.php</p>";
        
        // Show the changes
        echo "<h3>Changes made:</h3>";
        echo "<p>1. Updated INSERT statement to include both total and total_amount</p>";
        echo "<p>2. Updated bind_param to include both total and total_amount values</p>";
    } else {
        echo "<p>No changes needed or patterns not found. Let's check the actual SQL statement in the file.</p>";
        
        // Extract the INSERT statement
        preg_match('/INSERT INTO orders.*?\);/s', $content, $matches);
        echo "<pre>";
        print_r($matches);
        echo "</pre>";
    }
} else {
    echo "<h2>checkout.php not found</h2>";
}

// Check if the orders table has the total_amount column
$result = $conn->query("SHOW COLUMNS FROM orders LIKE 'total_amount'");
if ($result->num_rows > 0) {
    echo "<p>total_amount column exists in orders table</p>";
} else {
    echo "<p>total_amount column does NOT exist in orders table</p>";
    
    // Add the column
    $alter_table = "ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,2) AFTER total";
    
    if ($conn->query($alter_table) === TRUE) {
        echo "<p>total_amount column added successfully</p>";
    } else {
        echo "<p>Error adding total_amount column: " . $conn->error . "</p>";
    }
}

$conn->close();
?>