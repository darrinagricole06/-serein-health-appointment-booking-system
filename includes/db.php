<?php
// MySQLi database connection (Task 4)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '12345678');
define('DB_NAME', 'serein_health');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die('Database connection failed: ' . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
}

$conn->set_charset('utf8mb4');
?>