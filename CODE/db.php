<?php
// Updated to use MySQL port 3307 (matches your XAMPP configuration)
$host = 'localhost:3307';  // MySQL port 3307
$db   = 'hackathon_db';
$user = 'root';
$pass = '';

// Check if MySQL server is running
$connection_test = @mysqli_connect($host, $user, $pass);

if (!$connection_test) {
    die("Database connection failed: MySQL server is not running. Please start XAMPP MySQL service.");
}

// Try to connect to the specific database
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // If database doesn't exist, create it
    if ($conn->connect_errno == 1049) {
        $conn = new mysqli($host, $user, $pass);
        $sql = "CREATE DATABASE IF NOT EXISTS $db";
        if ($conn->query($sql) === TRUE) {
            $conn->select_db($db);
            // Create users table if it doesn't exist
            createUsersTable($conn);
        } else {
            die("Error creating database: " . $conn->error);
        }
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

function createUsersTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('shopkeeper', 'vendor', 'admin') NOT NULL,
        address TEXT,
        city VARCHAR(50),
        state VARCHAR(50),
        postal_code VARCHAR(20),
        country VARCHAR(50),
        phone VARCHAR(20),
        shop_name VARCHAR(100),
        shop_type VARCHAR(50),
        business_name VARCHAR(100),
        vendor_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating users table: " . $conn->error);
    }
}
?>