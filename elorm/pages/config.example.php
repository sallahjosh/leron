<?php
// Copy this file to config.php and fill in your Twilio credentials.
// Never commit real secrets to version control.

return [
    // Your Twilio Account SID (e.g., ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx)
    'TWILIO_ACCOUNT_SID' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',

    // Your Twilio Auth Token
    'TWILIO_AUTH_TOKEN' => 'your_auth_token_here',

    // A verified Twilio phone number in E.164 format (e.g., +12025551234)
    'TWILIO_FROM' => '+12025551234',

    // Destination number in E.164 format. For Ghana: +233 followed by 9 digits.
    // Example for 0534286806 -> +233534286806
    'DEST_TO' => '+233534286806',
    
    // SMTP (Gmail) settings for email notifications
    // Set these to enable email sending instead of SMS
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => 587,
    'SMTP_SECURE' => 'tls', // tls or ssl
    'SMTP_USER' => 'your_gmail_address@gmail.com',
    'SMTP_PASS' => 'your_app_password_here',
    // Where contact messages should be delivered
    'SMTP_TO'   => 'leronsepenoo@gmail.com',
    // Visible From header (can be same as SMTP_USER)
    'SMTP_FROM' => 'your_gmail_address@gmail.com',
];
