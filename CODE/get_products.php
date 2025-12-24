<?php
require 'db.php';

header('Content-Type: application/json');

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// Build vendor columns dynamically to avoid SQL errors if some columns are missing
$vendorAvailable = [];
try {
    $vendorColsRes = $conn->query("DESCRIBE vendors");
    if ($vendorColsRes) {
        while ($r = $vendorColsRes->fetch_assoc()) {
            $vendorAvailable[] = strtolower($r['Field']);
        }
    }
} catch (Exception $e) {
    // If vendors table doesn't exist, just use empty array
    $vendorAvailable = [];
}

function pickVendorCol($available, $col, $alias) {
    return in_array($col, $available) ? "v.`$col` AS `$alias`" : "NULL AS `$alias`";
}

$vendorSelectParts = [];
$vendorSelectParts[] = pickVendorCol($vendorAvailable, 'name', 'vendor_name');
$vendorSelectParts[] = pickVendorCol($vendorAvailable, 'email', 'vendor_email');
$vendorSelectParts[] = pickVendorCol($vendorAvailable, 'phone', 'vendor_phone');
$vendorSelectParts[] = pickVendorCol($vendorAvailable, 'address', 'vendor_address');
$vendorSelectParts[] = pickVendorCol($vendorAvailable, 'city', 'vendor_city');
$vendorSelectParts[] = pickVendorCol($vendorAvailable, 'state', 'vendor_state');
$vendorSelectParts[] = pickVendorCol($vendorAvailable, 'postal_code', 'vendor_postal_code');
$vendorSelectParts[] = pickVendorCol($vendorAvailable, 'country', 'vendor_country');

// Base query with safe vendor selection and explicit stock selection
// Select all product columns, then add normalized stock fields
$vendorSelect = !empty($vendorSelectParts) ? ", " . implode(",\n               ", $vendorSelectParts) : "";
$sql = "SELECT p.*" . $vendorSelect . "
        FROM products p";
        
// Only add JOIN if vendors table exists and vendor_id column exists
if (!empty($vendorSelectParts)) {
    $sql .= " LEFT JOIN vendors v ON p.vendor_id = v.id";
}

// Add WHERE clauses for filtering
$where_clauses = [];
$params = [];
$types = "";

if ($category) {
    $where_clauses[] = "p.category LIKE ?";
    $params[] = "%$category%";
    $types .= "s";
}

if ($search) {
    $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($min_price !== null) {
    $where_clauses[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price !== null) {
    $where_clauses[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

// Combine WHERE clauses if any exist
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Add ORDER BY clause
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'stock':
        // Order by whichever stock column exists
        $sql .= " ORDER BY COALESCE(p.stock, p.stock_quantity, 0) DESC";
        break;
    default:
        $sql .= " ORDER BY p.name ASC";
}

// Prepare and execute the statement
try {
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("SQL prepare error: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("SQL execute error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("SQL result error: " . $conn->error);
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Normalize stock field for frontend - always ensure stock is set
        // Priority: stock_quantity > stock > 0
        if (isset($row['stock_quantity']) && $row['stock_quantity'] !== null) {
            $row['stock'] = (int)$row['stock_quantity'];
        } elseif (isset($row['stock']) && $row['stock'] !== null) {
            $row['stock'] = (int)$row['stock'];
        } else {
            $row['stock'] = 0;
        }
        // Ensure stock is always a number, never undefined
        if (!isset($row['stock']) || $row['stock'] === null || $row['stock'] === '') {
            $row['stock'] = 0;
        }
        // Normalize image field
        if (empty($row['image_url']) && !empty($row['image_path'])) {
            $row['image_url'] = 'uploads/products/' . $row['image_path'];
        }
        $products[] = $row;
    }
    
    echo json_encode($products);
    $stmt->close();
} catch (Exception $e) {
    // Return error as JSON so frontend can handle it
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
$conn->close();
?>