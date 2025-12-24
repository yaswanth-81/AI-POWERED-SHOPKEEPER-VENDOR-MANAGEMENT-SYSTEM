<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

// Verify session
if (!isset($_SESSION['password_reset_verified']) || !$_SESSION['password_reset_verified']) {
    echo json_encode(['success' => false, 'message' => 'OTP verification required. Please verify OTP first.']);
    exit;
}

// Check if session is not expired (15 minutes)
if (isset($_SESSION['password_reset_time']) && (time() - $_SESSION['password_reset_time']) > 900) {
    unset($_SESSION['password_reset_verified']);
    unset($_SESSION['password_reset_email']);
    unset($_SESSION['password_reset_time']);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
    exit;
}

// Verify email matches session
if (empty($email) || $email !== $_SESSION['password_reset_email']) {
    echo json_encode(['success' => false, 'message' => 'Email mismatch. Please start the process again.']);
    exit;
}

// Validate password
if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

// Check if email exists
$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Email not found in database']);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Hash the new password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Update password in database
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {
    // Clear session
    unset($_SESSION['password_reset_verified']);
    unset($_SESSION['password_reset_email']);
    unset($_SESSION['password_reset_time']);
    
    // Also delete any remaining OTP tokens for this email
    $cleanup_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
    $cleanup_stmt->bind_param("s", $email);
    $cleanup_stmt->execute();
    $cleanup_stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>

