<?php
// Database connection using PDO
// Load local config if present (recommended to keep credentials out of VCS)
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Application log
if (!defined('APP_LOG')) define('APP_LOG', __DIR__ . '/logs/app.log');

$host = 'localhost';
$db   = 'alburaq';
// Use configured DB user/pass if present, otherwise fall back to root (local default)
$user = defined('DB_USER') ? DB_USER : 'root';
$pass = defined('DB_PASS') ? DB_PASS : '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Log full error details to server-side app log (not shown to users)
    @error_log("[" . date('Y-m-d H:i:s') . "] DB CONNECTION ERROR: " . $e->getMessage() . PHP_EOL, 3, APP_LOG);
    http_response_code(500);
    // Generic message returned to client
    echo "Server error: please try again later.";
    exit;
}