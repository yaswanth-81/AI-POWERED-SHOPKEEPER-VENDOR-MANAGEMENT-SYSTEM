<?php
// Check vendor_orders.php for any references to 'username'
$file_path = __DIR__ . '/vendor_orders.php';

if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    
    // Check for 'username' in the file
    if (strpos($content, 'username') !== false) {
        echo "<p>Found 'username' in vendor_orders.php</p>";
        
        // Extract lines containing 'username'
        $lines = explode("\n", $content);
        $matching_lines = [];
        
        foreach ($lines as $line_num => $line) {
            if (strpos($line, 'username') !== false) {
                $matching_lines[$line_num + 1] = $line;
            }
        }
        
        if (!empty($matching_lines)) {
            echo "<pre>";
            foreach ($matching_lines as $line_num => $line) {
                echo "Line " . $line_num . ": " . htmlspecialchars($line) . "\n";
            }
            echo "</pre>";
        }
    } else {
        echo "<p>No direct references to 'username' found in vendor_orders.php</p>";
    }
    
    // Check for SQL query in the file
    if (preg_match('/\$sql\s*=\s*"([^"]+)"/s', $content, $matches)) {
        echo "<p>Found SQL query in vendor_orders.php:</p>";
        echo "<pre>" . htmlspecialchars($matches[1]) . "</pre>";
        
        // Check if the query contains a reference to u.username
        if (strpos($matches[1], 'u.username') !== false) {
            echo "<p>SQL query contains reference to 'u.username'</p>";
        } else {
            echo "<p>SQL query does not contain direct reference to 'u.username'</p>";
        }
    }
    
    // Check for prepare statement and execution
    if (preg_match('/\$stmt\s*=\s*\$conn->prepare\(\$sql\);([^;]+);/s', $content, $matches)) {
        echo "<p>Found prepare statement and execution:</p>";
        echo "<pre>" . htmlspecialchars($matches[1]) . "</pre>";
    }
    
    // Check for fetch_assoc and usage of fields
    if (preg_match('/\$order\s*=\s*\$result->fetch_assoc\(\);([^}]+)/s', $content, $matches)) {
        echo "<p>Found fetch_assoc and field usage:</p>";
        echo "<pre>" . htmlspecialchars($matches[1]) . "</pre>";
        
        // Check if username is used in the field usage
        if (strpos($matches[1], 'username') !== false) {
            echo "<p>Field usage contains reference to 'username'</p>";
        } else {
            echo "<p>Field usage does not contain direct reference to 'username'</p>";
        }
    }
} else {
    echo "<p>vendor_orders.php file not found</p>";
}
?>