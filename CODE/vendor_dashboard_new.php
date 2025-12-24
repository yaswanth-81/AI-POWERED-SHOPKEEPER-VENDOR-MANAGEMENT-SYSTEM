<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'db.php';

// Check if user is logged in as vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    // Redirect to login page if not logged in as vendor
    header("Location: login.php");
    exit();
}

// Fetch vendor's products from database - ensure stock is included
$vendor_id = $_SESSION['user_id'];
$sql = "SELECT *, COALESCE(stock_quantity, stock, 0) as stock FROM products WHERE vendor_id = $vendor_id";
$result = $conn->query($sql);

// Count total products
$total_products = $result->num_rows;

// Fetch all orders for this vendor (since there's no status column)
$orders_sql = "SELECT * FROM orders WHERE user_id = $vendor_id";
$orders_result = $conn->query($orders_sql);
$active_orders = $orders_result ? $orders_result->num_rows : 0;

// Calculate total revenue (since there's no status column)
$revenue_sql = "SELECT SUM(total) as total_revenue FROM orders WHERE user_id = $vendor_id";
$revenue_result = $conn->query($revenue_sql);
$revenue_row = $revenue_result ? $revenue_result->fetch_assoc() : null;
$total_revenue = $revenue_row ? $revenue_row['total_revenue'] : 0;

// Fetch recent orders
$recent_orders_sql = "SELECT o.* FROM orders o 
                      WHERE o.user_id = $vendor_id 
                      ORDER BY o.created_at DESC LIMIT 3";
$recent_orders_result = $conn->query($recent_orders_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - AI Raw Material Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <a href="vendor_dashboard_new.php" class="border-green-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Dashboard</a>
                        <a href="#products" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Products</a>
                        <a href="vendor_orders.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Orders</a>
                        <a href="analytics.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Analytics</a>
                        <a href="ai_assistant.html" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">AI Assistant</a>
                    </nav>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <div class="ml-3 relative">
                        <div>
                            <button type="button" class="bg-white rounded-full flex text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" id="user-menu-button">
                                <span class="sr-only">Open user menu</span>
                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-medium">
                                    <?php 
                                    // Display vendor initials if available
                                    if (isset($_SESSION['vendor_name'])) {
                                        $initials = strtoupper(substr($_SESSION['vendor_name'], 0, 2));
                                        echo $initials;
                                    } else {
                                        echo "VD";
                                    }
                                    ?>
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        <span class="text-green-600 font-medium">+2.5%</span> from last month
                    </p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Active Orders</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $active_orders; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        <span class="text-green-600 font-medium">+12%</span> from last week
                    </p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Revenue</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">₹<?php echo number_format($total_revenue, 2); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        <span class="text-green-600 font-medium">+8.3%</span> from last month
                    </p>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6" id="orders">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Recent Orders</h2>
                    <a href="vendor_orders.php" class="text-sm font-medium text-green-600 hover:text-green-500">View all</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            if ($recent_orders_result && $recent_orders_result->num_rows > 0) {
                                while ($order = $recent_orders_result->fetch_assoc()) {
                                    // Get status class based on order status
                                    $status_class = '';
                                    switch (strtolower($order['status'])) {
                                        case 'completed':
                                            $status_class = 'bg-green-100 text-green-800';
                                            break;
                                        case 'processing':
                                            $status_class = 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'shipped':
                                            $status_class = 'bg-blue-100 text-blue-800';
                                            break;
                                        default:
                                            $status_class = 'bg-gray-100 text-gray-800';
                                    }
                                    
                                    // Get items count (this would need to be adjusted based on your database structure)
                                    $items_sql = "SELECT COUNT(*) as item_count FROM order_items WHERE order_id = {$order['id']}";
                                    $items_result = $conn->query($items_sql);
                                    $items_count = 0;
                                    if ($items_result && $items_row = $items_result->fetch_assoc()) {
                                        $items_count = $items_row['item_count'];
                                    }
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#ORD-<?php echo $order['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $items_count; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="text-green-600 hover:text-green-900">View</a>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                            ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No recent orders found.</td>
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Products Management -->
            <div class="bg-white p-6 rounded-lg shadow-sm" id="products">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Your Products</h2>
                    <button id="addProductBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Product
                    </button>
                </div>
                
                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    // Reset the result pointer to the beginning
                    if ($result) {
                        $result->data_seek(0);
                    }
                    
                    if ($result && $result->num_rows > 0) {
                        while ($product = $result->fetch_assoc()) {
                            // Determine if product is in stock (handle case where stock field might not exist)
                            $stock = isset($product['stock']) ? (int)$product['stock'] : (isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0);
                            $in_stock = $stock > 0;
                            $status_class = $in_stock ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                            $status_text = $in_stock ? 'Active' : 'Out of stock';
                            
                            // Get product image or use placeholder
                            $image_url = !empty($product['image_url']) ? $product['image_url'] : 'images/default.jpg';
                            
                            // Check if the image file exists
                            if (!empty($product['image_url']) && !file_exists($product['image_url'])) {
                                // If the image doesn't exist, use a default image
                                $image_url = 'images/default.jpg';
                            }
                    ?>
                    <!-- Product Card -->
                    <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                        <div class="relative">
                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>" class="w-full h-48 object-cover">
                            <div class="absolute top-2 right-2 flex space-x-1">
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="bg-white p-1 rounded-full shadow hover:bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <a href="delete_product.php?id=<?php echo $product['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')" class="bg-white p-1 rounded-full shadow hover:bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($product['name'] ?? 'Product'); ?></h3>
                            <div class="mt-2">
                                <div class="flex justify-between items-center mb-2">
                                    <div>
                                        <span class="text-xl font-bold text-gray-900">₹<?php echo number_format($product['price'], 2); ?></span>
                                        <span class="text-sm text-gray-500">/ <?php echo htmlspecialchars($product['unit'] ?? 'unit'); ?></span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <?php 
                                    $stock_value = isset($product['stock']) ? (int)$product['stock'] : (isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0);
                                    $stock_class = $stock_value > 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                                    ?>
                                    <span class="text-sm <?php echo $stock_class; ?>">
                                        <strong>Stock:</strong> <?php echo $stock_value; ?> <?php echo htmlspecialchars($product['unit'] ?? 'units'); ?>
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Last updated: <?php echo date('M j, Y', strtotime($product['updated_at'] ?? 'now')); ?></span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                    ?>
                    <div class="col-span-3 text-center py-8">
                        <p class="text-gray-500">No products found. Click "Add Product" to add your first product.</p>
                    </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
            
            <!-- Add Product Modal -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 hidden" id="addProductModal">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Add New Product</h3>
                        <button id="closeModal" class="text-gray-400 hover:text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form action="add_product.php" method="post" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="productName" class="block text-sm font-medium text-gray-700">Product Name</label>
                            <input type="text" id="productName" name="product_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label for="productCategory" class="block text-sm font-medium text-gray-700">Category</label>
                            <select id="productCategory" name="category" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="Vegetables">Vegetables</option>
                                <option value="Fruits">Fruits</option>
                                <option value="Dairy">Dairy</option>
                                <option value="Meat">Meat</option>
                                <option value="Grains">Grains</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="productPrice" class="block text-sm font-medium text-gray-700">Price</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" step="0.01" name="price" id="productPrice" required class="focus:ring-green-500 focus:border-green-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="0.00">
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <label for="unit" class="sr-only">Unit</label>
                                        <select id="unit" name="unit" class="focus:ring-green-500 focus:border-green-500 h-full py-0 pl-2 pr-7 border-transparent bg-transparent text-gray-500 sm:text-sm rounded-md">
                                            <option value="kg">/kg</option>
                                            <option value="lb">/lb</option>
                                            <option value="unit">/unit</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="productStock" class="block text-sm font-medium text-gray-700">Stock</label>
                                <input type="number" id="productStock" name="stock" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>
                        <div>
                            <label for="productDescription" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="productDescription" name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Product Image</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <div class="flex text-sm text-gray-600">
                                        <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500">
                                            <span>Upload a file</span>
                                            <input id="file-upload" name="product_image" type="file" class="sr-only">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" id="cancelAddProduct" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Cancel
                            </button>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Save Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Modal functionality
        document.getElementById('addProductBtn').addEventListener('click', function() {
            document.getElementById('addProductModal').classList.remove('hidden');
        });
        
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('addProductModal').classList.add('hidden');
        });
        
        document.getElementById('cancelAddProduct').addEventListener('click', function() {
            document.getElementById('addProductModal').classList.add('hidden');
        });
    </script>
</body>
</html>