<?php
require 'db.php';
header('Content-Type: application/json');

// Read comma-separated ids from query: ids=1,2,3
$idsParam = isset($_GET['ids']) ? trim($_GET['ids']) : '';
if ($idsParam === '') {
    echo json_encode([]);
    exit;
}

// Sanitize and deduplicate
$rawIds = array_filter(array_map('trim', explode(',', $idsParam)), function ($v) {
    return $v !== '';
});
$ids = array_values(array_unique(array_map('intval', $rawIds)));

if (count($ids) === 0) {
    echo json_encode([]);
    exit;
}

// Discover available vendor columns to build a safe SELECT
$colsRes = $conn->query("DESCRIBE vendors");
$available = [];
if ($colsRes) {
    while ($r = $colsRes->fetch_assoc()) {
        $available[] = strtolower($r['Field']);
    }
}

$desired = ['id','name','email','phone','address','city','state','postal_code','country','latitude','longitude','business_name','vendor_type','shop_name'];
$selectCols = array_values(array_intersect($desired, $available));
if (empty($selectCols)) { $selectCols = ['id','name','email']; }

// Build placeholders for prepared statement
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$sql = "SELECT " . implode(',', array_map(function($c){return "`$c`";}, $selectCols)) . " FROM vendors WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param($types, ...$ids);
$stmt->execute();
$result = $stmt->get_result();

$vendors = [];
while ($row = $result->fetch_assoc()) {
    $id = isset($row['id']) ? (int)$row['id'] : null;
    if ($id === null) { continue; }
    $vendors[$id] = $row;
}

echo json_encode($vendors);
$conn->close();
?>


