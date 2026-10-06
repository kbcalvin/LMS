<?php
function require_library_role(array $allowedRoles, string $loginPath): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $userId = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;
    $authenticated = (is_string($userId) || is_int($userId)) &&
        filter_var($userId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false &&
        in_array($role, ['librarian', 'user'], true);

    if (!$authenticated) {
        header('Location: ' . $loginPath);
        exit;
    }

    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}
