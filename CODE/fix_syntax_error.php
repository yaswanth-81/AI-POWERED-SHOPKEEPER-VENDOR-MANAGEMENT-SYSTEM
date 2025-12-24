<?php
// This script will fix the syntax error in checkout.php

// First, make a backup of the original file
$original_file = 'checkout.php';
$backup_file = 'checkout.php.bak_syntax_fix';

if (!file_exists($backup_file)) {
    copy($original_file, $backup_file);
    echo "Backup created: $backup_file<br>";
}

// Read the original file
$content = file_get_contents($original_file);

// Fix the syntax error - remove the extra closing brace on line 90
$pattern = '/\$update_stmt->execute\(\);\s*\}\s*else\s*\{/s';
$replacement = '\$update_stmt->execute();\n    } else {';

$content = preg_replace($pattern, $replacement, $content);

// Write the updated content back to the file
file_put_contents($original_file, $content);

echo "Checkout.php has been updated to fix the syntax error.<br>";
?>