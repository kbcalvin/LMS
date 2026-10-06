<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$logoutUserId = $_SESSION['user_id'] ?? null;
$logoutRole = $_SESSION['role'] ?? null;
if (!(is_string($logoutUserId) || is_int($logoutUserId)) ||
    filter_var($logoutUserId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false ||
    !in_array($logoutRole, ['user', 'librarian'], true)) {
    return;
}

$logoutCsrfToken = $_SESSION['logout_csrf_token'] ?? null;
if (!is_string($logoutCsrfToken) || !preg_match('/\A[a-f0-9]{64}\z/D', $logoutCsrfToken)) {
    $_SESSION['logout_csrf_token'] = bin2hex(random_bytes(32));
    $logoutCsrfToken = $_SESSION['logout_csrf_token'];
}
?>
<style>
    .auth-logout-form { margin: 0; }
    .auth-logout-button {
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 12px;
        border: 1px solid #dcd2c2;
        border-radius: 7px;
        color: #4b3528;
        background: #fffdf8;
        font: inherit;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: background .18s, border-color .18s;
    }
    .auth-logout-button:hover { border-color: #bca989; background: #faf6ed; }
    .auth-logout-button svg { width: 15px; height: 15px; }
    .librarian-sidebar .auth-logout-form { padding: 14px 4px 0; }
    .librarian-sidebar .auth-logout-button {
        width: 100%;
        justify-content: flex-start;
        border-color: rgba(239,225,204,.2);
        color: #e6d7c4;
        background: rgba(255,255,255,.045);
    }
    .librarian-sidebar .auth-logout-button:hover { border-color: rgba(239,225,204,.35); color: #fffaf1; background: rgba(255,255,255,.1); }
    .reader-sidebar .auth-logout-form { margin-top: 14px; }
    .reader-sidebar .auth-logout-button {
        width: 100%;
        justify-content: flex-start;
        border-color: rgba(239,225,204,.2);
        color: #e6d7c4;
        background: rgba(255,255,255,.045);
    }
    .reader-sidebar .auth-logout-button:hover { border-color: rgba(239,225,204,.35); color: #fffaf1; background: rgba(255,255,255,.1); }
    .member-page-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .member-page-actions .back-link { margin: 0; }
    @media (max-width: 480px) {
        .member-page-actions { align-items: flex-start; }
        .member-page-actions .auth-logout-button { min-height: 33px; padding: 0 9px; }
    }
</style>
<form class="auth-logout-form" method="post" action="../auth/logout.php">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($logoutCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <button class="auth-logout-button" type="submit">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
        Log out
    </button>
</form>
