<?php
$host = getenv('STOCKSMART_DB_HOST') ?: '127.0.0.1';
$port = getenv('STOCKSMART_DB_PORT') ?: '3306';
$db   = getenv('STOCKSMART_DB_NAME') ?: 'stocksmart';
$user = getenv('STOCKSMART_DB_USER') ?: 'root';
$pass = (string)getenv('STOCKSMART_DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Could not connect to the StockSmart database.');
}
