<?php
require_once 'db.php';

// Create a backup of the original file
$original_file = 'checkout.php';
$backup_file = 'checkout.php.bak_final_backslash';

if (!copy($original_file, $backup_file)) {
    echo "Failed to create backup file.\n";
    exit;
}

// Read the original file
$content = file_get_contents($original_file);

// Fix the backslash and newline characters
$content = str_replace('            }\n    }\n}', '            }
        }
    }
', $content);

// Write the fixed content back to the file
if (file_put_contents($original_file, $content)) {
    echo "Successfully fixed the backslash and newline characters in checkout.php.\n";
    echo "A backup of the original file has been created as $backup_file.\n";
} else {
    echo "Failed to update the file.\n";
}
?>