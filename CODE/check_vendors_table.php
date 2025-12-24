<?php
require 'db.php';

// Check if vendors table exists
$check_table = "SHOW TABLES LIKE 'vendors'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    // Create vendors table if it doesn't exist
    $create_table = "CREATE TABLE vendors (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table) === TRUE) {
        echo "<p>Vendors table created successfully</p>";
    } else {
        echo "<p>Error creating vendors table: " . $conn->error . "</p>";
    }
    
    // Check if there are any users with role 'vendor' in the users table
    $check_vendors = "SELECT * FROM users WHERE role = 'vendor'";
    $vendors_result = $conn->query($check_vendors);
    
    if ($vendors_result && $vendors_result->num_rows > 0) {
        echo "<p>Migrating vendors from users table...</p>";
        
        while ($vendor = $vendors_result->fetch_assoc()) {
            // Check if name field exists, otherwise use username or email as name
            $name = isset($vendor['name']) ? $vendor['name'] : 
                   (isset($vendor['username']) ? $vendor['username'] : 
                   (isset($vendor['email']) ? explode('@', $vendor['email'])[0] : 'Vendor'));
            
            $name = $conn->real_escape_string($name);
            $email = $conn->real_escape_string($vendor['email']);
            $password = $conn->real_escape_string($vendor['password']);
            
            $insert_vendor = "INSERT INTO vendors (id, name, email, password) 
                              VALUES ({$vendor['id']}, '{$name}', '{$email}', '{$password}')";
            
            if ($conn->query($insert_vendor) === TRUE) {
                echo "<p>Migrated vendor: {$name}</p>";
            } else {
                echo "<p>Error migrating vendor {$name}: " . $conn->error . "</p>";
            }
        }
    } else {
        // Insert sample vendor data if no vendors exist
        $insert_sample = "INSERT INTO vendors (name, email, password) VALUES 
                         ('Fresh Farms', 'freshfarms@example.com', '$2y$10$abcdefghijklmnopqrstuv'),
                         ('Organic Supplies', 'organic@example.com', '$2y$10$abcdefghijklmnopqrstuv'),
                         ('Local Produce', 'local@example.com', '$2y$10$abcdefghijklmnopqrstuv')";
        
        if ($conn->query($insert_sample) === TRUE) {
            echo "<p>Sample vendor data inserted successfully</p>";
        } else {
            echo "<p>Error inserting sample vendor data: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p>Vendors table already exists</p>";
    
    // Display vendors table structure
    $table_structure = "DESCRIBE vendors";
    $structure_result = $conn->query($table_structure);
    
    if ($structure_result) {
        echo "<h3>Vendors Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $structure_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Display sample vendor data
    $sample_data = "SELECT * FROM vendors LIMIT 5";
    $data_result = $conn->query($sample_data);
    
    if ($data_result && $data_result->num_rows > 0) {
        echo "<h3>Sample Vendor Data:</h3>";
        echo "<table border='1'>";
        echo "<tr>";
        
        // Get field names
        $fields = $data_result->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>{$field->name}</th>";
        }
        echo "</tr>";
        
        // Reset data pointer
        $data_result->data_seek(0);
        
        // Output data
        while ($row = $data_result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No vendor data available</p>";
    }
}

echo "<p><a href='vendor_dashboard.php'>Return to Vendor Dashboard</a></p>";
echo "<p><a href='shopkeeper_dashboard.php'>Go to Shopkeeper Dashboard</a></p>";

$conn->close();
?>