<?php
session_start();

$sessionUserId = $_SESSION['user_id'] ?? null;
$sessionRole = $_SESSION['role'] ?? null;
if ((is_string($sessionUserId) || is_int($sessionUserId)) &&
    filter_var($sessionUserId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false &&
    in_array($sessionRole, ['user', 'librarian'], true)) {
    $dashboard = $sessionRole === 'librarian'
        ? '../Librarian/dashboard.php'
        : '../User/dashboard.php';
    header('Location: ' . $dashboard);
    exit;
}

$csrfToken = $_SESSION['login_csrf_token'] ?? null;
if (!is_string($csrfToken) || !preg_match('/\A[a-f0-9]{64}\z/D', $csrfToken)) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
    $csrfToken = $_SESSION['login_csrf_token'];
}

$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};
$username = '';
$error = null;
$databaseError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedUsername = $_POST['username'] ?? null;
    $username = is_string($submittedUsername) ? trim($submittedUsername) : '';
    $password = $_POST['password'] ?? null;

    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) ||
        !hash_equals($csrfToken, $_POST['csrf_token'])) {
        $error = 'Your session could not be verified. Refresh the page and try again.';
    } elseif (!preg_match('/\A[A-Za-z0-9_.-]{3,50}\z/D', $username) ||
        !is_string($password) || $password === '' || strlen($password) > 72) {
        $error = 'Invalid username or password.';
    } else {
        try {
            require __DIR__ . '/../inc/connection.php';
            $query = $pdo->prepare(
                'SELECT id, name, username, password, role
                 FROM users
                 WHERE username = :username
                 LIMIT 1'
            );
            $query->execute(['username' => $username]);
            $account = $query->fetch();

            if (!$account || !is_string($account['password']) ||
                !password_verify($password, $account['password']) ||
                !in_array($account['role'], ['user', 'librarian'], true)) {
                $error = 'Invalid username or password.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $account['id'];
                $_SESSION['role'] = $account['role'];
                $_SESSION['name'] = $account['name'];
                unset($_SESSION['login_csrf_token']);
                $dashboard = $account['role'] === 'librarian'
                    ? '../Librarian/dashboard.php'
                    : '../User/dashboard.php';
                header('Location: ' . $dashboard);
                exit;
            }
        } catch (PDOException $exception) {
            error_log('Unable to authenticate library account: ' . $exception->getMessage());
            $databaseError = 'Sign-in is temporarily unavailable. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <title>Log in | Oak &amp; Ink Library</title>
    <style>
        :root {
            --paper: #f6f2e9;
            --card: #fffdf8;
            --ink: #29251f;
            --muted: #8a8377;
            --line: #ebe5d9;
            --brown: #4b3528;
            --brown-light: #6f503b;
            --red: #9f4f3d;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            margin: 0;
            padding: 28px 16px;
            color: var(--ink);
            background:
                radial-gradient(ellipse at 90% 8%, rgba(197,164,109,.12), transparent 31%),
                var(--paper);
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
        }
        a { color: inherit; text-decoration: none; }
        .login-page { width: min(420px, 100%); }
        .brand { display: flex; align-items: center; justify-content: center; gap: 11px; margin-bottom: 22px; }
        .brand-mark { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; color: #e9d5b0; background: var(--brown); }
        .brand-mark svg { width: 22px; height: 22px; }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 18px; }
        .brand-caption { margin-top: 3px; color: var(--muted); font-size: 9px; letter-spacing: 1.1px; text-transform: uppercase; }
        .card { padding: 29px; border: 1px solid #eee8dd; border-radius: 13px; background: var(--card); box-shadow: 0 12px 36px rgba(69,51,36,.07); }
        h1 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 29px; font-weight: 400; letter-spacing: -.5px; text-align: center; }
        .intro { margin: 8px 0 22px; color: var(--muted); font-size: 12px; line-height: 1.5; text-align: center; }
        .field { display: grid; gap: 7px; margin-top: 15px; }
        label { color: #51483d; font-size: 10px; font-weight: 600; }
        input { width: 100%; min-height: 42px; padding: 10px 11px; border: 1px solid var(--line); border-radius: 7px; outline: none; color: var(--ink); background: #fffefa; font: inherit; font-size: 12px; }
        input:focus { border-color: #b69a71; box-shadow: 0 0 0 3px rgba(182,154,113,.13); }
        .notice { padding: 11px 12px; margin: 0 0 14px; border: 1px solid #efd8cc; border-radius: 7px; color: var(--red); background: #f7ebe4; font-size: 10px; line-height: 1.5; }
        .notice.success { border-color: #dfe5d4; color: #536343; background: #edf0e7; }
        .submit { width: 100%; min-height: 42px; margin-top: 21px; border: 0; border-radius: 7px; color: #fffaf3; background: var(--brown); font: inherit; font-size: 11px; font-weight: 600; cursor: pointer; transition: background .18s, transform .18s; }
        .submit:hover { background: #624632; transform: translateY(-1px); }
        .footnote { margin: 17px 0 0; color: #8d8376; font-size: 10px; line-height: 1.5; text-align: center; }
        .footnote a { color: var(--brown-light); font-weight: 600; }
        .footnote a:hover { text-decoration: underline; }
        .role-note { margin: 15px 0 0; color: #999184; font-size: 9px; line-height: 1.5; text-align: center; }
        @media (max-width: 460px) {
            body { padding: 18px 12px; }
            .card { padding: 24px 18px; }
            h1 { font-size: 26px; }
        }
    </style>
</head>
<body>
    <main class="login-page">
        <a class="brand" href="../User/dashboard.php" aria-label="Oak and Ink Library">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-2.5 8-1z"/><path d="M12 6.5v13"/></svg>
            </span>
            <span><span class="brand-name">Oak &amp; Ink</span><span class="brand-caption">Community Library</span></span>
        </a>

        <section class="card" aria-labelledby="loginTitle">
            <h1 id="loginTitle">Welcome back</h1>
            <p class="intro">Log in with the username and password linked to your library account.</p>

            <?php if ($error !== null): ?>
                <p class="notice" role="alert"><?php echo $escape($error); ?></p>
            <?php endif; ?>
            <?php if ($databaseError !== null): ?>
                <p class="notice" role="alert"><?php echo $escape($databaseError); ?></p>
            <?php endif; ?>
            <?php if (isset($_GET['logged_out']) && $_GET['logged_out'] === '1'): ?>
                <p class="notice success" role="status">You have been logged out successfully.</p>
            <?php endif; ?>

            <form method="post" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\x2d]{3,50}" value="<?php echo $escape($username); ?>" placeholder="Enter your username" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" maxlength="72" placeholder="Enter your password" required>
                </div>
                <button class="submit" type="submit">Log in</button>
            </form>
            <p class="footnote">New to the library? <a href="register.php">Create a member account</a></p>
            <p class="role-note">Members and librarians use the same sign-in form and will be sent to the appropriate dashboard.</p>
        </section>
    </main>
</body>
</html>