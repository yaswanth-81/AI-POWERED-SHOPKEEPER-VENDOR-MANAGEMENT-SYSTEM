<?php
include 'db.php';

// Get table structure
$result = $conn->query("DESCRIBE users");
echo "<h2>Users Table Structure:</h2>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Get a sample row
echo "<h2>Sample User:</h2>";
$result = $conn->query("SELECT * FROM users LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "No users found or error: " . $conn->error;
}
?>