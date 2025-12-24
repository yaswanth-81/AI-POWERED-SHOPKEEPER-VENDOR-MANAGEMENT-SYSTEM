<?php
session_start();
include 'db.php';

// Check if user is logged in as vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No product specified for deletion.";
    header("Location: vendor_dashboard.php#products");
    exit();
}

$id = intval($_GET['id']);
$vendor_id = $_SESSION['user_id'];

// First check if the product exists and belongs to this vendor
$check_sql = "SELECT id FROM products WHERE id = ? AND vendor_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $id, $vendor_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['error_message'] = "Product not found or you don't have permission to delete it.";
    header("Location: vendor_dashboard.php#products");
    exit();
}

// Delete the product
$sql = "DELETE FROM products WHERE id = ? AND vendor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $vendor_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Product deleted successfully!";
} else {
    $_SESSION['error_message'] = "Error deleting product: " . $conn->error;
}

header("Location: vendor_dashboard.php#products");
exit();