<?php
require 'db.php';

header('Content-Type: text/plain');

function columnExists(mysqli $conn, string $table, string $column): bool {
    $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->bind_param('s', $column);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res && $res->num_rows > 0;
}

// Create vendors table if it does not exist
$createSql = <<<SQL
CREATE TABLE IF NOT EXISTS vendors (
  id INT(11) NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

if (!$conn->query($createSql)) {
    echo "Failed creating base vendors table: {$conn->error}\n";
}

// Add missing vendor profile columns
$columnsToAdd = [
  'phone' => 'VARCHAR(50) NULL',
  'address' => 'VARCHAR(255) NULL',
  'city' => 'VARCHAR(100) NULL',
  'state' => 'VARCHAR(100) NULL',
  'postal_code' => 'VARCHAR(20) NULL',
  'country' => 'VARCHAR(100) NULL',
  'latitude' => 'DECIMAL(10,7) NULL',
  'longitude' => 'DECIMAL(10,7) NULL',
  'business_name' => 'VARCHAR(255) NULL',
  'vendor_type' => 'VARCHAR(100) NULL',
  'shop_name' => 'VARCHAR(255) NULL',
  'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
];

foreach ($columnsToAdd as $col => $definition) {
    if (!columnExists($conn, 'vendors', $col)) {
        $sql = "ALTER TABLE vendors ADD COLUMN `$col` $definition";
        if ($conn->query($sql)) {
            echo "Added column vendors.$col\n";
        } else {
            echo "Failed adding column $col: {$conn->error}\n";
        }
    }
}

// Migrate/update vendor rows from users table for users with role='vendor'
$migrateSql = <<<SQL
INSERT INTO vendors (id, name, email, password, phone, address, city, state, postal_code, country, business_name, vendor_type, shop_name)
SELECT u.id,
       COALESCE(NULLIF(u.business_name, ''), CONCAT_WS(' ', NULLIF(u.first_name,''), NULLIF(u.last_name,''))),
       u.email,
       u.password,
       u.phone,
       u.address,
       u.city,
       u.state,
       u.postal_code,
       u.country,
       u.business_name,
       u.vendor_type,
       u.shop_name
FROM users u
WHERE u.role = 'vendor'
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  email = VALUES(email),
  phone = VALUES(phone),
  address = VALUES(address),
  city = VALUES(city),
  state = VALUES(state),
  postal_code = VALUES(postal_code),
  country = VALUES(country),
  business_name = VALUES(business_name),
  vendor_type = VALUES(vendor_type),
  shop_name = VALUES(shop_name);
SQL;

if ($conn->query($migrateSql)) {
    echo "Vendor records synchronized from users table.\n";
} else {
    echo "Failed syncing vendors: {$conn->error}\n";
}

echo "Done.";

$conn->close();
?>


