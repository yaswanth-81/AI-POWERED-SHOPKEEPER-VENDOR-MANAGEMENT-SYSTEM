
<?php
session_start();

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login page.html');
    exit();
}

require 'db.php';

// Get vendor's products and orders
$vendor_id = $_SESSION['user_id'];

// Get total products count
$stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products WHERE vendor_id = ?");
if (!$stmt) {
    die("Error preparing products query: " . $conn->error);
}
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$products_result = $stmt->get_result();
$total_products = $products_result->fetch_assoc()['total_products'];
$stmt->close();

// Get active orders count for this vendor - orders that contain products from this vendor
$stmt = $conn->prepare("SELECT COUNT(DISTINCT o.id) as active_orders 
                        FROM orders o 
                        JOIN order_items oi ON o.id = oi.order_id 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE p.vendor_id = ? AND o.status IN ('pending', 'processing', 'shipped')");
if (!$stmt) {
    die("Error preparing active orders query: " . $conn->error);
}
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$active_orders = $orders_result->fetch_assoc()['active_orders'];
$stmt->close();

// Get total revenue for this vendor - orders that contain products from this vendor
$stmt = $conn->prepare("SELECT COALESCE(SUM(o.total_amount), 0) as total_revenue 
                        FROM orders o 
                        JOIN order_items oi ON o.id = oi.order_id 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE p.vendor_id = ? AND o.status = 'delivered'");
if (!$stmt) {
    die("Error preparing revenue query: " . $conn->error);
}
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$revenue_result = $stmt->get_result();
$total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;
$stmt->close();

// Get recent orders for this vendor's products with customer details
$stmt = $conn->prepare("SELECT DISTINCT o.id, o.total_amount, o.status, o.created_at, o.user_id,
                        u.first_name, u.last_name, u.email, u.shop_name, u.shop_type, u.phone, u.city, u.state, u.role
                        FROM orders o
                        JOIN users u ON o.user_id = u.id
                        JOIN order_items oi ON o.id = oi.order_id
                        JOIN products p ON oi.product_id = p.id
                        WHERE p.vendor_id = ?
                        ORDER BY o.created_at DESC 
                        LIMIT 10");
if (!$stmt) {
    die("Error preparing recent orders query: " . $conn->error);
}
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$recent_orders = $stmt->get_result();
$stmt->close();

// Get vendor's products - use stock_quantity and image_path columns (show all products)
$stmt = $conn->prepare("SELECT id, name, price, COALESCE(stock_quantity, 0) as stock, image_path FROM products WHERE vendor_id = ? ORDER BY created_at DESC");
if (!$stmt) {
    die("Error preparing products query: " . $conn->error);
}
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - AI Raw Material Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
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
                        <a href="vendor_dashboard.php" class="border-green-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Dashboard</a>
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
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Products</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $total_products; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i data-feather="package" class="w-6 h-6 text-green-600"></i>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        <span class="text-green-600 font-medium">Active</span> products in your store
                    </p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Active Orders</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $active_orders; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i data-feather="shopping-cart" class="w-6 h-6 text-blue-600"></i>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        <span class="text-green-600 font-medium">Pending</span> and processing orders
                    </p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">₹<?php echo number_format($total_revenue, 2); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i data-feather="dollar-sign" class="w-6 h-6 text-purple-600"></i>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        <span class="text-green-600 font-medium">Delivered</span> orders revenue
                    </p>
                </div>
            </div>
            
            <!-- Recent Shopkeeper Orders -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Recent Shopkeeper Orders</h2>
                    <a href="vendor_orders.php" class="text-sm font-medium text-green-600 hover:text-green-500">View all</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shopkeeper</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($recent_orders->num_rows > 0): ?>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                                <div class="text-gray-500"><?php echo htmlspecialchars($order['email']); ?></div>
                                                <div class="text-gray-500"><?php echo htmlspecialchars($order['phone']); ?></div>
                                                <div class="text-xs text-blue-600 font-medium"><?php echo ucfirst($order['role']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($order['shop_name'] ?: 'Not specified'); ?></div>
                                                <div class="text-gray-500"><?php echo htmlspecialchars(ucfirst($order['shop_type'] ?: 'Not specified')); ?></div>
                                                <div class="text-gray-500"><?php echo htmlspecialchars($order['city'] . ', ' . $order['state']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $order_status = $order['status'] ?? 'pending';
                                            if (empty($order_status) || strtolower($order_status) === 'null') {
                                                $order_status = 'pending';
                                            }
                                            $status_colors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'processing' => 'bg-blue-100 text-blue-800',
                                                'confirmed' => 'bg-blue-100 text-blue-800',
                                                'shipped' => 'bg-purple-100 text-purple-800',
                                                'delivered' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800'
                                            ];
                                            $status_color = $status_colors[strtolower($order_status)] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                                <?php echo ucfirst($order_status); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="shopkeeper_details.php?id=<?php echo $order['user_id']; ?>&order_id=<?php echo $order['id']; ?>" class="text-green-600 hover:text-green-900">View Details</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No recent orders from shopkeepers found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Products Management -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Your Products</h2>
                    <a href="add_product.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i data-feather="plus" class="w-4 h-4 mr-2"></i>
                        Add Product
                    </a>
                </div>
                
                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if ($products->num_rows > 0): ?>
                        <?php while ($product = $products->fetch_assoc()): 
                            // Handle image_path - if it's already a full path, use it; otherwise prepend uploads/products/
                            $image_src = 'images/default.jpg';
                            if (!empty($product['image_path'])) {
                                if (strpos($product['image_path'], 'uploads/') === 0 || strpos($product['image_path'], '/') === 0) {
                                    $image_src = $product['image_path'];
                                } else {
                                    $image_src = 'uploads/products/' . $product['image_path'];
                                }
                            }
                        ?>
                            <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                                <div class="relative">
                                    <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="w-full h-48 object-cover">
                                    <div class="absolute top-2 right-2 flex space-x-1">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="bg-white p-1 rounded-full shadow hover:bg-gray-100">
                                            <i data-feather="edit" class="w-4 h-4 text-blue-500"></i>
                                        </a>
                                        <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="bg-white p-1 rounded-full shadow hover:bg-gray-100">
                                            <i data-feather="trash-2" class="w-4 h-4 text-red-500"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="mt-2">
                                        <div class="flex justify-between items-center mb-2">
                                            <div>
                                                <span class="text-xl font-bold text-gray-900">₹<?php echo number_format($product['price'], 2); ?></span>
                                                <span class="text-sm text-gray-500">/ unit</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <?php 
                                            $stock_value = (int)$product['stock'];
                                            $stock_class = $stock_value > 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                                            ?>
                                            <span class="text-sm <?php echo $stock_class; ?>">
                                                <strong>Stock:</strong> <?php echo $stock_value; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-500">
                                <i data-feather="package" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                                <p class="text-lg font-medium text-gray-900 mb-2">No products yet</p>
                                <p class="text-gray-500 mb-4">Start by adding your first product to the marketplace.</p>
                                <a href="add_product.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                                    <i data-feather="plus" class="w-4 h-4 mr-2"></i>
                                    Add Your First Product
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        feather.replace();
        
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                fetch('delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({id: productId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting product: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting product');
                });
            }
        }
    </script>
</body>
</html>