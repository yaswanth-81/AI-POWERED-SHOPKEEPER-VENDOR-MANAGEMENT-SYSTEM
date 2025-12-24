<?php
session_start();
include 'db.php';
// Ensure fresh data on every view (prevent browser/proxy caching)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Check if user is logged in as shopkeeper
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shopkeeper') {
    header("Location: login.php");
    exit();
}

$shopkeeper_id = $_SESSION['user_id'];

// Get filter parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build SQL query with filter - check if vendor_id column exists
$check_vendor_col = $conn->query("SHOW COLUMNS FROM orders LIKE 'vendor_id'");
$has_vendor_id = $check_vendor_col && $check_vendor_col->num_rows > 0;

$sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone, u.shop_name,
               COUNT(oi.id) as item_count";
if ($has_vendor_id) {
    $sql .= ", o.vendor_id";
}
$sql .= " FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ?";

if ($status_filter === 'active') {
    // Show only orders that need status updates (not delivered or cancelled)
    $sql .= " AND o.status IN ('pending', 'processing', 'shipped')";
} elseif ($status_filter !== 'all') {
    $sql .= " AND o.status = ?";
}

$sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT 50";

$stmt = $conn->prepare($sql);
if ($status_filter === 'active') {
    $stmt->bind_param("i", $shopkeeper_id);
} elseif ($status_filter !== 'all') {
    $stmt->bind_param("is", $shopkeeper_id, $status_filter);
} else {
    $stmt->bind_param("i", $shopkeeper_id);
}
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    COALESCE(SUM(total_amount), 0) as total_spent
    FROM orders WHERE user_id = ?";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $shopkeeper_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Get recent orders for comparison (last week)
$recent_sql = "SELECT COUNT(*) as recent_orders FROM orders 
               WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $shopkeeper_id);
$recent_stmt->execute();
$recent_count = $recent_stmt->get_result()->fetch_assoc()['recent_orders'];
$recent_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - AI Raw Material Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e4e8;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #4a6cf7;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #4a6cf7;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .dashboard-header {
            margin-bottom: 25px;
        }
        
        .dashboard-header h1 {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-card-title {
            font-size: 16px;
            color: #718096;
        }
        
        .stat-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .icon-delivered {
            background-color: #48bb78;
        }
        
        .icon-pending {
            background-color: #ecc94b;
        }
        
        .icon-processing {
            background-color: #4299e1;
        }
        
        .icon-total {
            background-color: #9f7aea;
        }
        
        .icon-shipped {
            background-color: #ed8936;
        }
        
        .stat-card-value {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
        }
        
        .stat-card-change {
            font-size: 14px;
            margin-top: 5px;
        }
        
        .positive {
            color: #48bb78;
        }
        
        .negative {
            color: #e53e3e;
        }
        
        .filters {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .filters h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #2d3748;
        }
        
        .filter-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 10px 20px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: white;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #4a5568;
            display: inline-block;
        }
        
        .filter-btn:hover {
            background: #f7fafc;
            text-decoration: none;
            color: #4a5568;
        }
        
        .filter-btn.active {
            background: #4a6cf7;
            color: white;
            border-color: #4a6cf7;
        }
        
        .orders-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .orders-summary h3 {
            font-size: 18px;
            color: #2d3748;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 20px;
            padding: 8px 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .search-box input {
            border: none;
            outline: none;
            padding: 5px 10px;
            font-size: 14px;
            width: 200px;
        }
        
        .orders-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background-color: #f7fafc;
            font-weight: 600;
            color: #718096;
            font-size: 14px;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .customer-info {
            display: flex;
            flex-direction: column;
        }
        
        .customer-name {
            font-weight: 600;
            color: #2d3748;
        }
        
        .customer-detail {
            font-size: 13px;
            color: #718096;
        }
        
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-delivered {
            background-color: #c6f6d5;
            color: #2f855a;
        }
        
        .status-pending {
            background-color: #fefcbf;
            color: #744210;
        }
        
        .status-processing {
            background-color: #bee3f8;
            color: #2c5282;
        }
        
        .status-shipped {
            background-color: #fed7d7;
            color: #c53030;
        }
        
        .status-cancelled {
            background-color: #fed7d7;
            color: #c53030;
        }
        
        .view-order-btn {
            padding: 8px 15px;
            background-color: #edf2f7;
            border: none;
            border-radius: 6px;
            color: #4a5568;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .view-order-btn:hover {
            background-color: #e2e8f0;
            text-decoration: none;
            color: #4a5568;
        }
        
        .no-orders {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }
        
        .no-orders i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #cbd5e0;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #4a6cf7;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: background 0.3s ease;
        }
        
        .back-btn:hover {
            background: #3b5bdb;
            text-decoration: none;
            color: white;
        }
        
        @media (max-width: 992px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .filter-options {
                flex-direction: column;
            }
            
            .filter-btn {
                width: 100%;
            }
            
            .orders-summary {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">Marketplace AI</div>
            <div class="user-info">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
                    <div class="user-role">Shopkeeper</div>
                </div>
            </div>
        </header>
        
        <a href="shopkeeper_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
        
        <div class="dashboard-header">
            <h1>My Orders</h1>
            <p>Track and manage all your orders in one place</p>
        </div>
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">Total Orders</div>
                    <div class="stat-card-icon icon-total">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-card-change <?php echo $recent_count > 0 ? 'positive' : ''; ?>">
                    <?php echo $recent_count > 0 ? "+{$recent_count} this week" : "No recent orders"; ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">Pending Orders</div>
                    <div class="stat-card-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-card-change">Awaiting processing</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">Processing</div>
                    <div class="stat-card-icon icon-processing">
                        <i class="fas fa-cog"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $stats['processing_orders']; ?></div>
                <div class="stat-card-change">Being prepared</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">Shipped</div>
                    <div class="stat-card-icon icon-shipped">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $stats['shipped_orders']; ?></div>
                <div class="stat-card-change">On the way</div>
                        </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">Delivered</div>
                    <div class="stat-card-icon icon-delivered">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $stats['delivered_orders']; ?></div>
                <div class="stat-card-change positive">Successfully completed</div>
            </div>
        </div>
        
        <div class="filters">
            <h2>Filter by Status:</h2>
            <div class="filter-options">
                <?php
                $status_filters = [
                    'all' => 'All Orders',
                    'active' => 'Active Orders',
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled'
                ];
                
                foreach ($status_filters as $value => $label):
                    $is_active = ($status_filter === $value);
                    $active_class = $is_active ? 'active' : '';
                ?>
                    <a href="?status=<?php echo $value; ?>" class="filter-btn <?php echo $active_class; ?>">
                        <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="orders-summary">
            <h3>Showing <?php echo $orders_result->num_rows; ?> 
                <?php 
                if ($status_filter === 'active') {
                    echo 'active orders (requiring updates)';
                } elseif ($status_filter === 'all') {
                    echo 'total orders';
                } else {
                    echo strtolower($status_filters[$status_filter]);
                }
                ?>
            </h3>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search orders..." id="searchInput">
            </div>
            </div>
            
        <div class="orders-table">
            <?php if ($orders_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Vendor</th>
                            <th>Products</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                            <?php 
                            // Get vendor information - check if vendor_id exists in order
                            $vendor = ['first_name' => '', 'last_name' => '', 'shop_name' => 'Vendor'];
                            if (isset($order['vendor_id']) && !empty($order['vendor_id'])) {
                                $vendor_sql = "SELECT first_name, last_name, shop_name, business_name FROM users WHERE id = ?";
                            $vendor_stmt = $conn->prepare($vendor_sql);
                            $vendor_stmt->bind_param("i", $order['vendor_id']);
                            $vendor_stmt->execute();
                                $vendor_result = $vendor_stmt->get_result();
                                if ($vendor_result && $vendor_result->num_rows > 0) {
                                    $vendor = $vendor_result->fetch_assoc();
                                }
                                $vendor_stmt->close();
                            } else {
                                // Try to get vendor from order_items -> products -> vendor_id
                                $items_sql_temp = "SELECT DISTINCT p.vendor_id 
                                                  FROM order_items oi 
                                                  JOIN products p ON oi.product_id = p.id 
                                                  WHERE oi.order_id = ? LIMIT 1";
                                $items_stmt_temp = $conn->prepare($items_sql_temp);
                                $items_stmt_temp->bind_param("i", $order['id']);
                                $items_stmt_temp->execute();
                                $vendor_id_result = $items_stmt_temp->get_result();
                                if ($vendor_id_result && $vendor_id_result->num_rows > 0) {
                                    $vendor_row = $vendor_id_result->fetch_assoc();
                                    if (!empty($vendor_row['vendor_id'])) {
                                        $vendor_sql = "SELECT first_name, last_name, shop_name, business_name FROM users WHERE id = ?";
                                        $vendor_stmt = $conn->prepare($vendor_sql);
                                        $vendor_stmt->bind_param("i", $vendor_row['vendor_id']);
                                        $vendor_stmt->execute();
                                        $vendor_result = $vendor_stmt->get_result();
                                        if ($vendor_result && $vendor_result->num_rows > 0) {
                                            $vendor = $vendor_result->fetch_assoc();
                                        }
                            $vendor_stmt->close();
                                    }
                                }
                                $items_stmt_temp->close();
                            }
                            
                            // Get order items
                            $items_sql = "SELECT oi.*, p.name as product_name 
                                          FROM order_items oi 
                                        JOIN products p ON oi.product_id = p.id 
                                        WHERE oi.order_id = ?";
                            $items_stmt = $conn->prepare($items_sql);
                            $items_stmt->bind_param("i", $order['id']);
                            $items_stmt->execute();
                            $items_result = $items_stmt->get_result();
                            $items_stmt->close();
                            
                            $status_class = 'status-' . strtolower($order['status']);
                            ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">
                                            <?php 
                                            $vendor_name = trim(($vendor['first_name'] ?? '') . ' ' . ($vendor['last_name'] ?? ''));
                                            if (empty($vendor_name)) {
                                                $vendor_name = $vendor['business_name'] ?? 'Vendor';
                                            }
                                            echo htmlspecialchars($vendor_name);
                                            ?>
                                        </span>
                                        <span class="customer-detail">
                                            <?php echo htmlspecialchars($vendor['shop_name'] ?? $vendor['business_name'] ?? 'Vendor'); ?>
                                            </span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $product_names = [];
                                    while ($item = $items_result->fetch_assoc()) {
                                        $product_names[] = htmlspecialchars($item['product_name']) . ' x ' . $item['quantity'];
                                    }
                                    echo implode(', ', $product_names);
                                    ?>
                                </td>
                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <?php 
                                    $order_status = $order['status'] ?? 'pending';
                                    if (empty($order_status) || strtolower($order_status) === 'null') {
                                        $order_status = 'pending';
                                    }
                                    $status_class = 'status-' . strtolower($order_status);
                                    ?>
                                    <span class="status <?php echo $status_class; ?>"><?php echo ucfirst($order_status); ?></span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="view-order-btn">
                                        View Order
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No orders found</h3>
                    <p>
                        <?php if ($status_filter === 'active'): ?>
                            No active orders requiring updates.
                        <?php elseif ($status_filter === 'all'): ?>
                            You haven't placed any orders yet.
                        <?php else: ?>
                            No orders with status "<?php echo ucfirst($status_filter); ?>" found.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Simple JavaScript for search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>