<?php
require 'db.php';
header('Content-Type: application/json');

// Expect product_ids=1,2,3
$param = isset($_GET['product_ids']) ? trim($_GET['product_ids']) : '';
if ($param === '') {
    echo json_encode([]);
    exit;
}

$raw = array_filter(array_map('trim', explode(',', $param)), function ($v) { return $v !== ''; });
$productIds = array_values(array_unique(array_map('intval', $raw)));
if (count($productIds) === 0) {
    echo json_encode([]);
    exit;
}

// Query to map product id -> vendor details
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$types = str_repeat('i', count($productIds));

$sql = "SELECT p.id AS product_id, v.id AS vendor_id, v.name AS vendor_name, v.email AS vendor_email, v.phone AS vendor_phone
        FROM products p
        LEFT JOIN vendors v ON p.vendor_id = v.id
        WHERE p.id IN ($placeholders)";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param($types, ...$productIds);
$stmt->execute();
$result = $stmt->get_result();

$map = [];
while ($row = $result->fetch_assoc()) {
    $map[(int) $row['product_id']] = [
        'vendor_id' => $row['vendor_id'] ? (int) $row['vendor_id'] : null,
        'vendor_name' => $row['vendor_name'] ?? '',
        'vendor_email' => $row['vendor_email'] ?? '',
        'vendor_phone' => $row['vendor_phone'] ?? ''
    ];
}

echo json_encode($map);
$conn->close();
?>


