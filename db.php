<?php
require_once __DIR__ . '/maintenance-mode.php';
// BookAFlight Database Connection

$host = '127.0.0.1';
$dbname = 'bookaflight';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit(
        'Database connection failed. ' .
        'Check XAMPP MySQL and db.php settings.'
    );
}

// Start secure session

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Escape HTML output

function esc($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Generate CSRF token

function csrf_token() {

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Verify CSRF token

function valid_csrf($token) {

    return is_string($token) &&
        hash_equals(csrf_token(), $token);
}

// Protect dashboard

function require_login() {

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}