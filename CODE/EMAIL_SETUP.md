# Email Configuration Guide

## Setup Instructions

1. **Configure SMTP Settings**
   - Edit `email_config.php` and update the following:
     - `smtp_username`: Your email address
     - `smtp_password`: Your email password or app password
     - `smtp_host`: SMTP server (default: smtp.gmail.com)
     - `smtp_port`: Port number (default: 587 for TLS)

2. **For Gmail Users:**
   - Enable 2-Step Verification in your Google Account
   - Generate an App Password:
     - Go to Google Account → Security → 2-Step Verification → App passwords
     - Generate a new app password for "Mail"
     - Use this app password (not your regular password) in `email_config.php`

3. **For Other Email Providers:**
   - **Outlook/Hotmail**: 
     - Host: `smtp-mail.outlook.com`
     - Port: `587`
     - Encryption: `tls`
   
   - **Yahoo**:
     - Host: `smtp.mail.yahoo.com`
     - Port: `587`
     - Encryption: `tls`

## Email Features

When an order is placed:

### Shopkeeper Email Contains:
- ✅ Vendor details (name, email, phone, address)
- ✅ Order information (ID, date, total, items)
- ✅ Link to track order (`view_order.php?id=X`)

### Vendor Email Contains:
- ✅ Order details (ID, date, total, items)
- ✅ Shopkeeper details (name, business, email, phone)
- ✅ Link to view order (`shopkeeper_details.php?id=X&order_id=Y`)

## Testing

After configuration, place a test order to verify emails are being sent correctly. Check the PHP error log if emails are not being delivered.

## Troubleshooting

- **Emails not sending**: Check PHP error logs for SMTP connection errors
- **Authentication failed**: Verify your email and password are correct
- **Connection timeout**: Check firewall settings and SMTP port accessibility

