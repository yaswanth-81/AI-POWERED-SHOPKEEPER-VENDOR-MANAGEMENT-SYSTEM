<?php
// This script will check the braces in checkout.php and identify any mismatches

$file = 'checkout.php';
$content = file_get_contents($file);

// Count opening and closing braces
$open_braces = substr_count($content, '{');
$close_braces = substr_count($content, '}');

// Output results
echo "<h1>Brace Analysis for checkout.php</h1>";
echo "<p>Opening braces: $open_braces</p>";
echo "<p>Closing braces: $close_braces</p>";

if ($open_braces == $close_braces) {
    echo "<p style='color: green;'>Braces are balanced!</p>";
} else {
    echo "<p style='color: red;'>Braces are NOT balanced!</p>";
    if ($open_braces > $close_braces) {
        echo "<p>Missing " . ($open_braces - $close_braces) . " closing braces</p>";
    } else {
        echo "<p>Extra " . ($close_braces - $open_braces) . " closing braces</p>";
    }
}

// Find line numbers for each brace
echo "<h2>Brace Locations</h2>";
$lines = explode("\n", $content);
$open_locations = [];
$close_locations = [];

foreach ($lines as $i => $line) {
    $line_num = $i + 1;
    $open_count = substr_count($line, '{');
    $close_count = substr_count($line, '}');
    
    for ($j = 0; $j < $open_count; $j++) {
        $open_locations[] = $line_num;
    }
    
    for ($j = 0; $j < $close_count; $j++) {
        $close_locations[] = $line_num;
    }
}

// Display the brace locations
echo "<p>Opening braces at lines: " . implode(", ", $open_locations) . "</p>";
echo "<p>Closing braces at lines: " . implode(", ", $close_locations) . "</p>";
?>