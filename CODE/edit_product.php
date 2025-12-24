<?php
session_start();

// Redirect to add_product.php with the ID parameter
// This is a simple redirect file since add_product.php already handles both adding and editing
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    header("Location: add_product.php?id=$id");
    exit();
} else {
    // If no ID provided, redirect to dashboard
    header("Location: vendor_dashboard.php#products");
    exit();
}