<?php
session_start();
require 'db.php';

// Check if user is logged in as shopkeeper
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shopkeeper') {
    header("Location: login.php");
    exit();
}

$shopkeeper_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopkeeper Dashboard - AI Raw Material Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-blue-600">Marketplace AI</h1>
                    </div>
                    <nav class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="shopkeeper_dashboard.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Dashboard</a>
                        <a href="shopkeeper_orders.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Orders</a>
                        <a href="ai_assistant.html" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">AI Assistant</a>
                    </nav>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <div class="ml-3 relative flex items-center space-x-3">
                        <a href="logout.php" class="text-sm text-gray-600 hover:text-red-600">Logout</a>
                        <div>
                            <button type="button" class="bg-white rounded-full flex text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="user-menu-button">
                                <span class="sr-only">Open user menu</span>
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">SK</div>
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
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Sidebar -->
                <div class="w-full md:w-64 flex-shrink-0">
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Categories</h2>
                        <ul class="space-y-2" id="categoryList">
                            <li>
                                <a href="#" data-category="all" class="category-link flex items-center p-2 text-sm font-medium text-gray-900 rounded-lg hover:bg-gray-100">
                                    <span class="ml-3">All Materials</span>
                                </a>
                            </li>
                            <?php
                            // Get unique categories from products
                            $sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''";
                            $result = $conn->query($sql);
                            
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<li>';
                                    echo '<a href="#" data-category="' . htmlspecialchars($row['category']) . '" class="category-link flex items-center p-2 text-sm font-medium text-gray-900 rounded-lg hover:bg-gray-100">';
                                    echo '<span class="ml-3">' . htmlspecialchars($row['category']) . '</span>';
                                    echo '</a>';
                                    echo '</li>';
                                }
                            }
                            ?>
                        </ul>
                        
                        <div class="mt-6">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Filters</h2>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price Range</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" id="priceMin" placeholder="Min" class="w-full px-2 py-1 border border-gray-300 rounded">
                                        <span>to</span>
                                        <input type="number" id="priceMax" placeholder="Max" class="w-full px-2 py-1 border border-gray-300 rounded">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AI Recommendations -->
                    <div class="bg-white p-4 rounded-lg shadow-sm mt-4">
                        <h2 class="text-lg font-medium text-gray-900 mb-2">AI Suggestions</h2>
                        <div class="text-sm text-gray-600">
                            <p class="mb-2">Based on your purchase history, we recommend:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <?php
                                // Get random products for recommendations
                                $sql = "SELECT name FROM products ORDER BY RAND() LIMIT 3";
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<li>' . htmlspecialchars($row['name']) . '</li>';
                                    }
                                } else {
                                    echo '<li>No recommendations available</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <div class="flex-1">
                    <!-- Search and Sort -->
                    <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="relative flex-1">
                                <input type="text" id="searchInput" placeholder="Search raw materials..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <div class="absolute left-3 top-2.5 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">Sort by:</span>
                                <select id="sortBy" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="best">Best Match</option>
                                    <option value="priceLow">Price: Low to High</option>
                                    <option value="priceHigh">Price: High to Low</option>
                                    <option value="stock">Stock: High to Low</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Products Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="productsGrid">
                        <!-- Products will be rendered dynamically here -->
                    </div>
                    
                    <!-- Shopping Cart Button -->
                    <div class="fixed bottom-6 right-6">
                        <button id="cartButton" class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-full shadow-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center cart-count">0</span>
                        </button>
                    </div>
                    
                    <!-- Shopping Cart Sidebar -->
                    <div id="cartSidebar" class="fixed top-0 right-0 h-full w-80 bg-white shadow-lg transform translate-x-full transition-transform duration-300 ease-in-out z-50">
                        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-medium text-gray-900">Your Cart</h2>
                            <button id="closeCart" class="text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div id="cartItems" class="p-4 overflow-y-auto h-[calc(100%-12rem)]">
                            <!-- Cart items will be rendered here -->
                        </div>
                        <div class="p-4 border-t border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-lg font-medium text-gray-900">Total:</span>
                                <span id="cartTotal" class="text-lg font-bold text-gray-900">₹0.00</span>
                            </div>
                            <button id="checkoutButton" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Checkout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // --- Dynamic Cart Logic with localStorage ---
      let cart = [];
      // Load cart from localStorage
      if (localStorage.getItem('cart')) {
        try {
          cart = JSON.parse(localStorage.getItem('cart')) || [];
          updateCartCount();
        } catch (e) { cart = []; }
      }
      
      // --- Dynamic Product Rendering ---
      let allProducts = [];
      let filteredProducts = [];
      let currentCategory = 'all';
      let currentSearch = '';
      let currentSort = 'best';
      let currentPriceMin = '';
      let currentPriceMax = '';
      
      // Define category mappings for better filtering
      const categoryMappings = {
        'Fresh Produce': ['fresh', 'produce', 'fruit', 'vegetable', 'vegetables', 'fruits'],
        'Dry Goods': ['dry', 'grain', 'cereal', 'pasta', 'rice', 'sugar', 'tea', 'coffee', 'spice', 'spices', 'flour', 'bean', 'beans', 'nut', 'nuts'],
        'Dairy Products': ['dairy', 'milk', 'cheese', 'yogurt', 'butter', 'cream', 'curd'],
        'Meat & Poultry': ['meat', 'poultry', 'chicken', 'beef', 'pork', 'lamb', 'fish', 'seafood']
      };

      function applyFilters() {
        filteredProducts = allProducts.filter(p => {
          // Category filtering
          if (currentCategory !== 'all') {
            if (categoryMappings[currentCategory]) {
              // Check if product category matches any of the keywords for this category
              if (!p.category) return false;
              
              const productCategoryLower = p.category.toLowerCase();
              const matchesCategory = categoryMappings[currentCategory].some(keyword => 
                productCategoryLower.includes(keyword)
              );
              
              if (!matchesCategory) return false;
            } else if (p.category !== currentCategory) {
              // For custom categories not in our mapping
              return false;
            }
          }
          
          // Search filtering
          if (currentSearch) {
            const searchLower = currentSearch.toLowerCase();
            const nameMatch = p.name && p.name.toLowerCase().includes(searchLower);
            const descMatch = p.description && p.description.toLowerCase().includes(searchLower);
            const categoryMatch = p.category && p.category.toLowerCase().includes(searchLower);
            
            if (!(nameMatch || descMatch || categoryMatch)) return false;
          }
          
          // Price filtering
          if (currentPriceMin && parseFloat(p.price) < parseFloat(currentPriceMin)) return false;
          if (currentPriceMax && parseFloat(p.price) > parseFloat(currentPriceMax)) return false;
          
          return true;
        });
        // Sort products based on selected criteria
        switch(currentSort) {
          case 'priceLow':
            filteredProducts.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
            break;
          case 'priceHigh':
            filteredProducts.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
            break;
          case 'stock':
            filteredProducts.sort((a, b) => {
              const stockA = parseInt(a.stock || a.stock_quantity || 0);
              const stockB = parseInt(b.stock || b.stock_quantity || 0);
              return stockB - stockA;
            });
            break;
          case 'name':
            filteredProducts.sort((a, b) => a.name.localeCompare(b.name));
            break;
          case 'best':
          default:
            // For 'best match', we keep the original order or could implement a relevance score
            // No specific sorting needed
            break;
        }
        renderProducts(filteredProducts);
      }

      function renderProducts(products) {
        const grid = document.getElementById('productsGrid');
        grid.innerHTML = '';
        
        if (products.length === 0) {
          grid.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500">No products found matching your criteria.</div>';
          return;
        }
        
        products.forEach((product, idx) => {
          // Get vendor name if available
          let vendorInfo = '';
          if (product.vendor_id) {
            vendorInfo = `<p class="text-xs text-gray-500 mt-1">Vendor: ${product.vendor_name || 'ID: ' + product.vendor_id}</p>`;
          }
          
          // Default image if none provided
          const imageUrl = product.image_url || 'images/default.jpg';
          
          // Get stock value - handle both stock and stock_quantity fields
          const stockValue = parseInt(product.stock || product.stock_quantity || 0);
          
          // Always show stock count prominently, even if 0
          const stockClass = stockValue > 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
          const stockDisplay = `<div class="mt-2">
                                  <span class="text-sm ${stockClass}">
                                    <strong>Stock:</strong> ${stockValue}
                                  </span>
                                </div>`;
          
          // Disable add to cart button if out of stock
          const buttonDisabled = stockValue <= 0;
          
          grid.innerHTML += `
            <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
              <div class="relative">
                <img src="${imageUrl}" alt="${product.name}" class="w-full h-48 object-cover">
              </div>
              <div class="p-4">
                <h3 class="text-lg font-medium text-gray-900">${product.name}</h3>
                <p class="text-sm text-gray-500">${product.description || ''}</p>
                ${vendorInfo}
                <div class="mt-2 flex justify-between items-center">
                  <span class="text-xl font-bold text-gray-900">₹${product.price}</span>
                </div>
                ${stockDisplay}
                <button class="add-to-cart-btn mt-3 w-full ${buttonDisabled ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'} text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors" data-idx="${idx}" ${buttonDisabled ? 'disabled' : ''}>
                  ${buttonDisabled ? 'Out of Stock' : 'Add to Cart'}
                </button>
              </div>
            </div>
          `;
        });
        
        // Attach add to cart events
        document.querySelectorAll('.add-to-cart-btn:not([disabled])').forEach((btn, idx) => {
          btn.onclick = () => {
            const productIdx = parseInt(btn.getAttribute('data-idx'));
            addToCart({...products[productIdx], id: products[productIdx].id, price: parseFloat(products[productIdx].price)});
          };
        });
      }
      
      // Cart functionality
      function addToCart(product) {
        const found = cart.find(item => item.id == product.id);
        if (found) {
          found.qty += 1;
        } else {
          cart.push({...product, qty: 1});
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount();
        renderCartItems();
      }
      
      function updateCartCount() {
        document.querySelector('.cart-count').textContent = cart.length;
      }
      
      function renderCartItems() {
        const cartItemsContainer = document.getElementById('cartItems');
        const cartTotalElement = document.getElementById('cartTotal');
        
        if (cart.length === 0) {
          cartItemsContainer.innerHTML = '<div class="text-center py-8 text-gray-500">Your cart is empty</div>';
          cartTotalElement.textContent = '₹0.00';
          return;
        }
        
        let total = 0;
        cartItemsContainer.innerHTML = '';
        
        cart.forEach((item, index) => {
          const itemTotal = item.price * item.qty;
          total += itemTotal;
          
          cartItemsContainer.innerHTML += `
            <div class="flex items-center py-2 border-b border-gray-100">
              <div class="flex-1">
                <h4 class="text-sm font-medium text-gray-900">${item.name}</h4>
                <p class="text-xs text-gray-500">₹${item.price} x ${item.qty}</p>
              </div>
              <div class="flex items-center">
                <button class="cart-qty-btn text-gray-500 hover:text-gray-700 px-1" data-index="${index}" data-action="decrease">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                  </svg>
                </button>
                <span class="text-sm mx-1">${item.qty}</span>
                <button class="cart-qty-btn text-gray-500 hover:text-gray-700 px-1" data-index="${index}" data-action="increase">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                </button>
                <button class="cart-remove-btn text-red-500 hover:text-red-700 ml-2" data-index="${index}">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          `;
        });
        
        cartTotalElement.textContent = '₹' + total.toFixed(2);
        
        // Attach event listeners to cart item buttons
        document.querySelectorAll('.cart-qty-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            const action = this.getAttribute('data-action');
            
            if (action === 'increase') {
              cart[index].qty += 1;
            } else if (action === 'decrease') {
              if (cart[index].qty > 1) {
                cart[index].qty -= 1;
              } else {
                cart.splice(index, 1);
              }
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            renderCartItems();
          });
        });
        
        document.querySelectorAll('.cart-remove-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            cart.splice(index, 1);
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            renderCartItems();
          });
        });
      }
      
      // Cart sidebar toggle
      document.getElementById('cartButton').addEventListener('click', function() {
        document.getElementById('cartSidebar').classList.remove('translate-x-full');
        renderCartItems();
      });
      
      document.getElementById('closeCart').addEventListener('click', function() {
        document.getElementById('cartSidebar').classList.add('translate-x-full');
      });
      
      // Create modal for final order summary (after placing order)
      const orderModal = document.createElement('div');
      orderModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden';
      orderModal.id = 'orderModal';
      orderModal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-900">Order Confirmation</h3>
            <button id="closeOrderModal" class="text-gray-500 hover:text-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div id="orderDetails" class="mb-4"></div>
          <div class="flex justify-between items-center mb-2">
            <span class="text-lg font-medium">Total:</span>
            <span id="orderTotal" class="text-lg font-bold"></span>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <button id="viewOrdersButton" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
              View My Orders
            </button>
          </div>
        </div>
      `;
      document.body.appendChild(orderModal);
      
      // Close modal event
      document.getElementById('closeOrderModal').addEventListener('click', function() {
        document.getElementById('orderModal').classList.add('hidden');
      });
      
      // View orders button event
      document.getElementById('viewOrdersButton').addEventListener('click', function() {
        window.location.href = 'shopkeeper_orders.php';
      });
      
      // Direct checkout - redirect to orders.html
      const checkoutButton = document.getElementById('checkoutButton');
      checkoutButton.addEventListener('click', function() {
        if (cart.length === 0) {
          alert('Your cart is empty');
          return;
        }
        
        // Store cart data in localStorage for orders.html to access
        localStorage.setItem('checkout_cart', JSON.stringify(cart));
        
        // Redirect directly to orders.html
        window.location.href = 'orders.html';
      });
      
      // Fetch products with PHP
      fetch('get_products.php')
        .then(res => res.json())
        .then(products => {
          allProducts = products;
          applyFilters();
        });
        
      // Category filter logic
      document.querySelectorAll('.category-link').forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          currentCategory = link.getAttribute('data-category');
          // Highlight the selected category
          document.querySelectorAll('.category-link').forEach(l => {
            l.classList.remove('bg-blue-100', 'text-blue-700');
            l.classList.add('hover:bg-gray-100', 'text-gray-900');
          });
          link.classList.add('bg-blue-100', 'text-blue-700');
          link.classList.remove('hover:bg-gray-100', 'text-gray-900');
          applyFilters();
        });
      });
      
      // --- Filter and Sort Event Listeners ---
      document.getElementById('searchInput').addEventListener('input', function() {
        currentSearch = this.value.trim().toLowerCase();
        applyFilters();
      });
      
      document.getElementById('sortBy').addEventListener('change', function() {
        currentSort = this.value;
        applyFilters();
      });
      
      document.getElementById('priceMin').addEventListener('input', function() {
        currentPriceMin = this.value;
        applyFilters();
      });
      
      document.getElementById('priceMax').addEventListener('input', function() {
        currentPriceMax = this.value;
        applyFilters();
      });
    });
    </script>
</body>
</html>