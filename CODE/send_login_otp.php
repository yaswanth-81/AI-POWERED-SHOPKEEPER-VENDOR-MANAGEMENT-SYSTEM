<?php
require 'db.php';
header('Content-Type: application/json');

// Create login_otp_tokens table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS login_otp_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_role (email, role),
    INDEX idx_expires (expires_at)
)";
$conn->query($create_table);

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$role = trim($data['role'] ?? '');

if (empty($email) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'Email and role are required']);
    exit;
}

// Verify user exists with this email and role
$stmt = $conn->prepare("SELECT id, email, first_name, last_name FROM users WHERE email = ? AND role = ?");
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Generate 6-digit OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Delete old OTPs for this email and role
$delete_stmt = $conn->prepare("DELETE FROM login_otp_tokens WHERE email = ? AND role = ?");
$delete_stmt->bind_param("ss", $email, $role);
$delete_stmt->execute();
$delete_stmt->close();

// Insert new OTP - expires in 10 minutes
$insert_stmt = $conn->prepare("INSERT INTO login_otp_tokens (email, role, otp, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
$insert_stmt->bind_param("sss", $email, $role, $otp);

if (!$insert_stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to generate OTP. Please try again.']);
    exit;
}
$insert_stmt->close();

// Send OTP via email using PHPMailer
try {
    if (file_exists('vendor/autoload.php')) {
        @require_once 'vendor/autoload.php';
        
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
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
            $mail->Subject = 'Login OTP - Marketplace AI';
            $mail->Body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .otp-box { background-color: white; padding: 30px; margin: 20px 0; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .otp-code { font-size: 36px; font-weight: bold; color: #667eea; letter-spacing: 8px; font-family: monospace; }
                    .warning { color: #e11d48; font-size: 14px; margin-top: 15px; }
                    .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>Login Verification</h1>
                    </div>
                    <div class="content">
                        <p>Dear ' . htmlspecialchars($user['first_name']) . ',</p>
                        <p>You have successfully verified your password. Use the OTP below to complete your login:</p>
                        
                        <div class="otp-box">
                            <p style="margin-bottom: 15px; color: #666;">Your Login OTP is:</p>
                            <div class="otp-code">' . $otp . '</div>
                        </div>
                        
                        <p class="warning"><strong>This OTP will expire in 10 minutes.</strong></p>
                        <p>If you did not attempt to login, please ignore this email and consider changing your password.</p>
                        <p>Best regards,<br><strong>Marketplace AI Team</strong></p>
                    </div>
                </div>
            </body>
            </html>';
            
            $mail->send();
            echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
        } else {
            echo json_encode(['success' => true, 'message' => 'OTP generated: ' . $otp . ' (Email sending not configured)']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'OTP generated: ' . $otp . ' (Email sending not configured)']);
    }
} catch (Exception $e) {
    error_log("Email error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please contact support.']);
}

$conn->close();
?>

