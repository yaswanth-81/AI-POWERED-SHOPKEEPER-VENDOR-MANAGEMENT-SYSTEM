<?php
session_start();
include 'db.php';
// Ensure fresh data on every view (prevent browser/proxy caching)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login page.html");
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    header("Location: shopkeeper_orders.php");
    exit();
}

// Get user role
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'];

// Get order details - allow both shopkeepers and vendors to view
$order_sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone, u.shop_name, u.address, u.city, u.state, u.postal_code, u.country
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();
$order_stmt->close();

if (!$order) {
    header("Location: " . ($user_role === 'vendor' ? 'vendor_orders.php' : 'shopkeeper_orders.php'));
    exit();
}

// Verify ownership: shopkeeper can view their own orders, vendor can view orders assigned to them
$has_access = false;
if ($user_role === 'shopkeeper' && $order['user_id'] == $user_id) {
    $has_access = true;
} elseif ($user_role === 'vendor') {
    // Check if vendor_id column exists and matches
    if (!empty($order['vendor_id']) && $order['vendor_id'] == $user_id) {
        $has_access = true;
    } else {
        // Check via order_items and products
        $vendor_check_sql = "SELECT COUNT(*) as count 
                            FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = ? AND p.vendor_id = ?";
        $vendor_check_stmt = $conn->prepare($vendor_check_sql);
        if ($vendor_check_stmt) {
            $vendor_check_stmt->bind_param("ii", $order_id, $user_id);
            $vendor_check_stmt->execute();
            $vendor_check_result = $vendor_check_stmt->get_result();
            if ($vendor_check_result && $vendor_check_result->num_rows > 0) {
                $vendor_count = $vendor_check_result->fetch_assoc()['count'];
                $has_access = ($vendor_count > 0);
            }
            $vendor_check_stmt->close();
        }
    }
}

if (!$has_access) {
    header("Location: " . ($user_role === 'vendor' ? 'vendor_orders.php' : 'shopkeeper_orders.php'));
    exit();
}

// Get vendor information - check if vendor_id exists in order
$vendor = null;
if (!empty($order['vendor_id'])) {
    $vendor_sql = "SELECT first_name, last_name, shop_name, email, phone FROM users WHERE id = ?";
    $vendor_stmt = $conn->prepare($vendor_sql);
    $vendor_stmt->bind_param("i", $order['vendor_id']);
    $vendor_stmt->execute();
    $vendor = $vendor_stmt->get_result()->fetch_assoc();
    $vendor_stmt->close();
} else {
    // If no vendor_id in order, get from order_items -> products
    $vendor_from_items_sql = "SELECT DISTINCT u.first_name, u.last_name, u.shop_name, u.email, u.phone 
                             FROM users u 
                             INNER JOIN products p ON u.id = p.vendor_id 
                             INNER JOIN order_items oi ON p.id = oi.product_id 
                             WHERE oi.order_id = ? 
                             LIMIT 1";
    $vendor_from_items_stmt = $conn->prepare($vendor_from_items_sql);
    $vendor_from_items_stmt->bind_param("i", $order_id);
    $vendor_from_items_stmt->execute();
    $vendor = $vendor_from_items_stmt->get_result()->fetch_assoc();
    $vendor_from_items_stmt->close();
}

// Get order items
$items_sql = "SELECT oi.*, p.name as product_name, p.price, p.image_path
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items_stmt->close();

// Normalize order status
$order_status = strtolower($order['status'] ?? 'pending');
if (empty($order_status) || $order_status === 'null') {
    $order_status = 'pending';
}

$status_colors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'processing' => 'bg-blue-100 text-blue-800',
    'shipped' => 'bg-purple-100 text-purple-800',
    'delivered' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800'
];
$status_color = $status_colors[$order_status] ?? 'bg-gray-100 text-gray-800';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order['id']; ?> - AI Raw Material Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-green-600">Marketplace AI</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo ($user_role === 'vendor' ? 'vendor_orders.php' : 'shopkeeper_orders.php'); ?>" class="text-gray-600 hover:text-gray-900">Back to Orders</a>
                    <a href="logout.php" class="text-gray-600 hover:text-red-600">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Order Header -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">Order #<?php echo $order['id']; ?></h1>
                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $status_color; ?>">
                        <?php echo ucfirst($order_status); ?>
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                    </div>
                    <div>
                        <strong>Total Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                </div>

                <!-- Order Tracking -->
                <?php
                $steps = ['pending' => 1, 'processing' => 2, 'shipped' => 3, 'delivered' => 4, 'cancelled' => 0];
                $currentStep = $steps[$order_status] ?? 1;
                ?>
                <div class="mt-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-3">Order Tracking</h2>
                    <div class="flex items-center justify-between">
                        <?php
                        $labels = ['Pending', 'Processing', 'Shipped', 'Delivered'];
                        for ($i = 1; $i <= 4; $i++):
                            $active = $currentStep >= $i;
                        ?>
                            <div class="flex-1 flex items-center">
                                <div class="relative flex flex-col items-center">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $active ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'; ?>">
                                        <?php echo $i; ?>
                                    </div>
                                    <div class="mt-2 text-xs <?php echo $active ? 'text-gray-900' : 'text-gray-500'; ?>">
                                        <?php echo $labels[$i-1]; ?>
                                    </div>
                                </div>
                                <?php if ($i < 4): ?>
                                    <div class="flex-1 h-1 mx-2 <?php echo $currentStep > $i ? 'bg-blue-600' : 'bg-gray-200'; ?>"></div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <?php if ($order_status === 'cancelled'): ?>
                        <div class="mt-3 text-sm text-red-600 font-medium">
                            <i class="fas fa-ban mr-2"></i>This order was cancelled.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Cancel Order Button -->
                <?php 
                $can_cancel = !in_array($order_status, ['delivered', 'cancelled', 'shipped'], true);
                ?>
                <?php if ($can_cancel): ?>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <form id="cancelOrderForm" method="POST" action="cancel_order.php" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                                <i class="fas fa-times-circle mr-2"></i>Cancel Order
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Order Items -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h2>
                <div class="space-y-4">
                    <?php while ($item = $items_result->fetch_assoc()):
                        // Normalize image path similar to other views
                        $item_image = null;
                        if (!empty($item['image_path'])) {
                            // If it's already a full/relative path with a slash, use as-is
                            if (strpos($item['image_path'], 'uploads/') === 0 || strpos($item['image_path'], '/') === 0) {
                                $item_image = $item['image_path'];
                            } else {
                                // Treat as filename stored in uploads/products
                                $item_image = 'uploads/products/' . $item['image_path'];
                            }
                        }
                    ?>
                        <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                <?php if ($item_image): ?>
                                    <img src="<?php echo htmlspecialchars($item_image); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="w-full h-full object-cover rounded-lg">
                                <?php else: ?>
                                    <i class="fas fa-box text-gray-400"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <p class="text-sm text-gray-500">Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-gray-900">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                <p class="text-sm text-gray-500">₹<?php echo number_format($item['price'], 2); ?> each</p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Vendor Information -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Vendor Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Vendor Name</p>
                        <p class="font-medium text-gray-900">
                            <?php echo htmlspecialchars(($vendor['first_name'] ?? '') . ' ' . ($vendor['last_name'] ?? '')); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Business Name</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($vendor['shop_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($vendor['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Phone</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($vendor['phone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Delivery Address -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Delivery Address</h2>
                <div class="text-gray-700">
                    <p class="font-medium"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                    <p><?php echo htmlspecialchars($order['address']); ?></p>
                    <p><?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['postal_code']); ?></p>
                    <p><?php echo htmlspecialchars($order['country']); ?></p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

<?php
$conn->close();
?>