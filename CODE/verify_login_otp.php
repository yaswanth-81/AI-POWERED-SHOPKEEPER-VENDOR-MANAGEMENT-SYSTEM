<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$role = trim($data['role'] ?? '');
$otp = trim($data['otp'] ?? '');

if (empty($email) || empty($role) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Email, role, and OTP are required']);
    exit;
}

// Clean OTP - remove any spaces and ensure it's 6 digits
$otp = preg_replace('/\s+/', '', $otp);

if (strlen($otp) !== 6 || !ctype_digit($otp)) {
    echo json_encode(['success' => false, 'message' => 'OTP must be exactly 6 digits']);
    exit;
}

// Verify OTP - check if exists and not expired
$stmt = $conn->prepare("SELECT *, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_remaining FROM login_otp_tokens WHERE email = ? AND role = ? AND otp = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("sss", $email, $role, $otp);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please check and try again.']);
    $stmt->close();
    exit;
}

$otp_data = $result->fetch_assoc();
$stmt->close();

// Check if OTP is expired
if ($otp_data['seconds_remaining'] <= 0) {
    // Delete expired OTP
    $delete_expired = $conn->prepare("DELETE FROM login_otp_tokens WHERE email = ? AND role = ? AND otp = ?");
    $delete_expired->bind_param("sss", $email, $role, $otp);
    $delete_expired->execute();
    $delete_expired->close();
    
    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
    exit;
}

// OTP is valid - get user info and create session
$user_stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE email = ? AND role = ?");
$user_stmt->bind_param("ss", $email, $role);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    $user_stmt->close();
    exit;
}

$user = $user_result->fetch_assoc();
$user_stmt->close();

// Create session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $role;
$_SESSION['email'] = $email;
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];

// Delete used OTP
$delete_stmt = $conn->prepare("DELETE FROM login_otp_tokens WHERE email = ? AND role = ? AND otp = ?");
$delete_stmt->bind_param("sss", $email, $role, $otp);
$delete_stmt->execute();
$delete_stmt->close();

echo json_encode(['success' => true, 'message' => 'Login successful']);

$conn->close();
?>

