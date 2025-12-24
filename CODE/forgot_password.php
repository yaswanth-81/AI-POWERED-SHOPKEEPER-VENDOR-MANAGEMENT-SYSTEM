<?php
require 'db.php';
header('Content-Type: application/json');

// Create password_reset_tokens table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
)";
$conn->query($create_table);

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'send_otp') {
    $email = $data['email'] ?? '';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, email, first_name, last_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found in our system']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Delete old OTPs for this email
    $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
    $delete_stmt->bind_param("s", $email);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Insert new OTP - use MySQL's DATE_ADD for consistent timezone handling
    // OTP expires in 15 minutes (increased from 10 for better user experience)
    $insert_stmt = $conn->prepare("INSERT INTO password_reset_tokens (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
    $insert_stmt->bind_param("ss", $email, $otp);
    
    if (!$insert_stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate OTP. Please try again.']);
        exit;
    }
    $insert_stmt->close();
    
    // Send OTP via email using PHPMailer
    try {
        if (file_exists('vendor/autoload.php')) {
            @require_once 'vendor/autoload.php';
            
            // Check if PHPMailer class exists
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                // Use fully qualified class names (cannot use 'use' inside conditional)
                $PHPMailer = 'PHPMailer\PHPMailer\PHPMailer';
                
                // Load email configuration
                $email_config = [];
                if (file_exists('email_config.php')) {
                    $email_config = require 'email_config.php';
                } else {
                    $email_config = [
                        'smtp_host' => 'smtp.gmail.com',
                        'smtp_port' => 587,
                        'smtp_username' => 'your-email@gmail.com',
                        'smtp_password' => 'your-app-password',
                        'smtp_encryption' => 'tls',
                        'from_email' => 'noreply@marketplace.com',
                        'from_name' => 'Marketplace AI',
                    ];
                }
                
                $mail = new $PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = $email_config['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $email_config['smtp_username'];
                $mail->Password = $email_config['smtp_password'];
                if ($email_config['smtp_encryption'] === 'ssl') {
                    $mail->SMTPSecure = 2;
                } else {
                    $mail->SMTPSecure = 1;
                }
                $mail->Port = $email_config['smtp_port'];
                $mail->CharSet = 'UTF-8';
                
                // Recipients
                $mail->setFrom($email_config['from_email'], $email_config['from_name']);
                $mail->addAddress($email, $user['first_name'] . ' ' . $user['last_name']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset OTP - Marketplace AI';
                $mail->Body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                        .content { background-color: #f9f9f9; padding: 20px; }
                        .otp-box { background-color: white; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; }
                        .otp-code { font-size: 32px; font-weight: bold; color: #4CAF50; letter-spacing: 5px; }
                        .warning { color: #e11d48; font-size: 14px; margin-top: 15px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>Password Reset Request</h1>
                        </div>
                        <div class="content">
                            <p>Dear ' . htmlspecialchars($user['first_name']) . ',</p>
                            <p>You have requested to reset your password. Use the OTP below to verify your identity:</p>
                            
                            <div class="otp-box">
                                <p style="margin-bottom: 10px;">Your OTP is:</p>
                                <div class="otp-code">' . $otp . '</div>
                            </div>
                            
                            <p class="warning"><strong>This OTP will expire in 15 minutes.</strong></p>
                            <p>If you did not request this password reset, please ignore this email.</p>
                            <p>Best regards,<br>Marketplace AI Team</p>
                        </div>
                    </div>
                </body>
                </html>';
                
                $mail->send();
                echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
            } else {
                // PHPMailer class not found
                echo json_encode(['success' => true, 'message' => 'OTP generated: ' . $otp . ' (Email sending not configured)']);
            }
        } else {
            // Fallback: just return success (for testing without email)
            echo json_encode(['success' => true, 'message' => 'OTP generated: ' . $otp]);
        }
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please contact support.']);
    }
    
} elseif ($action === 'verify_otp') {
    $email = trim($data['email'] ?? '');
    $otp = trim($data['otp'] ?? '');
    
    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
        exit;
    }
    
    // Clean OTP - remove any spaces and ensure it's 6 digits
    $otp = preg_replace('/\s+/', '', $otp);
    
    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        echo json_encode(['success' => false, 'message' => 'OTP must be exactly 6 digits']);
        exit;
    }
    
    // Verify OTP - check if exists and not expired
    // Use TIMESTAMPDIFF to handle timezone issues better
    $stmt = $conn->prepare("SELECT *, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_remaining FROM password_reset_tokens WHERE email = ? AND otp = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("ss", $email, $otp);
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
        $delete_expired = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ? AND otp = ?");
        $delete_expired->bind_param("ss", $email, $otp);
        $delete_expired->execute();
        $delete_expired->close();
        
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
        exit;
    }
    
    // OTP is valid - mark it as used (delete it)
    $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ? AND otp = ?");
    $delete_stmt->bind_param("ss", $email, $otp);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Store verification in session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['password_reset_email'] = $email;
    $_SESSION['password_reset_verified'] = true;
    $_SESSION['password_reset_time'] = time();
    
    echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>

