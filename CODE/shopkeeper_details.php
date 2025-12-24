
<?php
session_start();

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login page.html');
    exit();
}

require 'db.php';

// Get shopkeeper ID and optional order ID from URL parameter
$shopkeeper_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$shopkeeper_id) {
    header('Location: vendor_dashboard.php');
    exit();
}

// Get customer details (shopkeeper or vendor)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Error preparing shopkeeper query: " . $conn->error);
}
$stmt->bind_param("i", $shopkeeper_id);
$stmt->execute();
$shopkeeper_result = $stmt->get_result();

if ($shopkeeper_result->num_rows === 0) {
    header('Location: vendor_dashboard.php');
    exit();
}

$shopkeeper = $shopkeeper_result->fetch_assoc();
$stmt->close();

// Get filter parameter for order status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';

// Get orders from this customer; if order_id is provided, filter to that single order
if ($order_id > 0) {
    $stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                           FROM orders o 
                           LEFT JOIN order_items oi ON o.id = oi.order_id 
                           WHERE o.user_id = ? AND o.id = ? 
                           GROUP BY o.id 
                           ORDER BY o.created_at DESC 
                           LIMIT 1");
    if (!$stmt) {
        die("Error preparing orders query: " . $conn->error);
    }
    $stmt->bind_param("ii", $shopkeeper_id, $order_id);
} else {
    // Build query based on status filter
    $sql = "SELECT o.*, COUNT(oi.id) as item_count 
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.user_id = ?";
    
    if ($status_filter === 'active') {
        // Show only orders that need status updates (not delivered or cancelled)
        $sql .= " AND o.status IN ('pending', 'processing', 'shipped')";
    } elseif ($status_filter !== 'all') {
        // Show specific status
        $sql .= " AND o.status = ?";
    }
    
    $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing orders query: " . $conn->error);
    }
    
    if ($status_filter === 'active') {
        $stmt->bind_param("i", $shopkeeper_id);
    } elseif ($status_filter !== 'all') {
        $stmt->bind_param("is", $shopkeeper_id, $status_filter);
    } else {
        $stmt->bind_param("i", $shopkeeper_id);
    }
}
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();

// Get total orders and revenue from this shopkeeper
$stmt = $conn->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_revenue 
                       FROM orders 
                       WHERE user_id = ? AND status = 'delivered'");
if (!$stmt) {
    die("Error preparing stats query: " . $conn->error);
}
$stmt->bind_param("i", $shopkeeper_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopkeeper Details - AI Raw Material Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-green-600">Marketplace AI</h1>
                    </div>
                    <nav class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="vendor_dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Dashboard</a>
                        <a href="add_product.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Products</a>
                        <a href="vendor_orders.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Orders</a>
                        <a href="ai_assistant.html" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">AI Assistant</a>
                    </nav>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <div class="ml-3 relative flex items-center space-x-3">
                        <a href="logout.php" class="hidden sm:inline-block text-sm text-gray-600 hover:text-red-600">Logout</a>
                        <div>
                            <button type="button" class="bg-white rounded-full flex text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" id="user-menu-button">
                                <span class="sr-only">Open user menu</span>
                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-medium">
                                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="vendor_dashboard.php" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                    <i data-feather="arrow-left" class="w-4 h-4 mr-2"></i>
                    Back to Dashboard
                </a>
            </div>

            <!-- Shopkeeper Details Card -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Shopkeeper Details</h1>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                            Active Customer
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 mb-1">Full Name</div>
                        <div class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($shopkeeper['first_name'] . ' ' . $shopkeeper['last_name']); ?>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 mb-1">Business Name</div>
                        <div class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($shopkeeper['shop_name'] ?: 'Not specified'); ?>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 mb-1">Business Type</div>
                        <div class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars(ucfirst($shopkeeper['shop_type'] ?: 'Not specified')); ?>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 mb-1">Email</div>
                        <div class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($shopkeeper['email']); ?>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 mb-1">Phone</div>
                        <div class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($shopkeeper['phone']); ?>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 mb-1">Member Since</div>
                        <div class="text-lg font-semibold text-gray-900">
                            <?php echo date('M j, Y', strtotime($shopkeeper['created_at'])); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Address Information -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Address Information</h3>
                    <div class="text-gray-700">
                        <div class="mb-2">
                            <strong>Address:</strong> <?php echo htmlspecialchars($shopkeeper['address']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>City:</strong> <?php echo htmlspecialchars($shopkeeper['city']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>State:</strong> <?php echo htmlspecialchars($shopkeeper['state']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Postal Code:</strong> <?php echo htmlspecialchars($shopkeeper['postal_code']); ?>
                        </div>
                        <div>
                            <strong>Country:</strong> <?php echo htmlspecialchars($shopkeeper['country']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Orders</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['total_orders']; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i data-feather="shopping-cart" class="w-6 h-6 text-blue-600"></i>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        <span class="text-green-600 font-medium">Delivered</span> orders from this shopkeeper
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">₹<?php echo number_format($stats['total_revenue'], 2); ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i data-feather="dollar-sign" class="w-6 h-6 text-green-600"></i>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        <span class="text-green-600 font-medium">Generated</span> from this shopkeeper
                    </p>
                </div>
            </div>

            <!-- Order Management -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Order Management</h2>
                </div>
                <?php if ($order_id === 0): ?>
                    <!-- Status Filter Buttons (hidden when viewing a single order) -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Filter by Status:</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $status_filters = [
                                'active' => ['label' => 'Active Orders', 'description' => 'Orders needing updates'],
                                'all' => ['label' => 'All Orders', 'description' => 'Show all orders'],
                                'pending' => ['label' => 'Pending', 'description' => 'New orders'],
                                'processing' => ['label' => 'Processing', 'description' => 'Being prepared'],
                                'shipped' => ['label' => 'Shipped', 'description' => 'In transit'],
                                'delivered' => ['label' => 'Delivered', 'description' => 'Completed'],
                                'cancelled' => ['label' => 'Cancelled', 'description' => 'Cancelled orders']
                            ];
                            
                            foreach ($status_filters as $value => $info):
                                $is_active = ($status_filter === $value);
                                $bg_color = $is_active ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200';
                            ?>
                                <a href="?id=<?php echo $shopkeeper_id; ?>&status=<?php echo $value; ?>" 
                                   class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo $bg_color; ?>"
                                   title="<?php echo $info['description']; ?>">
                                    <?php echo $info['label']; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($order_id === 0): ?>
                    <!-- Order Summary (hidden when viewing a single order) -->
                    <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-blue-800">
                                    <?php 
                                    $order_count = $orders_result->num_rows;
                                    if ($status_filter === 'all') {
                                        echo "Showing {$order_count} total orders";
                                    } elseif ($status_filter === 'active') {
                                        echo "Showing {$order_count} active orders";
                                    } else {
                                        echo "Showing " . ($order_count > 0 ? "1 " : "0 ") . strtolower($status_filters[$status_filter]['label']) . " order";
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php if ($status_filter === 'active'): ?>
                                <span class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded-full">
                                    Orders requiring action
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($orders_result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Update Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $order['item_count']; ?> items</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status_colors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'processing' => 'bg-blue-100 text-blue-800',
                                                'shipped' => 'bg-purple-100 text-purple-800',
                                                'delivered' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800'
                                            ];
                                            $status_color = $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php $current_status = strtolower($order['status']); ?>
                                            <?php if ($current_status === 'cancelled'): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-700">Order Cancelled</span>
                                            <?php elseif ($current_status === 'delivered'): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-green-100 text-green-700">Order Delivered</span>
                                            <?php else: ?>
                                                <?php if ($order_id > 0): ?>
                                                    <!-- When viewing a single order: show update controls -->
                                                    <div class="flex flex-col space-y-2">
                                                        <form method="post" action="update_order_status.php" class="inline">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="redirect_to" value="shopkeeper_details.php?id=<?php echo $shopkeeper_id; ?>&order_id=<?php echo $order_id; ?>">
                                                            <select name="status" class="text-sm border border-gray-300 rounded-md px-2 py-1 mr-2" onchange="this.form.submit()">
                                                                <?php
                                                                $current_status = strtolower($order['status']);
                                                                $status_options = [
                                                                    'pending' => 'Pending',
                                                                    'processing' => 'Processing', 
                                                                    'shipped' => 'Shipped',
                                                                    'delivered' => 'Delivered'
                                                                ];
                                                                $allowed_next = [
                                                                    'pending' => ['processing'],
                                                                    'processing' => ['shipped'],
                                                                    'shipped' => ['delivered'],
                                                                    'delivered' => []
                                                                ];
                                                                foreach ($status_options as $value => $label) {
                                                                    $selected = ($value === $current_status) ? 'selected' : '';
                                                                    $disabled = '';
                                                                    if ($value !== $current_status && !in_array($value, $allowed_next[$current_status])) {
                                                                        $disabled = 'disabled';
                                                                    }
                                                                    echo "<option value='$value' $selected $disabled>$label</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </form>
                                                        <form method="post" action="update_order_status.php" class="inline">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <input type="hidden" name="redirect_to" value="shopkeeper_details.php?id=<?php echo $shopkeeper_id; ?>&order_id=<?php echo $order_id; ?>">
                                                            <button type="submit" 
                                                                    class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md transition-colors"
                                                                    onclick="return confirm('Are you sure you want to cancel this order? This action cannot be undone?')">
                                                                Cancel Order
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- In list view: only show the current state text -->
                                                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-700">Manage via View Order</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="text-gray-500">
                            <i data-feather="shopping-cart" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                            <?php if ($status_filter === 'active'): ?>
                                <p class="text-lg font-medium text-gray-900 mb-2">No active orders</p>
                                <p class="text-gray-500">All orders are completed or cancelled. No status updates needed.</p>
                            <?php elseif ($status_filter === 'all'): ?>
                                <p class="text-lg font-medium text-gray-900 mb-2">No orders yet</p>
                                <p class="text-gray-500">This shopkeeper hasn't placed any orders yet.</p>
                            <?php else: ?>
                                <p class="text-lg font-medium text-gray-900 mb-2">No <?php echo strtolower($status_filters[$status_filter]['label']); ?> order found</p>
                                <p class="text-gray-500">No order with status "<?php echo ucfirst($status_filter); ?>" found for this customer.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        feather.replace();
    </script>
</body>
</html>
