<?php
// Email / SMTP settings for appointment notifications.
// Fill these in to send real emails. Leave empty to fall back to PHP mail().
return [
    'host' => '',           // e.g. smtp.gmail.com
    'port' => 587,
    'username' => '',       // your SMTP username / email
    'password' => '',       // your SMTP password or app password
    'encryption' => 'tls',  // tls, ssl, or none
    'from_email' => 'noreply@serein.sc',
    'from_name' => 'Serein Health',
];
