<?php
require_once 'db.php';

// Create a backup of the original file
$original_file = 'checkout.php';
$backup_file = 'checkout.php.bak_extra_brace';

if (!copy($original_file, $backup_file)) {
    echo "Failed to create backup file.\n";
    exit;
}

// Read the original file
$content = file_get_contents($original_file);

// Fix the extra closing brace
$pattern = '/        }\s*    }\s*    }\s*/s';
$replacement = '        }\n    }\n';

$updated_content = preg_replace($pattern, $replacement, $content);

// Write the fixed content back to the file
if (file_put_contents($original_file, $updated_content)) {
    echo "Successfully fixed the extra closing brace in checkout.php.\n";
    echo "A backup of the original file has been created as $backup_file.\n";
} else {
    echo "Failed to update the file.\n";
}
?>