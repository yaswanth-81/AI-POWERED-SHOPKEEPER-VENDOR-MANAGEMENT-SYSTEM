<?php
session_start();
include 'db.php';

// Simulate a vendor login
$_SESSION['user_id'] = 1; // Assuming vendor ID 1 exists
$_SESSION['role'] = 'vendor';

// Include the vendor_orders.php file
include 'vendor_orders.php';
?>