<?php
require_once 'db.php';

// Create a backup of the original file
$original_file = 'checkout.php';
$backup_file = 'checkout.php.bak_backslash';

if (!copy($original_file, $backup_file)) {
    echo "Failed to create backup file.\n";
    exit;
}

// Read the original file
$content = file_get_contents($original_file);

// Fix the backslash and newline character
$content = str_replace('$update_stmt->execute();\n    }', '$update_stmt->execute();
    }', $content);

// Write the fixed content back to the file
if (file_put_contents($original_file, $content)) {
    echo "Successfully fixed the backslash and newline character in checkout.php.\n";
    echo "A backup of the original file has been created as $backup_file.\n";
} else {
    echo "Failed to update the file.\n";
}
?>