<?php
// Twilio credentials and destination number for contact form SMS
// Fill these with your real Twilio credentials. Do NOT commit real secrets publicly.

return [
    // Your Twilio Account SID (LIVE)
    'TWILIO_ACCOUNT_SID' => 'AC0e6030aada8a15528f7fcde2f84d9c3f',

    // Your Twilio Auth Token (LIVE)
    'TWILIO_AUTH_TOKEN'  => '1f46b17f0429b9e447e06734997dfc96',

    // Your Twilio phone number in E.164 format (e.g., +12025551234)
    'TWILIO_FROM'        => '+18049069972',

    // Destination number in E.164 format (Ghana example: +233534286806)
    'DEST_TO'            => '+233534286806',

    // SMTP (Gmail) settings for email notifications
    // Fill SMTP_USER with your Gmail and SMTP_PASS with your Gmail App Password (not your normal password)
    'SMTP_HOST'   => 'smtp.gmail.com',
    'SMTP_PORT'   => 587,
    'SMTP_SECURE' => 'tls', // tls or ssl
    'SMTP_USER'   => '', // your Gmail address goes here
    'SMTP_PASS'   => '', // your Gmail App Password goes here
    // Destination for contact messages
    'SMTP_TO'     => 'leronsepenoo@gmail.com',
    // Visible From header (usually same as SMTP_USER)
    'SMTP_FROM'   => '',
];
