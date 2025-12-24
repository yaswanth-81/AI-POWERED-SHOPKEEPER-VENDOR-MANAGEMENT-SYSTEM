<?php
// Prevent any HTML output before processing
ob_start();

// Check if database connection is available
if (!file_exists('db.php')) {
    ob_end_clean();
    echo "error: Database configuration file not found";
    exit;
}

require 'db.php';

// Check if database connection is working
if (!isset($conn) || $conn->connect_error) {
    ob_end_clean();
    echo "error: Database connection failed: " . ($conn->connect_error ?? 'Unknown error');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo "error: Invalid request method";
    exit;
}

// Collect and sanitize input
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$country = trim($_POST['country'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$shop_name = trim($_POST['shop_name'] ?? '');
$shop_type = trim($_POST['shop_type'] ?? '');
$business_name = trim($_POST['business_name'] ?? '');
$vendor_type = trim($_POST['vendor_type'] ?? '');

// Validate required fields
$errors = [];

if (empty($first_name)) $errors[] = "First name is required";
if (empty($last_name)) $errors[] = "Last name is required";
if (empty($email)) $errors[] = "Email is required";
if (empty($password)) $errors[] = "Password is required";
if (empty($role)) $errors[] = "Role is required";
if (empty($address)) $errors[] = "Address is required";
if (empty($city)) $errors[] = "City is required";
if (empty($state)) $errors[] = "State is required";
if (empty($postal_code)) $errors[] = "Postal code is required";
if (empty($country)) $errors[] = "Country is required";
if (empty($phone)) $errors[] = "Phone is required";

// Validate email format
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

// Validate password length
if (!empty($password) && strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long";
}

// Validate role
if (!empty($role) && !in_array($role, ['shopkeeper', 'vendor', 'admin'])) {
    $errors[] = "Invalid role selected";
}

// If there are validation errors, return error
if (!empty($errors)) {
    $error_message = implode(", ", $errors);
    ob_end_clean();
    echo "error: " . $error_message;
    exit;
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, address, city, state, postal_code, country, phone, shop_name, shop_type, business_name, vendor_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        ob_end_clean();
        echo "error: Database query preparation failed: " . $conn->error;
        exit;
    }
    
    $stmt->bind_param("sssssssssssssss", $first_name, $last_name, $email, $hashed_password, $role, $address, $city, $state, $postal_code, $country, $phone, $shop_name, $shop_type, $business_name, $vendor_type);
    
    if ($stmt->execute()) {
        // Success - redirect based on role
        $user_id = $conn->insert_id;
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = $role;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        
        // If vendor, upsert into vendors table so shopkeepers can see vendor details
        if ($role === 'vendor') {
            // Ensure vendors table exists/has columns (best-effort)
            $conn->query("CREATE TABLE IF NOT EXISTS vendors (id INT(11) NOT NULL PRIMARY KEY, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            // Add optional columns if missing
            $conn->query("ALTER TABLE vendors ADD COLUMN phone VARCHAR(50) NULL");
            $conn->query("ALTER TABLE vendors ADD COLUMN address VARCHAR(255) NULL");
            $conn->query("ALTER TABLE vendors ADD COLUMN city VARCHAR(100) NULL");
            $conn->query("ALTER TABLE vendors ADD COLUMN state VARCHAR(100) NULL");
            $conn->query("ALTER TABLE vendors ADD COLUMN postal_code VARCHAR(20) NULL");
            $conn->query("ALTER TABLE vendors ADD COLUMN country VARCHAR(100) NULL");
            $conn->query("ALTER TABLE vendors ADD COLUMN business_name VARCHAR(255) NULL");
            $conn->query("ALTER TABLE vendors ADD COLUMN vendor_type VARCHAR(100) NULL");
            $conn->query("ALTER TABLE vendors ADD COLUMN shop_name VARCHAR(255) NULL");

            $vendor_name = !empty($business_name) ? $business_name : trim($first_name . ' ' . $last_name);
            $upsert = $conn->prepare("INSERT INTO vendors (id, name, email, password, phone, address, city, state, postal_code, country, business_name, vendor_type, shop_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), phone=VALUES(phone), address=VALUES(address), city=VALUES(city), state=VALUES(state), postal_code=VALUES(postal_code), country=VALUES(country), business_name=VALUES(business_name), vendor_type=VALUES(vendor_type), shop_name=VALUES(shop_name)");
            if ($upsert) {
                $upsert->bind_param('issssssssssss', $user_id, $vendor_name, $email, $hashed_password, $phone, $address, $city, $state, $postal_code, $country, $business_name, $vendor_type, $shop_name);
                $upsert->execute();
                $upsert->close();
            }
        }

        $stmt->close();
        $conn->close();
        ob_end_clean();
        echo "success";
        exit;
    } else {
        throw new Exception("Failed to insert user: " . $stmt->error);
    }
    
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        // Duplicate email
        ob_end_clean();
        echo "duplicate";
    } else {
        // Other database error
        ob_end_clean();
        echo "error: Database error: " . $e->getMessage();
    }
    exit;
} catch (Exception $e) {
    // General error
    ob_end_clean();
    echo "error: Registration failed: " . $e->getMessage();
    exit;
}

$stmt->close();
$conn->close();
?> 