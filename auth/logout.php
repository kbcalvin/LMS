<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use the logout form to sign out.');
}

$csrfToken = $_SESSION['logout_csrf_token'] ?? null;
$submittedToken = $_POST['csrf_token'] ?? null;
if (!is_string($csrfToken) || !is_string($submittedToken) ||
    !hash_equals($csrfToken, $submittedToken)) {
    http_response_code(403);
    exit('Your session could not be verified. Refresh the page and try again.');
}

$_SESSION = [];
$cookie = session_get_cookie_params();
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

header('Location: login.php?logged_out=1');
exit;