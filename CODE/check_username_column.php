<?php
include 'db.php';

// Check if username column exists in users table
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'username'");

if ($result && $result->num_rows > 0) {
    echo "<p>username column exists in users table</p>";
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<p>username column does NOT exist in users table</p>";
}

$conn->close();
?>