<?php
include 'db.php';

// Get table structure
$sql = "DESCRIBE products";
$result = $conn->query($sql);

echo "<h2>Products Table Structure:</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["Field"] . "</td>";
        echo "<td>" . $row["Type"] . "</td>";
        echo "<td>" . $row["Null"] . "</td>";
        echo "<td>" . $row["Key"] . "</td>";
        echo "<td>" . $row["Default"] . "</td>";
        echo "<td>" . $row["Extra"] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No results or table doesn't exist</td></tr>";
}
echo "</table>";

// Get sample data
$sql = "SELECT * FROM products LIMIT 1";
$result = $conn->query($sql);

echo "<h2>Sample Product Data:</h2>";
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "No products found in the database.";
}

$conn->close();
?>