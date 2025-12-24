<?php
require_once 'db.php';

// Create a backup of the original file
$original_file = 'checkout.php';
$backup_file = 'checkout.php.bak_final_brace';

if (!copy($original_file, $backup_file)) {
    echo "Failed to create backup file.\n";
    exit;
}

// Read the original file
$content = file_get_contents($original_file);

// Count braces to verify the issue
$open_braces = substr_count($content, '{');
$close_braces = substr_count($content, '}');

echo "Before fix: Opening braces: $open_braces, Closing braces: $close_braces\n";

// The analysis showed an extra closing brace at line 179
// Let's remove it by rebuilding the file content properly
$lines = explode("\n", $content);

// Create a new content without the extra brace
$new_content = '';
$brace_stack = 0;
$removed_brace = false;

foreach ($lines as $i => $line) {
    $line_num = $i + 1;
    
    // Count braces in this line
    $open_count = substr_count($line, '{');
    $close_count = substr_count($line, '}');
    
    // Update stack before processing
    $brace_stack += $open_count;
    $brace_stack -= $close_count;
    
    // If we have a negative stack and haven't removed a brace yet
    if ($brace_stack < 0 && !$removed_brace) {
        // Find position of the last closing brace in this line
        $pos = strrpos($line, '}');
        if ($pos !== false) {
            // Remove the last closing brace
            $line = substr($line, 0, $pos) . substr($line, $pos + 1);
            $brace_stack++; // Adjust stack after removal
            $removed_brace = true;
            echo "Removed extra closing brace at line $line_num\n";
        }
    }
    
    $new_content .= $line . "\n";
}

// Verify the fix
$new_open_braces = substr_count($new_content, '{');
$new_close_braces = substr_count($new_content, '}');

echo "After fix: Opening braces: $new_open_braces, Closing braces: $new_close_braces\n";

// Write the fixed content back to the file
if (file_put_contents($original_file, $new_content)) {
    echo "Successfully fixed the extra closing brace in checkout.php.\n";
    echo "A backup of the original file has been created as $backup_file.\n";
} else {
    echo "Failed to update the file.\n";
}
?>