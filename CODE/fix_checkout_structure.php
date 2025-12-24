<?php
require_once 'db.php';

// Create a backup of the original file
$original_file = 'checkout.php';
$backup_file = 'checkout.php.bak_structure';

if (!copy($original_file, $backup_file)) {
    echo "Failed to create backup file.\n";
    exit;
}

// Read the original file
$content = file_get_contents($original_file);

// Fix the structure error - remove the problematic else block
$pattern = '/\$update_stmt->execute\(\);\s*\} else \{\s*\/\/ Instead of throwing an exception, return a more user-friendly error\s*echo json_encode\(\[\s*\'success\' => false,\s*\'message\' => "Product \{\$item\[\'name\'\]\} not found",\s*\'product_id\' => \$product_id\s*\]\);\s*exit;\s*\}/s';
$replacement = '$update_stmt->execute();\n    }';

$updated_content = preg_replace($pattern, $replacement, $content);

// Write the fixed content back to the file
if (file_put_contents($original_file, $updated_content)) {
    echo "Successfully fixed the structure error in checkout.php.\n";
    echo "A backup of the original file has been created as $backup_file.\n";
} else {
    echo "Failed to update the file.\n";
}
?>