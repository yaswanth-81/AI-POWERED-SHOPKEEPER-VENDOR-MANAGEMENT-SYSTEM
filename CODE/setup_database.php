<?php
// Database setup script
// This script will create the database and all necessary tables

$host = 'localhost:3307';  // Updated to use port 3307
$user = 'root';
$pass = '';

echo "<h1>Database Setup</h1>";

// First, connect without database
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "<br>Please make sure XAMPP MySQL service is running on port 3307.");
}

echo "<p style='color: green;'>✓ Connected to MySQL server successfully on port 3307</p>";

// Create database
$db_name = 'hackathon_db';
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Database '$db_name' created or already exists</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating database: " . $conn->error . "</p>";
}

// Select the database
$conn->select_db($db_name);

// Create users table
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

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Users table created or already exists</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating users table: " . $conn->error . "</p>";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    vendor_id INT,
    image_path VARCHAR(255),
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Products table created or already exists</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating products table: " . $conn->error . "</p>";
}

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Orders table created or already exists</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating orders table: " . $conn->error . "</p>";
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Order items table created or already exists</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating order items table: " . $conn->error . "</p>";
}

$conn->close();

echo "<h2>Setup Complete!</h2>";
echo "<p>Your database is now ready to use. You can:</p>";
echo "<ul>";
echo "<li><a href='signup page.html'>Go to Signup Page</a></li>";
echo "<li><a href='login page.html'>Go to Login Page</a></li>";
echo "<li><a href='home.html'>Go to Home Page</a></li>";
echo "</ul>";
?>
