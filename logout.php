<?php
require_once __DIR__ . '/db.php';

// Allow logout only through POST with valid CSRF token

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !valid_csrf($_POST['csrf_token'] ?? null)
) {

    http_response_code(403);

    exit('Invalid logout request.');
}

// Clear session data

$_SESSION = [];

// Remove session cookie

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite']
    ]);
}

// Destroy session

session_destroy();

// Redirect to login

header('Location: login.php');
exit;