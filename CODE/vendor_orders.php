<?php
session_start();
include 'db.php';

// Check if user is logged in as vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];

// Get filter parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';

// Check which columns exist in orders table
$check_columns = $conn->query("SHOW COLUMNS FROM orders");
$existing_columns = [];
if ($check_columns) {
    while ($col = $check_columns->fetch_assoc()) {
        $existing_columns[] = $col['Field'];
    }
}

// Build SQL query with filter - get orders that contain products from this vendor
// Only select columns that exist
$select_fields = "DISTINCT o.id, o.user_id, o.total_amount, o.status, o.created_at";
if (in_array('vendor_id', $existing_columns)) {
    $select_fields .= ", o.vendor_id";
}
if (in_array('total', $existing_columns)) {
    $select_fields .= ", o.total";
}

$sql = "SELECT $select_fields,
               u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as customer_name, 
               u.email, u.phone, u.address, u.city, u.state, u.postal_code, u.country, u.shop_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE p.vendor_id = ?";

if ($status_filter === 'active') {
    // Show only orders that need status updates (not delivered or cancelled) - include NULL as pending
    $sql .= " AND COALESCE(o.status, 'pending') IN ('pending', 'processing', 'shipped')";
} elseif ($status_filter !== 'all') {
    // Use COALESCE to treat NULL as 'pending'
    $sql .= " AND COALESCE(o.status, 'pending') = ?";
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing query: " . $conn->error);
}

if ($status_filter === 'active') {
    $stmt->bind_param("i", $vendor_id);
} elseif ($status_filter !== 'all') {
    $stmt->bind_param("is", $vendor_id, $status_filter);
} else {
    $stmt->bind_param("i", $vendor_id);
}

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo "<div style='padding: 15px; margin-bottom: 20px; background-color: #c6f6d5; color: #2f855a; border-radius: 4px;'>";
    echo htmlspecialchars($_SESSION['success_message']);
    echo "</div>";
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo "<div style='padding: 15px; margin-bottom: 20px; background-color: #fed7d7; color: #c53030; border-radius: 4px;'>";
    echo htmlspecialchars($_SESSION['error_message']);
    echo "</div>";
    unset($_SESSION['error_message']);
}

// Fix NULL status values in existing orders
$fix_null_status = $conn->query("UPDATE orders SET status = 'pending' WHERE status IS NULL");
if ($fix_null_status) {
    // Status updated successfully
}

echo "<h1>Vendor Orders</h1>";

// Filter buttons
echo "<div style='margin-bottom: 20px;'>";
echo "<h3>Filter by Status:</h3>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";

$statuses = [
    'active' => 'Active Orders',
    'all' => 'All Orders',
    'pending' => 'Pending',
    'processing' => 'Processing', 
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled'
];

foreach ($statuses as $value => $label) {
    $active_class = ($status_filter === $value) ? 'background-color: #4CAF50; color: white;' : 'background-color: #f2f2f2; color: black;';
    echo "<a href='vendor_orders.php?status=$value' style='padding: 8px 16px; text-decoration: none; border-radius: 4px; $active_class'>$label</a>";
}

echo "</div>";
echo "</div>";

// Order summary
$order_count = $result->num_rows;
echo "<div style='margin-bottom: 20px; padding: 10px; background-color: #e3f2fd; border-radius: 4px;'>";
echo "<strong>Showing $order_count ";
if ($status_filter === 'active') {
    echo "active orders (requiring status updates)";
} elseif ($status_filter === 'all') {
    echo "total orders";
} else {
    echo strtolower($statuses[$status_filter]);
}
echo "</strong>";
echo "</div>";

// Display orders
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th style='padding: 10px; text-align: left;'>Order ID</th>";
    echo "<th style='padding: 10px; text-align: left;'>Customer</th>";
    echo "<th style='padding: 10px; text-align: left;'>Products</th>";
    echo "<th style='padding: 10px; text-align: left;'>Total</th>";
    echo "<th style='padding: 10px; text-align: left;'>Status</th>";
    echo "<th style='padding: 10px; text-align: left;'>Date</th>";
    echo "<th style='padding: 10px; text-align: left;'>View Order</th>";
    echo "</tr>";
    
    while ($order = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $order['id'] . "</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
        echo "<strong>" . htmlspecialchars($order['customer_name']) . "</strong><br>";
        echo "<small>Email: " . htmlspecialchars($order['email']) . "</small><br>";
        echo "<small>Phone: " . htmlspecialchars($order['phone']) . "</small><br>";
        echo "<small>Shop: " . htmlspecialchars($order['shop_name']) . "</small><br>";
        echo "<button type='button' onclick='toggleAddress(this)' class='btn btn-sm btn-info'>Show Address</button>";
        echo "<div style='display:none; margin-top: 5px;' class='address-details'>";
        echo htmlspecialchars($order['address']) . "<br>";
        echo htmlspecialchars($order['city']) . ", " . htmlspecialchars($order['state']) . " " . htmlspecialchars($order['postal_code']) . "<br>";
        echo htmlspecialchars($order['country']);
        echo "</div>";
        echo "</td>";
        
        // Get order items
        $items_sql = "SELECT oi.*, p.name as product_name 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $order['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
        if ($items_result && $items_result->num_rows > 0) {
            while ($item = $items_result->fetch_assoc()) {
                echo htmlspecialchars($item['product_name']) . " x " . $item['quantity'] . "<br>";
            }
        } else {
            echo "No items found";
        }
        echo "</td>";
        
        // Handle total amount - use total_amount if available, otherwise calculate from order_items
        $total_amount = $order['total_amount'] ?? null;
        if ($total_amount === null && isset($order['total'])) {
            $total_amount = $order['total'];
        }
        if ($total_amount === null) {
            // Calculate total from order items if not available
            $calc_sql = "SELECT SUM(quantity * price) as calc_total FROM order_items WHERE order_id = ?";
            $calc_stmt = $conn->prepare($calc_sql);
            $calc_stmt->bind_param("i", $order['id']);
            $calc_stmt->execute();
            $calc_result = $calc_stmt->get_result();
            if ($calc_result && $calc_result->num_rows > 0) {
                $calc_row = $calc_result->fetch_assoc();
                $total_amount = $calc_row['calc_total'] ?? 0;
            } else {
                $total_amount = 0;
            }
            $calc_stmt->close();
        }
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>₹" . number_format($total_amount, 2) . "</td>";
        
        // Status display and update form
        $current_status = $order['status'] ?? 'pending';
        if (empty($current_status) || strtolower($current_status) === 'null') {
            $current_status = 'pending';
        }
        $status_colors = [
            'pending' => '#ecc94b',
            'processing' => '#4299e1',
            'shipped' => '#ed8936',
            'delivered' => '#48bb78',
            'cancelled' => '#e53e3e'
        ];
        $status_color = $status_colors[strtolower($current_status)] ?? '#718096';
        
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
        echo "<form method='POST' action='update_order_status.php' id='statusForm" . $order['id'] . "' style='display: inline-block; margin-bottom: 5px;'>";
        echo "<input type='hidden' name='order_id' value='" . $order['id'] . "'>";
        echo "<select name='status' id='statusSelect" . $order['id'] . "' style='padding: 5px 10px; border-radius: 4px; border: 1px solid #ddd; background-color: " . $status_color . "; color: white; font-weight: bold;'>";
        $status_options = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        foreach ($status_options as $status_option) {
            $selected = (strtolower($current_status) === strtolower($status_option)) ? 'selected' : '';
            echo "<option value='$status_option' $selected>" . ucfirst($status_option) . "</option>";
        }
        echo "</select>";
        echo "<noscript><button type='submit' style='margin-left: 5px; padding: 5px 10px;'>Update</button></noscript>";
        echo "</form>";
        echo "<script>
        document.getElementById('statusSelect" . $order['id'] . "').addEventListener('change', function() {
            if (confirm('Update order #" . $order['id'] . " status to ' + this.value + '?')) {
                document.getElementById('statusForm" . $order['id'] . "').submit();
            } else {
                this.value = '" . $current_status . "';
            }
        });
        </script>";
        echo "<br><small style='color: #718096;'>Current: " . ucfirst($current_status) . "</small>";
        echo "</td>";
        
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . date('M j, Y g:i A', strtotime($order['created_at'])) . "</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
        
        // View Order link that redirects to shopkeeper details for the specific order
        echo "<a href='shopkeeper_details.php?id=" . $order['user_id'] . "&order_id=" . $order['id'] . "' style='padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; display: inline-block;'>View Order</a>";
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No orders found for this vendor.</p>";
}

// Create update_order_status.php script if it doesn't exist
if (!file_exists('update_order_status.php')) {
    $update_script = '<?php
    session_start();
    include "db.php";
    
    // Check if user is logged in as vendor
    if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "vendor") {
        header("Location: login.php");
        exit();
    }
    
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["order_id"]) && isset($_POST["status"])) {
        $order_id = intval($_POST["order_id"]);
        $status = $_POST["status"];
        $vendor_id = $_SESSION["user_id"];
        
        // Verify this order belongs to the vendor
        $check_sql = "SELECT id FROM orders WHERE id = ? AND vendor_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $order_id, $vendor_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update order status
            $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $status, $order_id);
            
            if ($update_stmt->execute()) {
                $_SESSION["success_message"] = "Order status updated successfully!";
            } else {
                $_SESSION["error_message"] = "Error updating order status: " . $conn->error;
            }
        } else {
            $_SESSION["error_message"] = "You do not have permission to update this order.";
        }
    } else {
        $_SESSION["error_message"] = "Invalid request.";
    }
    
    header("Location: vendor_orders.php");
    exit();
    ?>';
    
    file_put_contents('update_order_status.php', $update_script);
    echo "<p>Created update_order_status.php script.</p>";
}

echo "<p><a href='vendor_dashboard.php' style='display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Return to Dashboard</a></p>";

echo "<script>
function toggleAddress(button) {
    const addressDiv = button.nextElementSibling;
    if (addressDiv.style.display === 'none') {
        addressDiv.style.display = 'block';
        button.textContent = 'Hide Address';
    } else {
        addressDiv.style.display = 'none';
        button.textContent = 'Show Address';
    }
}
</script>";

$conn->close();
?>