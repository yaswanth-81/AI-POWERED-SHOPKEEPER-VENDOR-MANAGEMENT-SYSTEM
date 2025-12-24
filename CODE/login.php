<?php
// Check if database connection is available
if (!file_exists('db.php')) {
    echo "error: Database configuration file not found";
    exit;
}

require 'db.php';

// Check if database connection is working
if (!isset($conn) || $conn->connect_error) {
    echo "error: Database connection failed: " . ($conn->connect_error ?? 'Unknown error');
    exit;
}

// Check if POST variables exist before accessing them
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : '';

// Validate that required fields are not empty
if (empty($email) || empty($password) || empty($role)) {
    echo "missing";
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "error: Invalid email format";
    exit;
}

// Validate role
if (!in_array($role, ['shopkeeper', 'vendor', 'admin'])) {
    echo "error: Invalid role selected";
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, password, role, first_name, last_name FROM users WHERE email = ? AND role = ?");
    if (!$stmt) {
        echo "error: Database query preparation failed: " . $conn->error;
        exit;
    }
    
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $user_role, $first_name, $last_name);
        $stmt->fetch();
        
        if (password_verify($password, $hashed_password)) {
            session_start();
            $_SESSION['user_id'] = $id;
            $_SESSION['role'] = $user_role;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            
            echo $user_role; // Return role for frontend redirection
        } else {
            echo "invalid";
        }
    } else {
        echo "notfound";
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo "error: Database error: " . $e->getMessage();
}

$conn->close();
?>