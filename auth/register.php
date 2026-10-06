<?php
session_start();

$sessionUserId = $_SESSION['user_id'] ?? null;
if ((is_string($sessionUserId) || is_int($sessionUserId)) &&
    filter_var($sessionUserId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false &&
    in_array($_SESSION['role'] ?? null, ['librarian', 'user'], true)) {
    $registrationDashboard = $_SESSION['role'] === 'librarian'
        ? '../Librarian/dashboard.php'
        : '../User/dashboard.php';
    header('Location: ' . $registrationDashboard);
    exit;
}

$registerCsrfToken = $_SESSION['register_csrf_token'] ?? null;
if (!is_string($registerCsrfToken) || !preg_match('/\A[a-f0-9]{64}\z/D', $registerCsrfToken)) {
    $_SESSION['register_csrf_token'] = bin2hex(random_bytes(32));
    $registerCsrfToken = $_SESSION['register_csrf_token'];
}

$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};
$characterCount = static function (string $value): int|false {
    $count = preg_match_all('/./us', $value);
    return $count === false ? false : $count;
};

$values = [
    'name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
];
$errors = [];
$databaseError = null;
$profilePhoto = null;
$uploadedPhotoAbsolutePath = null;
$removeUploadedPhoto = static function () use (&$uploadedPhotoAbsolutePath): void {
    if ($uploadedPhotoAbsolutePath !== null && is_file($uploadedPhotoAbsolutePath) &&
        !unlink($uploadedPhotoAbsolutePath)) {
        error_log('Unable to remove profile photo after unsuccessful account registration.');
    }
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $default) {
        $posted = $_POST[$field] ?? $default;
        $values[$field] = is_string($posted) ? trim($posted) : '';
    }
    $password = $_POST['password'] ?? null;
    $passwordConfirmation = $_POST['password_confirmation'] ?? null;

    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) ||
        !hash_equals($registerCsrfToken, $_POST['csrf_token'])) {
        $errors['form'] = 'Your session could not be verified. Refresh the page and try again.';
    }

    $nameLength = $characterCount($values['name']);
    if ($nameLength === false || $nameLength < 2 || $nameLength > 100 ||
        !preg_match('/\A[\p{L}\p{M}][\p{L}\p{M} .\'’-]*\z/u', $values['name'])) {
        $errors['name'] = 'Enter a name between 2 and 100 characters using letters, spaces, apostrophes, periods, or hyphens.';
    }

    if (!preg_match('/\A[A-Za-z0-9_.-]{3,50}\z/D', $values['username'])) {
        $errors['username'] = 'Username must be 3–50 characters and use only letters, numbers, dots, underscores, or hyphens.';
    }

    if ($values['email'] === '' || strlen($values['email']) > 100 ||
        filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address no longer than 100 characters.';
    }

    if ($password === null || !is_string($password) ||
        preg_match('/\A.{8,}\z/us', $password) !== 1 || strlen($password) > 72) {
        $errors['password'] = 'Password must be at least 8 characters and no more than 72 bytes.';
    }

    if (!is_string($passwordConfirmation) || !is_string($password) ||
        !hash_equals($password, $passwordConfirmation)) {
        $errors['password_confirmation'] = 'The password confirmation does not match.';
    }

    if ($values['phone'] !== '' &&
        !preg_match('/\A\+?[0-9().\-\s]{7,20}\z/D', $values['phone'])) {
        $errors['phone'] = 'Enter a valid phone number using 7–20 digits, spaces, or common phone punctuation.';
    }

    $addressLength = $characterCount($values['address']);
    if ($addressLength === false || $addressLength > 500 ||
        preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $values['address'])) {
        $errors['address'] = 'Address must be no longer than 500 characters and cannot contain control characters.';
    }

    $photoUpload = $_FILES['photo'] ?? null;
    if ($photoUpload !== null) {
        if (!is_array($photoUpload) || !isset($photoUpload['error']) ||
            !is_int($photoUpload['error'])) {
            $errors['photo'] = 'The profile picture upload could not be verified.';
        } elseif ($photoUpload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($photoUpload['error'] !== UPLOAD_ERR_OK) {
                $errors['photo'] = $photoUpload['error'] === UPLOAD_ERR_INI_SIZE ||
                    $photoUpload['error'] === UPLOAD_ERR_FORM_SIZE
                    ? 'The profile picture must be no larger than 5 MB.'
                    : 'The profile picture could not be uploaded. Please try again.';
            } elseif (!isset($photoUpload['tmp_name'], $photoUpload['size']) ||
                !is_string($photoUpload['tmp_name']) || !is_int($photoUpload['size']) ||
                $photoUpload['size'] < 1 || $photoUpload['size'] > 5 * 1024 * 1024 ||
                !is_uploaded_file($photoUpload['tmp_name'])) {
                $errors['photo'] = 'Choose a profile picture no larger than 5 MB.';
            } else {
                $imageInfo = @getimagesize($photoUpload['tmp_name']);
                $photoTypes = [
                    IMAGETYPE_JPEG => ['mime' => 'image/jpeg', 'extension' => 'jpg'],
                    IMAGETYPE_PNG => ['mime' => 'image/png', 'extension' => 'png'],
                    IMAGETYPE_WEBP => ['mime' => 'image/webp', 'extension' => 'webp'],
                ];
                $imageType = is_array($imageInfo) ? ($imageInfo[2] ?? null) : null;
                if (!is_int($imageType) || !isset($photoTypes[$imageType]) ||
                    ($imageInfo['mime'] ?? null) !== $photoTypes[$imageType]['mime']) {
                    $errors['photo'] = 'Use a valid JPEG, PNG, or WebP image for your profile picture.';
                } elseif ($errors === []) {
                    $uploadDirectory = __DIR__ . '/../uploads/profile_pictures';
                    if (!is_dir($uploadDirectory) &&
                        !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
                        $errors['photo'] = 'The profile picture could not be saved. Please try again later.';
                    } else {
                        $photoFilename = bin2hex(random_bytes(16)) . '.' . $photoTypes[$imageType]['extension'];
                        $uploadedPhotoAbsolutePath = $uploadDirectory . '/' . $photoFilename;
                        if (!move_uploaded_file($photoUpload['tmp_name'], $uploadedPhotoAbsolutePath)) {
                            $uploadedPhotoAbsolutePath = null;
                            $errors['photo'] = 'The profile picture could not be saved. Please try again later.';
                        } else {
                            $profilePhoto = 'uploads/profile_pictures/' . $photoFilename;
                        }
                    }
                }
            }
        }
    }

    if ($errors === []) {
        try {
            require __DIR__ . '/../inc/connection.php';
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                throw new RuntimeException('Password hashing failed.');
            }

            $insert = $pdo->prepare(
                "INSERT INTO users (name, username, email, password, phone, address, role, photo)
                 VALUES (:name, :username, :email, :password, :phone, :address, 'user', :photo)"
            );
            $insert->execute([
                'name' => $values['name'],
                'username' => $values['username'],
                'email' => $values['email'],
                'password' => $passwordHash,
                'phone' => $values['phone'] !== '' ? $values['phone'] : null,
                'address' => $values['address'] !== '' ? $values['address'] : null,
                'photo' => $profilePhoto,
            ]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['role'] = 'user';
            $_SESSION['name'] = $values['name'];
            unset($_SESSION['register_csrf_token']);
            header('Location: ../User/dashboard.php');
            exit;
        } catch (PDOException $exception) {
            $removeUploadedPhoto();
            $mysqlErrorCode = isset($exception->errorInfo[1]) ? (int) $exception->errorInfo[1] : 0;
            if ($exception->getCode() === '23000' && $mysqlErrorCode === 1062) {
                $errors['form'] = 'That username or email address is already registered. Try another one.';
            } else {
                error_log('Unable to create library member account: ' . $exception->getMessage());
                $databaseError = 'Your account could not be created right now. Please try again later.';
            }
        } catch (RuntimeException $exception) {
            $removeUploadedPhoto();
            error_log('Unable to hash password for library member registration: ' . $exception->getMessage());
            $databaseError = 'Your account could not be created right now. Please try again later.';
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
    <title>Create an account | Oak &amp; Ink Library</title>
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
                radial-gradient(ellipse at 10% 8%, rgba(197,164,109,.12), transparent 31%),
                var(--paper);
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
        }
        a { color: inherit; text-decoration: none; }
        .register-page { width: min(510px, 100%); }
        .brand { display: flex; align-items: center; justify-content: center; gap: 11px; margin-bottom: 22px; }
        .brand-mark { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; color: #e9d5b0; background: var(--brown); }
        .brand-mark svg { width: 22px; height: 22px; }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 18px; }
        .brand-caption { margin-top: 3px; color: var(--muted); font-size: 9px; letter-spacing: 1.1px; text-transform: uppercase; }
        .card { padding: 27px; border: 1px solid #eee8dd; border-radius: 13px; background: var(--card); box-shadow: 0 12px 36px rgba(69,51,36,.07); }
        h1 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 29px; font-weight: 400; letter-spacing: -.5px; text-align: center; }
        .intro { margin: 8px 0 22px; color: var(--muted); font-size: 12px; text-align: center; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 13px; }
        .field { display: grid; align-content: start; gap: 6px; }
        .field.full { grid-column: 1 / -1; }
        label { color: #51483d; font-size: 10px; font-weight: 600; }
        .optional { color: #a69b8b; font-weight: 400; }
        input, textarea { width: 100%; min-height: 40px; padding: 9px 10px; border: 1px solid var(--line); border-radius: 7px; outline: none; color: var(--ink); background: #fffefa; font: inherit; font-size: 11px; }
        input[type="file"] { min-height: auto; padding: 8px; }
        textarea { min-height: 67px; resize: vertical; }
        input:focus, textarea:focus { border-color: #b69a71; box-shadow: 0 0 0 3px rgba(182,154,113,.13); }
        input::placeholder, textarea::placeholder { color: #b0a89d; }
        .hint { margin: 0; color: #968e81; font-size: 9px; line-height: 1.45; }
        .error-list, .database-error { padding: 11px 12px; margin: 0 0 15px; border: 1px solid #efd8cc; border-radius: 7px; color: var(--red); background: #f7ebe4; font-size: 10px; line-height: 1.5; }
        .error-list ul { padding-left: 17px; margin: 5px 0 0; }
        .error-list li + li { margin-top: 3px; }
        .submit { width: 100%; min-height: 42px; margin-top: 19px; border: 0; border-radius: 7px; color: #fffaf3; background: var(--brown); font: inherit; font-size: 11px; font-weight: 600; cursor: pointer; transition: background .18s, transform .18s; }
        .submit:hover { background: #624632; transform: translateY(-1px); }
        .footnote { margin: 15px 0 0; color: #8d8376; font-size: 10px; line-height: 1.5; text-align: center; }
        .footnote a { color: var(--brown-light); font-weight: 600; }
        .footnote a:hover { text-decoration: underline; }
        .security-note { margin: 15px 0 0; color: #999184; font-size: 9px; line-height: 1.5; text-align: center; }
        @media (max-width: 480px) {
            body { padding: 18px 12px; }
            .card { padding: 22px 16px; }
            h1 { font-size: 26px; }
            .form-grid { grid-template-columns: 1fr; gap: 12px; }
            .field.full { grid-column: auto; }
        }
    </style>
</head>
<body>
    <main class="register-page">
        <a class="brand" href="../User/dashboard.php" aria-label="Oak and Ink Library">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-2.5 8-1z"/><path d="M12 6.5v13"/></svg>
            </span>
            <span><span class="brand-name">Oak &amp; Ink</span><span class="brand-caption">Community Library</span></span>
        </a>

        <section class="card" aria-labelledby="registerTitle">
            <h1 id="registerTitle">Join the library</h1>
            <p class="intro">Create your member account to borrow books and keep track of your reading.</p>

            <?php if ($errors !== []): ?>
                <div class="error-list" role="alert">
                    <strong>Please correct the following:</strong>
                    <ul>
                        <?php foreach ($errors as $message): ?>
                            <li><?php echo $escape($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($databaseError !== null): ?>
                <p class="database-error" role="alert"><?php echo $escape($databaseError); ?></p>
            <?php endif; ?>

            <form method="post" action="register.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $escape($registerCsrfToken); ?>">
                <div class="form-grid">
                    <div class="field full">
                        <label for="name">Full name</label>
                        <input id="name" name="name" type="text" autocomplete="name" maxlength="100" value="<?php echo $escape($values['name']); ?>" placeholder="Your full name" required>
                    </div>
                    <div class="field">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" autocomplete="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\x2d]{3,50}" value="<?php echo $escape($values['username']); ?>" placeholder="Choose a username" required>
                        <p class="hint">3–50 letters, numbers, dots, underscores, or hyphens.</p>
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" maxlength="100" value="<?php echo $escape($values['email']); ?>" placeholder="you@example.com" required>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="passwordHint" required>
                        <p class="hint" id="passwordHint">Use at least 8 characters (maximum 72 bytes).</p>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                    </div>
                    <div class="field">
                        <label for="phone">Phone <span class="optional">(optional)</span></label>
                        <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="20" value="<?php echo $escape($values['phone']); ?>" placeholder="Your phone number">
                    </div>
                    <div class="field full">
                        <label for="address">Address <span class="optional">(optional)</span></label>
                        <textarea id="address" name="address" autocomplete="street-address" maxlength="500" placeholder="Your address"><?php echo $escape($values['address']); ?></textarea>
                    </div>
                    <div class="field full">
                        <label for="photo">Profile picture <span class="optional">(optional)</span></label>
                        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="photoHint">
                        <p class="hint" id="photoHint">JPEG, PNG, or WebP; maximum file size 5 MB.</p>
                    </div>
                </div>
                <button class="submit" type="submit">Create member account</button>
            </form>
            <p class="footnote">Already have an account? <a href="login.php">Log in</a></p>
            <p class="footnote">Creating an account registers you as a library member. Librarian privileges are assigned separately.</p>
            <p class="security-note">Your password is securely hashed and never stored as plain text.</p>
        </section>
    </main>
</body>
</html>