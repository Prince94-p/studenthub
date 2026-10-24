<?php
// db.php - shared database connection (Practical 8). PDO for most pages, MySQLi for P9/P11/P12.
$DB_HOST = 'localhost';
$DB_NAME = 'studenthub';
$DB_USER = 'root';
$DB_PASS = '';          // XAMPP default is empty

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());               // log the real error, never show it to users
    die('Database connection failed. Please check includes/db.php settings.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    die('Database connection failed (MySQLi).');
}
