<?php
// ---- Database configuration (change if your MySQL settings differ) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'vehicle_rental');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default is empty
define('APP_NAME', 'DriveEasy Rentals');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('<h2 style="font-family:sans-serif;color:#b00">Database connection failed.</h2>'
      . '<p style="font-family:sans-serif">Make sure MySQL is running and you imported <b>database.sql</b>.<br>Error: '
      . htmlspecialchars($e->getMessage()) . '</p>');
}

require_once __DIR__ . '/../includes/functions.php';
