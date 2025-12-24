<?php
// Check if PHP error log exists and display its contents
$error_log_path = ini_get('error_log');
echo "<p>PHP error log path: " . $error_log_path . "</p>";

if (file_exists($error_log_path)) {
    echo "<p>Error log exists. Last 20 lines:</p>";
    $log_content = file_get_contents($error_log_path);
    $lines = explode("\n", $log_content);
    $last_lines = array_slice($lines, -20);
    
    echo "<pre>";
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p>Error log file not found at: " . $error_log_path . "</p>";
    
    // Try to find error log in common locations
    $common_locations = [
        'C:/xampp/php/logs/php_error_log',
        'C:/xampp/apache/logs/error.log',
        'C:/xampp/logs/php_error_log',
        'C:/xampp/htdocs/error_log'
    ];
    
    foreach ($common_locations as $location) {
        if (file_exists($location)) {
            echo "<p>Found log at: " . $location . "</p>";
            $log_content = file_get_contents($location);
            $lines = explode("\n", $log_content);
            $last_lines = array_slice($lines, -20);
            
            echo "<pre>";
            foreach ($last_lines as $line) {
                echo htmlspecialchars($line) . "\n";
            }
            echo "</pre>";
            break;
        }
    }
}

// Check for any references to 'username' in the code
echo "<h2>Checking for 'username' references in PHP files</h2>";
$directory = __DIR__;
$php_files = glob($directory . '/*.php');

foreach ($php_files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'username') !== false) {
        echo "<p>Found 'username' in file: " . basename($file) . "</p>";
        
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
    }
}
?>