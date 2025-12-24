<?php
include 'db.php';

// Check status column structure
$result = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");

if ($result && $result->num_rows > 0) {
    echo "Status column structure:\n";
    $row = $result->fetch_assoc();
    print_r($row);
    echo "\n";
} else {
    echo "Status column not found\n";
}

// Check actual status values in the database
echo "Sample status values from orders table:\n";
$result = $conn->query("SELECT DISTINCT status FROM orders LIMIT 10");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Status: '" . $row['status'] . "'\n";
    }
} else {
    echo "No orders found or no status column\n";
}

$conn->close();
?>
