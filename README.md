# 🛒 AI Raw Material Marketplace

<div align="center">

A comprehensive, AI-powered marketplace platform connecting vendors and shopkeepers in the raw materials industry. Built with PHP, MySQL, and modern web technologies.

[![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://mysql.com/)
[![License](https://img.shields.io/badge/License-Educational-green.svg)](LICENSE)

[Features](#-features) • [Installation](#-installation) • [Configuration](#-configuration) • [Documentation](#-documentation) • [Troubleshooting](#-troubleshooting)

</div>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Screenshots](#-screenshots)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Database Setup](#-database-setup)
- [Usage Guide](#-usage-guide)
- [AI Assistant](#-ai-assistant)
- [Email Configuration](#-email-configuration)
- [Project Structure](#-project-structure)
- [API Endpoints](#-api-endpoints)
- [Security Features](#-security-features)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [License](#-license)
- [Credits](#-credits)

---

## 🎯 Overview

**AI Raw Material Marketplace** is a full-featured B2B e-commerce platform designed to facilitate seamless transactions between raw material vendors and shopkeepers. The platform features an intelligent AI assistant, real-time inventory management, multi-vendor support, and comprehensive order tracking.

### Key Highlights

- ✅ **Role-Based Access Control** - Separate dashboards for Shopkeepers, Vendors, and Admins
- ✅ **AI-Powered Assistant** - Multilingual support (English, Hindi, Telugu, Tamil, Kannada)
- ✅ **Real-Time Inventory** - Stock tracking and automatic updates
- ✅ **Order Management** - Complete order lifecycle with status tracking
- ✅ **Email Notifications** - Automated email alerts for orders
- ✅ **Responsive Design** - Mobile-friendly interface built with Tailwind CSS

---

## ✨ Features

### 👥 User Management
- Secure registration and authentication system
- Role-based access (Shopkeeper, Vendor, Admin)
- Password hashing with PHP's `password_hash()`
- Session management for secure login
- Forgot password functionality with OTP verification

### 🛍️ Product Management
- **For Vendors:**
  - Add, edit, and delete products
  - Upload product images
  - Set pricing and stock quantities
  - Categorize products
  - Track product performance

- **For Shopkeepers:**
  - Browse products with advanced search and filters
  - Filter by category, price range, and stock availability
  - Sort by price, stock, and relevance
  - View vendor information for each product

### 🛒 Shopping Cart & Checkout
- Persistent cart using localStorage
- Real-time cart updates
- Group orders by vendor
- Automatic stock validation
- Order confirmation with email notifications

### 📦 Order Management
- **Order Statuses:**
  - `pending` → `processing` → `shipped` → `delivered`
  - Cancellation support (only for pending/processing orders)
  
- **For Shopkeepers:**
  - View all orders with detailed information
  - Track order status in real-time
  - View vendor details for each order

- **For Vendors:**
  - View and manage orders containing their products
  - Update order status
  - Access shopkeeper contact information
  - Revenue tracking and analytics

### 🤖 AI Assistant
- Multilingual support (5 languages)
- Context-aware responses
- FAQ handling
- Site navigation assistance
- Developer information
- Fallback responses when APIs are unavailable
- Provider chain: Groq → Gemini → OpenAI → Fallback

### 📊 Dashboards
- **Shopkeeper Dashboard:**
  - Product browsing with search and filters
  - Shopping cart management
  - Order history
  - AI-powered product recommendations

- **Vendor Dashboard:**
  - Product management interface
  - Order tracking and management
  - Revenue statistics
  - Customer information access

- **Admin Dashboard:**
  - Platform oversight
  - User management capabilities
  - System analytics

### 📧 Email Notifications
- Order confirmation emails for shopkeepers
- New order notifications for vendors
- HTML-formatted emails with order details
- PHPMailer integration with SMTP support

---

## 🛠️ Tech Stack

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+** - Relational database
- **PHPMailer** - Email functionality

### Frontend
- **HTML5** - Markup
- **CSS3** - Styling
- **JavaScript (ES6+)** - Client-side interactivity
- **Tailwind CSS** - Utility-first CSS framework
- **Feather Icons** - Icon library

### AI Integration
- **Groq API** - Primary AI provider (Llama 3.1)
- **Google Gemini API** - Secondary AI provider
- **OpenAI API** - Tertiary AI provider (optional)

### Development Tools
- **XAMPP** - Local development environment
- **Composer** - PHP dependency management


---

## 📥 Installation

### Prerequisites

Before you begin, ensure you have the following installed:

- **XAMPP** (or any PHP/MySQL environment)
  - Download from [https://www.apachefriends.org/](https://www.apachefriends.org/)
  - Includes PHP, MySQL, and Apache
  
- **Web Browser** - Any modern browser (Chrome, Firefox, Edge, Safari)
- **Code Editor** (Optional) - VS Code, PhpStorm, or any preferred editor

### Step-by-Step Installation

#### 1. Clone or Download the Repository

```bash
git clone https://github.com/yourusername/FSD-II-CODE.git
# or download and extract the ZIP file
```

#### 2. Move Project to XAMPP Directory

```bash
# Copy the project folder to:
C:\xampp\htdocs\fsd\
# or
/opt/lampp/htdocs/fsd/  (Linux)
```

#### 3. Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** service (green status)
3. Start **MySQL** service (green status)

> **Note:** If MySQL shows a different port (e.g., 3307 instead of 3306), update `db.php` accordingly.

#### 4. Configure Database Connection

Edit `db.php` if your MySQL port is different:

```php
$host = 'localhost:3307';  // Change port if needed
$db   = 'hackathon_db';
$user = 'root';
$pass = '';  // XAMPP default is empty
```

#### 5. Set Up Database

1. Open your browser
2. Navigate to: `http://localhost/fsd/setup_database.php`
3. You should see green checkmarks for all setup steps

Alternatively, you can import the database manually:
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Create a new database named `hackathon_db`
- Run the SQL queries from `setup_database.php`

#### 6. Configure AI Assistant (Optional)

Edit `config.php` to add your API keys:

```php
'groq' => [
    'enabled' => true,
    'api_key' => 'YOUR_GROQ_API_KEY_HERE',
    // ...
],
'gemini' => [
    'enabled' => true,
    'api_key' => 'YOUR_GEMINI_API_KEY_HERE',
    // ...
]
```

**Get API Keys:**
- **Groq:** [https://console.groq.com/](https://console.groq.com/)
- **Gemini:** [https://makersuite.google.com/app/apikey](https://makersuite.google.com/app/apikey)
- **OpenAI:** [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)

#### 7. Set Up Email Notifications (Optional)

1. Copy `email_config.php.example` to `email_config.php` (if exists)
2. Edit `email_config.php` with your SMTP settings:

```php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com',
    'smtp_password' => 'your-app-password',
    'smtp_encryption' => 'tls',
    'from_email' => 'noreply@marketplace.com',
    'from_name' => 'Marketplace AI',
];
```

For Gmail users, see [Email Configuration](#-email-configuration) section.

#### 8. Set Permissions

Ensure the `uploads/products/` directory is writable:

```bash
# Windows: Right-click folder → Properties → Security → Edit permissions
# Linux/Mac:
chmod -R 755 uploads/
```

#### 9. Access the Application

- **Home Page:** `http://localhost/fsd/home.html`
- **Signup:** `http://localhost/fsd/signup page.html`
- **Login:** `http://localhost/fsd/login page.html`

---

## ⚙️ Configuration

### Database Configuration (`db.php`)

```php
$host = 'localhost:3307';  // MySQL host and port
$db   = 'hackathon_db';    // Database name
$user = 'root';            // MySQL username
$pass = '';                // MySQL password
```

### AI Configuration (`config.php`)

The AI assistant uses a provider chain for reliability. Configure priority in `config.php`:

```php
'order' => ['groq', 'gemini', 'openai'],  // Provider priority
```

### Email Configuration (`email_config.php`)

See [Email Configuration](#-email-configuration) section below.

---

## 🗄️ Database Setup

### Automatic Setup

Run `setup_database.php` in your browser:
```
http://localhost/fsd/setup_database.php
```

### Manual Setup

The database includes the following tables:

- **`users`** - User accounts (shopkeepers, vendors, admins)
- **`products`** - Product catalog
- **`orders`** - Order records
- **`order_items`** - Individual items in orders
- **`vendors`** - Vendor information (synced with users)

### Database Schema

<details>
<summary>View Database Schema</summary>

```sql
-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('shopkeeper', 'vendor', 'admin') NOT NULL,
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    postal_code VARCHAR(20),
    country VARCHAR(50),
    phone VARCHAR(20),
    shop_name VARCHAR(100),
    shop_type VARCHAR(50),
    business_name VARCHAR(100),
    vendor_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    vendor_id INT,
    image_path VARCHAR(255),
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order Items Table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

</details>

---

## 📖 Usage Guide

### For Shopkeepers

1. **Registration:**
   - Visit the signup page
   - Select "Shopkeeper" role
   - Fill in personal and shop details
   - Create account

2. **Shopping:**
   - Browse products on the dashboard
   - Use search and filters to find items
   - Add products to cart
   - Review cart and checkout

3. **Order Management:**
   - View order history
   - Track order status
   - Contact vendors if needed

### For Vendors

1. **Registration:**
   - Visit the signup page
   - Select "Vendor" role
   - Fill in business details
   - Create account

2. **Product Management:**
   - Add products via "Add Product" button
   - Upload product images
   - Set prices and stock quantities
   - Edit or delete products as needed

3. **Order Management:**
   - View orders in Vendor Dashboard
   - Update order status (pending → processing → shipped → delivered)
   - View shopkeeper details
   - Track revenue

### For Admins

1. Access admin dashboard for platform oversight
2. Manage users and products
3. View system-wide analytics

---

## 🤖 AI Assistant

The AI Assistant provides multilingual support and intelligent responses about the platform.

### Supported Languages

- English (en)
- Hindi (hi)
- Telugu (te)
- Tamil (ta)
- Kannada (kn)

### Features

- **Context Awareness:** Remembers conversation history
- **FAQ Handling:** Answers common questions about the platform
- **Navigation Help:** Guides users to different sections
- **Developer Information:** Provides information about creators
- **Fallback Responses:** Works even when APIs are unavailable

### Accessing the AI Assistant

1. Login to your account (any role)
2. Navigate to "AI Assistant" in the menu
3. Start chatting in your preferred language

### Configuration

Edit `config.php` to customize AI providers:

```php
'providers' => [
    'order' => ['groq', 'gemini', 'openai'],
    'groq' => [
        'enabled' => true,
        'api_key' => 'YOUR_KEY',
        'model' => 'llama-3.1-8b-instant',
    ],
    // ...
]
```

---

## 📧 Email Configuration

### Gmail Setup

1. **Enable 2-Step Verification:**
   - Go to Google Account → Security → 2-Step Verification

2. **Generate App Password:**
   - Google Account → Security → App passwords
   - Select "Mail" and generate password
   - Copy the 16-character password

3. **Configure `email_config.php`:**
   ```php
   'smtp_username' => 'your-email@gmail.com',
   'smtp_password' => 'xxxx xxxx xxxx xxxx',  // App password
   'smtp_host' => 'smtp.gmail.com',
   'smtp_port' => 587,
   'smtp_encryption' => 'tls',
   ```

### Other Email Providers

**Outlook/Hotmail:**
```php
'smtp_host' => 'smtp-mail.outlook.com',
'smtp_port' => 587,
'smtp_encryption' => 'tls',
```

**Yahoo:**
```php
'smtp_host' => 'smtp.mail.yahoo.com',
'smtp_port' => 587,
'smtp_encryption' => 'tls',
```

See `EMAIL_SETUP.md` for detailed instructions.

---

## 📁 Project Structure

```
FSD-II-CODE/
├── config.php                    # AI provider configuration
├── db.php                        # Database connection
├── email_config.php              # Email SMTP settings
├── setup_database.php            # Database initialization script
│
├── Authentication/
│   ├── signup.php                # User registration backend
│   ├── signup page.html          # Registration frontend
│   ├── login.php                 # Authentication backend
│   ├── login page.html           # Login frontend
│   ├── logout.php                # Session termination
│   ├── forgot_password.php       # Password reset backend
│   ├── forgot_password.html      # Password reset frontend
│   ├── reset_password.php        # Password reset handler
│   ├── send_login_otp.php        # OTP sender
│   └── verify_login_otp.php      # OTP verification
│
├── Dashboards/
│   ├── shopkeeper_dashboard.php  # Shopkeeper interface
│   ├── vendor_dashboard.php      # Vendor interface
│   ├── admin dashboard.html      # Admin interface
│   └── home.html                 # Home page
│
├── Products/
│   ├── get_products.php          # Product API endpoint
│   ├── add_product.php           # Add/edit product page
│   ├── edit_product.php          # Product editing
│   └── delete_product.php        # Product deletion
│
├── Orders/
│   ├── checkout.php              # Order processing
│   ├── orders.html               # Order management UI
│   ├── place_order.php           # Order creation
│   ├── update_order_status.php   # Status updates
│   ├── view_order.php            # Order details
│   ├── vendor_orders.php         # Vendor order view
│   ├── shopkeeper_orders.php     # Shopkeeper order view
│   └── cancel_order.php          # Order cancellation
│
├── AI Assistant/
│   ├── ai_assistant.html         # AI chat interface
│   ├── ai_assistant_backend.php  # AI response handler
│   ├── ai_assistant_reset.php    # Reset chat history
│   └── all_ai_suggestions.php    # Suggestion history
│
├── Assets/
│   ├── style.css                 # Custom styles
│   ├── images/                   # Static images
│   └── uploads/products/         # Product images
│
├── vendor/                       # Composer dependencies
│   └── phpmailer/                # PHPMailer library
│
└── README.md                     # This file
```

---

## 🔌 API Endpoints

### Product Endpoints

- **GET** `/get_products.php`
  - Query parameters: `category`, `search`, `min_price`, `max_price`, `sort`
  - Returns: JSON array of products

### Authentication Endpoints

- **POST** `/login.php`
  - Body: `email`, `password`, `role`
  - Returns: Role string on success

- **POST** `/signup.php`
  - Body: User registration data
  - Returns: `success` or error message

### Order Endpoints

- **POST** `/checkout.php`
  - Body: `cart` (JSON array)
  - Returns: Order confirmation JSON

- **POST** `/update_order_status.php`
  - Body: `order_id`, `status`
  - Returns: Success/error message

### AI Assistant Endpoints

- **POST** `/ai_assistant_backend.php`
  - Body: `message`, `language`
  - Returns: AI response JSON

---

## 🔒 Security Features

- ✅ **Password Hashing:** Uses PHP's `password_hash()` with bcrypt
- ✅ **Prepared Statements:** All SQL queries use prepared statements to prevent SQL injection
- ✅ **Input Validation:** Server-side validation for all user inputs
- ✅ **Session Management:** Secure session handling with role-based access
- ✅ **CSRF Protection:** Session tokens for form submissions (recommended addition)
- ✅ **XSS Prevention:** `htmlspecialchars()` used for output escaping
- ✅ **File Upload Validation:** Image type and size validation

### Security Recommendations

- [ ] Implement CSRF tokens for forms
- [ ] Add rate limiting for login attempts
- [ ] Use HTTPS in production
- [ ] Regular security audits
- [ ] Update dependencies regularly

---

## 🔧 Troubleshooting

### Common Issues

#### 1. "Database connection failed"

**Problem:** MySQL service not running or wrong port.

**Solution:**
- Open XAMPP Control Panel
- Start MySQL service
- Verify port in `db.php` (default: 3307 for some XAMPP installations)

#### 2. "No connection could be made"

**Problem:** MySQL port conflict.

**Solution:**
- Check XAMPP MySQL port (click "Config" → "my.ini")
- Update `db.php` with correct port
- Restart MySQL service

#### 3. Images not uploading

**Problem:** Permission issues or directory missing.

**Solution:**
```bash
# Create uploads directory
mkdir -p uploads/products

# Set permissions (Linux/Mac)
chmod -R 755 uploads/

# Windows: Right-click → Properties → Security → Edit
```

#### 4. AI Assistant not responding

**Problem:** Missing or invalid API keys.

**Solution:**
- Check `config.php` for API keys
- Verify keys are valid and have credits
- AI will use fallback responses if APIs fail

#### 5. Emails not sending

**Problem:** SMTP configuration incorrect.

**Solution:**
- Verify `email_config.php` settings
- For Gmail, use App Password (not regular password)
- Check PHP error logs for SMTP errors
- Test SMTP connection separately

#### 6. Session expires quickly

**Problem:** PHP session timeout.

**Solution:**
Edit `php.ini`:
```ini
session.gc_maxlifetime = 3600  # 1 hour
```

#### 7. "Order status update failed"

**Problem:** ENUM value mismatch.

**Solution:**
- Check order status ENUM values in database
- Ensure status values match: `pending`, `processing`, `shipped`, `delivered`, `cancelled`
- Run `fix_status_enum.php` if available

### Getting Help

1. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
2. Check MySQL error logs: XAMPP Control Panel → MySQL → Logs
3. Enable error display (development only):
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. **Fork the repository**
2. **Create a feature branch:**
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Commit your changes:**
   ```bash
   git commit -m 'Add amazing feature'
   ```
4. **Push to the branch:**
   ```bash
   git push origin feature/amazing-feature
   ```
5. **Open a Pull Request**

### Contribution Guidelines

- Follow existing code style
- Add comments for complex logic
- Test your changes thoroughly
- Update documentation if needed

---

## 📄 License

This project is developed for **educational and demonstration purposes**.

### Usage Rights

- ✅ Use for learning and education
- ✅ Modify for personal projects
- ✅ Share with attribution
- ❌ Commercial use (without permission)
- ❌ Remove attribution

---

## 👨‍💻 Credits

### Developers

- **Rishi Vedi** - Development & Design
- **N. Yaswanth** - Development & Backend

### Acknowledgments

- **XAMPP** - Development environment
- **Tailwind CSS** - CSS framework
- **PHPMailer** - Email functionality
- **Groq** - AI API provider
- **Google Gemini** - AI API provider
- **OpenAI** - AI API provider

### Special Thanks

Thanks to all contributors and users who provided feedback during development.

---

## 📞 Contact & Support

For questions, issues, or support:

- **GitHub Issues:** [Create an issue]
- **Email:** support@marketplace-ai.com 

---

## 🗺️ Roadmap

Future enhancements planned:

- [ ] Payment gateway integration
- [ ] Real-time chat between users
- [ ] Advanced analytics dashboard
- [ ] Mobile app (React Native)
- [ ] Multi-currency support
- [ ] Product reviews and ratings
- [ ] Wishlist functionality
- [ ] Automated inventory alerts

---

<div align="center">

**Made with ❤️ by Rishi Vedi & N. Yaswanth**

⭐ **Star this repository if you find it helpful!**

</div>
