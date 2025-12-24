# AI Raw Material Marketplace

A comprehensive marketplace platform for connecting vendors and shopkeepers in the raw materials industry.

## Features

- **User Registration & Authentication**: Support for shopkeepers, vendors, and admins
- **Product Management**: Add, edit, and manage products with images
- **Order Management**: Complete order processing system
- **Dashboard**: Role-specific dashboards for different user types
- **AI Assistant**: Integrated AI assistant for marketplace queries

## Setup Instructions

### Prerequisites

1. **XAMPP** - Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. **Web Browser** - Any modern web browser

### Installation Steps

1. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL** services
   - Ensure both services show green status

2. **Database Setup**
   - Open your web browser
   - Navigate to: `http://localhost/fsd/setup_database.php`
   - This will create the database and all necessary tables
   - You should see green checkmarks for all setup steps

3. **Access the Application**
   - Navigate to: `http://localhost/fsd/`
   - Start with the signup page: `http://localhost/fsd/signup page.html`

## File Structure

```
fsd/
├── db.php                          # Database connection
├── setup_database.php              # Database setup script
├── signup.php                      # User registration backend
├── signup page.html                # User registration frontend
├── login.php                       # User authentication backend
├── login page.html                 # User authentication frontend
├── home.html                       # Home page
├── vendor dashboard.html           # Vendor dashboard
├── shopkeeper dashboard.html       # Shopkeeper dashboard
├── admin dashboard.html            # Admin dashboard
├── add_product.php                 # Product management
├── checkout.php                    # Order processing
├── orders.html                     # Order management
└── uploads/                        # Product images storage
```

## User Roles

### Shopkeeper
- Browse and purchase products
- Manage orders and track deliveries
- Access to shopkeeper dashboard

### Vendor
- Add and manage products
- View and process orders
- Access to vendor dashboard

### Admin
- Oversee all marketplace activities
- Manage users and products
- Access to admin dashboard

## Troubleshooting

### Common Issues

1. **"No connection could be made because the target machine actively refused it"**
   - **Solution**: Start MySQL service in XAMPP Control Panel
   - Ensure MySQL is running (green status)

2. **"Database connection failed"**
   - **Solution**: Run `setup_database.php` to create the database
   - Check if MySQL service is running

3. **"Registration failed"**
   - **Solution**: Ensure all required fields are filled
   - Check if email is unique
   - Verify password is at least 8 characters

4. **Images not uploading**
   - **Solution**: Check `uploads/` folder permissions
   - Ensure folder exists and is writable

### Database Connection Issues

If you're still having database connection issues:

1. **Check XAMPP Status**
   - Open XAMPP Control Panel
   - Ensure both Apache and MySQL are running (green)
   - If not, click "Start" for both services

2. **Check MySQL Port**
   - Default MySQL port is 3306
   - If port is in use, change it in XAMPP settings

3. **Verify Database Exists**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Check if `hackathon_db` database exists
   - If not, run `setup_database.php`

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Input validation and sanitization
- Session management for user authentication

## Development Notes

- Built with PHP, MySQL, HTML, CSS, and JavaScript
- Uses Tailwind CSS for styling
- Responsive design for mobile compatibility
- Modular code structure for easy maintenance

## Support

For technical support or questions:
1. Check the troubleshooting section above
2. Ensure XAMPP services are running
3. Verify database setup is complete
4. Check browser console for JavaScript errors

## License

This project is developed for educational and demonstration purposes.
