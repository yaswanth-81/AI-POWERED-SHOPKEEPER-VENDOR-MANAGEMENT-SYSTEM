<?php
/**
 * Email Configuration for Marketplace AI
 * 
 * Configure your SMTP settings here
 * For Gmail, you'll need to:
 * 1. Enable 2-Step Verification
 * 2. Generate an App Password (not your regular password)
 * 3. Use the App Password below
 */

return [
    'smtp_host' => 'smtp.gmail.com',        // SMTP server (Gmail, Outlook, etc.)
    'smtp_port' => 587,                     // Port (587 for TLS, 465 for SSL)
    'smtp_username' => 'aipoweredshopkeepervendor@gmail.com',  // Your email address
    'smtp_password' => 'rujw nmab uxnh hcoy',      // Your email password or app password
    'smtp_encryption' => 'tls',             // 'tls' or 'ssl'
    'from_email' => 'noreply@marketplace.com', // Sender email
    'from_name' => 'Marketplace AI',         // Sender name
];

