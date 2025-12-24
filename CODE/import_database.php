<?php
// Database Import Script
// This script will help you import your friend's SQL file

echo "<h1>Database Import Tool</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
</style>";

// Check if MySQL is running
$host = 'localhost:3307';  // Updated to use port 3307
$user = 'root';
$pass = '';

$test_connection = @mysqli_connect($host, $user, $pass);

if (!$test_connection) {
    echo "<p class='error'>✗ MySQL service is not running!</p>";
    echo "<p class='warning'>Please start MySQL service in XAMPP Control Panel first.</p>";
    exit();
}

echo "<p class='success'>✓ MySQL service is running on port 3307</p>";

// Check if SQL file exists
$sql_file = 'hackathon_db.sql'; // Your friend's exported SQL file

if (!file_exists($sql_file)) {
    echo "<p class='warning'>⚠ SQL file '$sql_file' not found in the current directory.</p>";
    echo "<p class='info'>Please place your friend's exported SQL file in this directory and rename it to 'hackathon_db.sql'</p>";
    echo "<p class='info'>Or upload your SQL file using the form below:</p>";
    
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<input type='file' name='sqlfile' accept='.sql' required>";
    echo "<input type='submit' value='Import SQL File'>";
    echo "</form>";
    
    if ($_FILES && isset($_FILES['sqlfile'])) {
        $uploaded_file = $_FILES['sqlfile']['tmp_name'];
        $sql_content = file_get_contents($uploaded_file);
        
        if ($sql_content) {
            echo "<p class='success'>✓ SQL file uploaded successfully</p>";
            importSQL($sql_content);
        } else {
            echo "<p class='error'>✗ Failed to read uploaded SQL file</p>";
        }
    }
} else {
    echo "<p class='success'>✓ SQL file found: $sql_file</p>";
    
    // Read and import the SQL file
    $sql_content = file_get_contents($sql_file);
    
    if ($sql_content) {
        echo "<p class='success'>✓ SQL file read successfully</p>";
        importSQL($sql_content);
    } else {
        echo "<p class='error'>✗ Failed to read SQL file</p>";
    }
}

function importSQL($sql_content) {
    global $host, $user, $pass;
    
    // Connect to MySQL
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        echo "<p class='error'>✗ Cannot connect to MySQL: " . $conn->connect_error . "</p>";
        return;
    }
    
    echo "<p class='success'>✓ Connected to MySQL on port 3307</p>";
    
    // Create database if it doesn't exist
    $db_name = 'hackathon_db';
    $conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
    $conn->select_db($db_name);
    
    echo "<p class='success'>✓ Database '$db_name' ready</p>";
    
    // Split SQL into individual statements
    $statements = explode(';', $sql_content);
    $success_count = 0;
    $error_count = 0;
    
    echo "<h3>Importing SQL statements...</h3>";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (!empty($statement)) {
            if ($conn->query($statement)) {
                $success_count++;
                echo "<p class='success'>✓ Statement executed successfully</p>";
            } else {
                $error_count++;
                echo "<p class='error'>✗ Error executing statement: " . $conn->error . "</p>";
            }
        }
    }
    
    echo "<h3>Import Summary</h3>";
    echo "<p class='success'>✓ Successful statements: $success_count</p>";
    echo "<p class='error'>✗ Failed statements: $error_count</p>";
    
    // Test the imported data
    echo "<h3>Testing Imported Data</h3>";
    
    $tables = ['users', 'products', 'orders', 'order_items'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            if ($count_result) {
                $count = $count_result->fetch_assoc()['count'];
                echo "<p class='success'>✓ Table '$table' exists with $count records</p>";
            }
        } else {
            echo "<p class='warning'>⚠ Table '$table' not found</p>";
        }
    }
    
    $conn->close();
    
    echo "<h3>Next Steps</h3>";
    echo "<p>If the import was successful:</p>";
    echo "<ul>";
    echo "<li><a href='test_database_connection.php'>Run Database Connection Test</a></li>";
    echo "<li><a href='login page.html'>Test Login</a></li>";
    echo "<li><a href='signup page.html'>Test Signup</a></li>";
    echo "</ul>";
}

echo "<h3>Manual Import Instructions</h3>";
echo "<p>If the automatic import doesn't work, you can manually import using phpMyAdmin:</p>";
echo "<ol>";
echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
echo "<li>Create a new database named 'hackathon_db'</li>";
echo "<li>Select the database and go to 'Import' tab</li>";
echo "<li>Choose your SQL file and click 'Go'</li>";
echo "<li>After import, test the connection using the link above</li>";
echo "</ol>";
?>
