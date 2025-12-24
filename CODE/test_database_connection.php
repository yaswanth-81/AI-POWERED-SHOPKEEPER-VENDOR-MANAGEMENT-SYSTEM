<?php
// Comprehensive Database Connection Test
// This script will help diagnose your database connection issues

echo "<h1>Database Connection Test</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
</style>";

// Test 1: Check if MySQL service is running
echo "<h2>Step 1: MySQL Service Check</h2>";
$host = 'localhost:3307';  // Updated to use port 3307
$user = 'root';
$pass = '';

// Try to connect without specifying database
$test_connection = @mysqli_connect($host, $user, $pass);

if ($test_connection) {
    echo "<p class='success'>✓ MySQL service is running and accessible on port 3307</p>";
    
    // Get MySQL version
    $version = mysqli_get_server_info($test_connection);
    echo "<p class='info'>MySQL Version: $version</p>";
    
    // List all databases
    echo "<h3>Available Databases:</h3>";
    $result = mysqli_query($test_connection, "SHOW DATABASES");
    if ($result) {
        echo "<ul>";
        while ($row = mysqli_fetch_array($result)) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    
    mysqli_close($test_connection);
} else {
    echo "<p class='error'>✗ MySQL service is NOT running or not accessible on port 3307</p>";
    echo "<p class='warning'>Solution: Start MySQL service in XAMPP Control Panel</p>";
    echo "<p class='info'>Error: " . mysqli_connect_error() . "</p>";
    exit();
}

// Test 2: Check if hackathon_db exists
echo "<h2>Step 2: Database Check</h2>";
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    echo "<p class='error'>✗ Cannot connect to MySQL: " . $conn->connect_error . "</p>";
    exit();
}

$db_name = 'hackathon_db';
$result = $conn->query("SHOW DATABASES LIKE '$db_name'");

if ($result->num_rows > 0) {
    echo "<p class='success'>✓ Database '$db_name' exists</p>";
} else {
    echo "<p class='warning'>⚠ Database '$db_name' does not exist</p>";
    echo "<p class='info'>Creating database...</p>";
    
    if ($conn->query("CREATE DATABASE $db_name")) {
        echo "<p class='success'>✓ Database '$db_name' created successfully</p>";
    } else {
        echo "<p class='error'>✗ Failed to create database: " . $conn->error . "</p>";
        exit();
    }
}

// Test 3: Connect to the specific database
echo "<h2>Step 3: Database Connection Test</h2>";
$conn->select_db($db_name);

if ($conn->error) {
    echo "<p class='error'>✗ Cannot select database: " . $conn->error . "</p>";
    exit();
} else {
    echo "<p class='success'>✓ Successfully connected to database '$db_name'</p>";
}

// Test 4: Check if tables exist
echo "<h2>Step 4: Table Check</h2>";
$tables = ['users', 'products', 'orders', 'order_items'];
$missing_tables = [];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p class='success'>✓ Table '$table' exists</p>";
        
        // Count rows in table
        $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($count_result) {
            $count = $count_result->fetch_assoc()['count'];
            echo "<p class='info'>  - Records in $table: $count</p>";
        }
    } else {
        echo "<p class='warning'>⚠ Table '$table' does not exist</p>";
        $missing_tables[] = $table;
    }
}

// Test 5: Create missing tables if needed
if (!empty($missing_tables)) {
    echo "<h2>Step 5: Creating Missing Tables</h2>";
    
    foreach ($missing_tables as $table) {
        echo "<p class='info'>Creating table: $table</p>";
        
        switch ($table) {
            case 'users':
                $sql = "CREATE TABLE users (
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
                break;
                
            case 'products':
                $sql = "CREATE TABLE products (
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
                break;
                
            case 'orders':
                $sql = "CREATE TABLE orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    total_amount DECIMAL(10,2) NOT NULL,
                    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                    shipping_address TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                break;
                
            case 'order_items':
                $sql = "CREATE TABLE order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    product_id INT NOT NULL,
                    quantity INT NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
                )";
                break;
        }
        
        if ($conn->query($sql)) {
            echo "<p class='success'>✓ Table '$table' created successfully</p>";
        } else {
            echo "<p class='error'>✗ Failed to create table '$table': " . $conn->error . "</p>";
        }
    }
}

// Test 6: Test user authentication
echo "<h2>Step 6: Authentication Test</h2>";
$test_email = "test@example.com";
$test_password = "testpassword123";

// Check if test user exists
$stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p class='success'>✓ Test user exists in database</p>";
} else {
    echo "<p class='info'>Creating test user for authentication testing...</p>";
    
    // Create test user
    $hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
    $insert_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, address, city, state, postal_code, country, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $first_name = "Test";
    $last_name = "User";
    $role = "shopkeeper";
    $address = "Test Address";
    $city = "Test City";
    $state = "Test State";
    $postal_code = "12345";
    $country = "Test Country";
    $phone = "1234567890";
    
    $insert_stmt->bind_param("sssssssssss", $first_name, $last_name, $test_email, $hashed_password, $role, $address, $city, $state, $postal_code, $country, $phone);
    
    if ($insert_stmt->execute()) {
        echo "<p class='success'>✓ Test user created successfully</p>";
        echo "<p class='info'>Test credentials: Email: $test_email, Password: $test_password</p>";
    } else {
        echo "<p class='error'>✗ Failed to create test user: " . $insert_stmt->error . "</p>";
    }
    
    $insert_stmt->close();
}

$stmt->close();

// Test 7: Final connection test
echo "<h2>Step 7: Final Connection Test</h2>";
require_once 'db.php';

if (isset($conn) && !$conn->connect_error) {
    echo "<p class='success'>✓ Database connection is working properly!</p>";
    echo "<p class='info'>Your login and signup should now work correctly.</p>";
} else {
    echo "<p class='error'>✗ Database connection is still failing</p>";
    echo "<p class='warning'>Please check your db.php file configuration</p>";
}

$conn->close();

echo "<h2>Next Steps</h2>";
echo "<p>If all tests passed:</p>";
echo "<ul>";
echo "<li><a href='signup page.html'>Test Signup</a></li>";
echo "<li><a href='login page.html'>Test Login</a></li>";
echo "<li><a href='home.html'>Go to Home Page</a></li>";
echo "</ul>";

echo "<p>If you're still having issues:</p>";
echo "<ol>";
echo "<li>Make sure XAMPP MySQL service is running (green status)</li>";
echo "<li>Check if port 3307 is not being used by another application</li>";
echo "<li>Restart XAMPP services if needed</li>";
echo "<li>Check your firewall settings</li>";
echo "</ol>";
?>
