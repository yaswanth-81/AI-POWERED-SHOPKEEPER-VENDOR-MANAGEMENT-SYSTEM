<?php
session_start();

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login page.html');
    exit();
}

require 'db.php';

$vendor_id = $_SESSION['user_id'];
$edit_mode = false;
$product = [];
$id = null;

// If edit mode
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $product = $result->fetch_assoc();
            $edit_mode = true;
        }
        $stmt->close();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['product_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $description = $_POST['description'] ?? '';

    $image_url = '';

    if (!empty($_FILES['product_image']['tmp_name'])) {
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
            $image_url = $upload_path;
        }
    }

    // Store image path (extract filename from full path if needed)
    $image_path = '';
    if ($image_url) {
        // If image_url contains uploads/products/, extract just the filename
        // Otherwise use the full path
        if (strpos($image_url, 'uploads/products/') !== false) {
            $image_path = basename($image_url);
        } else {
            $image_path = $image_url;
        }
    }
    
    if ($edit_mode) {
        if ($image_path) {
            // Update with image: name, category, price, stock_quantity, description, image_path, id, vendor_id
            $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, stock_quantity=?, description=?, image_path=? WHERE id=? AND vendor_id=?");
            if ($stmt) {
                $stmt->bind_param("ssdissii", $name, $category, $price, $stock, $description, $image_path, $id, $vendor_id);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            // Update without image: name, category, price, stock_quantity, description, id, vendor_id
            $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, stock_quantity=?, description=? WHERE id=? AND vendor_id=?");
            if ($stmt) {
                $stmt->bind_param("ssdissi", $name, $category, $price, $stock, $description, $id, $vendor_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, category, price, stock_quantity, description, image_path, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssdissi", $name, $category, $price, $stock, $description, $image_path, $vendor_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header('Location: vendor_dashboard.php#products');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'Add'; ?> Product - AI Raw Material Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-xl font-medium text-gray-900"><?php echo $edit_mode ? 'Edit Product' : 'Add New Product'; ?></h1>
                <a href="vendor_dashboard.php#products" class="text-green-600 hover:text-green-500">Back to Dashboard</a>
            </div>
            
            <form action="<?php echo $edit_mode ? 'add_product.php?id=' . $id : 'add_product.php'; ?>" method="post" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="productName" class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" id="productName" name="product_name" value="<?php echo $edit_mode ? htmlspecialchars($product['name']) : ''; ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div>
                    <label for="productCategory" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="productCategory" name="category" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <?php
                        $categories = ['Vegetables', 'Fruits', 'Dairy', 'Meat', 'Grains'];
                        foreach ($categories as $cat) {
                            $selected = $edit_mode && $product['category'] == $cat ? 'selected' : '';
                            echo "<option value=\"$cat\" $selected>$cat</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="productPrice" class="block text-sm font-medium text-gray-700">Price</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" step="0.01" name="price" id="productPrice" value="<?php echo $edit_mode ? htmlspecialchars($product['price']) : ''; ?>" required class="focus:ring-green-500 focus:border-green-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div>
                        <label for="productStock" class="block text-sm font-medium text-gray-700">Stock Quantity</label>
                        <input type="number" id="productStock" name="stock" value="<?php echo $edit_mode ? htmlspecialchars($product['stock_quantity'] ?? $product['stock'] ?? '') : ''; ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
                
                <div>
                    <label for="productDescription" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="productDescription" name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500"><?php echo $edit_mode ? htmlspecialchars($product['description']) : ''; ?></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Product Image</label>
                    <?php if ($edit_mode && !empty($product['image_path'])): 
                        // Handle image_path display - if it's just a filename, prepend uploads/products/
                        $current_image = $product['image_path'];
                        if (strpos($current_image, 'uploads/') !== 0 && strpos($current_image, '/') !== 0) {
                            $current_image = 'uploads/products/' . $current_image;
                        }
                    ?>
                    <div class="mt-2 mb-4">
                        <p class="text-sm text-gray-500 mb-2">Current image:</p>
                        <img src="<?php echo htmlspecialchars($current_image); ?>" alt="Current product image" class="h-32 w-auto object-cover rounded-md">
                    </div>
                    <?php endif; ?>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500">
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
                    <a href="vendor_dashboard.php#products" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <?php echo $edit_mode ? 'Update Product' : 'Save Product'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
