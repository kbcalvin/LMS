<?php
session_start();

$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};
$sessionUserId = $_SESSION['user_id'] ?? null;
$userId = is_string($sessionUserId) || is_int($sessionUserId)
    ? filter_var($sessionUserId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
$isUser = $userId !== false && $userId !== null;
if ($isUser && isset($_SESSION['role']) && $_SESSION['role'] !== 'user') {
    $isUser = false;
}

if (!isset($_SESSION['borrow_csrf_token'])) {
    $_SESSION['borrow_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['borrow_csrf_token'];
$message = $_SESSION['borrow_message'] ?? null;
unset($_SESSION['borrow_message']);
$error = null;
$books = [];
$databaseError = null;
$pdo = null;
try {
    require __DIR__ . '/../inc/connection.php';
} catch (PDOException $exception) {
    error_log('Unable to connect to the library database: ' . $exception->getMessage());
    $databaseError = 'Book availability is temporarily unavailable. Please try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isUser) {
        $error = 'Sign in with a library member account before borrowing a book.';
    } elseif (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        $error = 'Your session could not be verified. Refresh the page and try again.';
    } elseif ($pdo === null) {
        $error = $databaseError ?? 'The library database is unavailable. Please try again later.';
    } else {
        $submittedBookId = $_POST['book_id'] ?? null;
        $bookId = is_string($submittedBookId)
            ? filter_var($submittedBookId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;

        if ($bookId === false || $bookId === null) {
            $error = 'Choose a valid book to borrow.';
        } else {
            try {
                $pdo->beginTransaction();

                $memberQuery = $pdo->prepare(
                    "SELECT is_penalized FROM users WHERE id = :user_id AND role = 'user' FOR UPDATE"
                );
                $memberQuery->execute(['user_id' => $userId]);
                $member = $memberQuery->fetch();

                if (!$member) {
                    $pdo->rollBack();
                    $error = 'Your library member account could not be verified. Please contact a librarian.';
                } elseif ((int) $member['is_penalized'] === 1) {
                    $pdo->rollBack();
                    $error = 'Borrowing is unavailable for this account. Please speak with a librarian.';
                } else {
                    $bookQuery = $pdo->prepare(
                        'SELECT id, name, available FROM textbooks WHERE id = :book_id FOR UPDATE'
                    );
                    $bookQuery->execute(['book_id' => $bookId]);
                    $book = $bookQuery->fetch();

                    if (!$book) {
                        $pdo->rollBack();
                        $error = 'That book is no longer in the library catalog.';
                    } elseif ((int) $book['available'] < 1) {
                        $pdo->rollBack();
                        $error = 'There are no available copies of that book right now.';
                    } else {
                        $pendingQuery = $pdo->prepare(
                            "SELECT id FROM borrowings
                             WHERE user_id = :user_id AND book_id = :book_id AND status = 'pending'
                             LIMIT 1"
                        );
                        $pendingQuery->execute([
                            'user_id' => $userId,
                            'book_id' => $bookId,
                        ]);
                        if ($pendingQuery->fetch()) {
                            $pdo->rollBack();
                            $error = 'You already have a pending request for that book.';
                        } else {
                            $createBorrowing = $pdo->prepare(
                                "INSERT INTO borrowings (user_id, book_id, status, due_date)
                                 VALUES (:user_id, :book_id, 'pending', NULL)"
                            );
                            $createBorrowing->execute([
                                'user_id' => $userId,
                                'book_id' => $bookId,
                            ]);
                            $pdo->commit();
                            $_SESSION['borrow_message'] = 'Your request for "' . $book['name'] . '" has been sent to the librarian for approval.';
                            header('Location: borrow_book.php');
                            exit;
                        }
                    }
                }
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Unable to record a book borrowing: ' . $exception->getMessage());
                $error = 'The borrowing could not be recorded. Please try again or ask a librarian for help.';
            }
        }
    }
}

if ($pdo !== null) {
    try {
        $bookQuery = $pdo->query(
            'SELECT id, name, author, publisher, quantity, available
             FROM textbooks
             ORDER BY name ASC'
        );
        $books = $bookQuery->fetchAll();
    } catch (PDOException $exception) {
        error_log('Unable to load books for borrowing: ' . $exception->getMessage());
        $databaseError = 'The book list could not be loaded. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <title>Borrow a book | Oak &amp; Ink Library</title>
    <style>
        :root {
            --paper: #f6f2e9;
            --card: #fffdf8;
            --ink: #29251f;
            --muted: #8a8377;
            --line: #ebe5d9;
            --brown: #4b3528;
            --brown-light: #6f503b;
            --green: #64734f;
            --red: #9f4f3d;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            padding: 36px 20px;
            color: var(--ink);
            background: var(--paper);
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
        }
        a { color: inherit; text-decoration: none; }
        .page { width: min(1060px, 100%); margin: 0 auto; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 27px; }
        .brand-mark { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; color: #e9d5b0; background: var(--brown); }
        .brand-mark svg { width: 21px; height: 21px; }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 17px; }
        .brand-caption { margin-top: 3px; color: var(--muted); font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }
        .back-link { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 15px; color: var(--brown-light); font-size: 11px; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
        h1 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: clamp(27px, 3vw, 34px); font-weight: 400; letter-spacing: -.5px; }
        .intro { margin: 8px 0 20px; color: var(--muted); font-size: 12px; }
        .notice { padding: 12px 14px; margin: 0 0 14px; border: 1px solid #dfe5d4; border-radius: 8px; color: #536343; background: #edf0e7; font-size: 11px; line-height: 1.55; }
        .notice.error { border-color: #efd8cc; color: var(--red); background: #f7ebe4; }
        .notice.info { border-color: #e7dfd0; color: #756b5e; background: #f5f0e6; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 13px; }
        .search { position: relative; width: min(340px, 100%); }
        .search svg { position: absolute; top: 10px; left: 11px; width: 16px; height: 16px; color: #92897d; }
        .search input { width: 100%; height: 37px; padding: 0 11px 0 35px; border: 1px solid var(--line); border-radius: 7px; outline: none; color: var(--ink); background: var(--card); font-size: 11px; }
        .search input:focus { border-color: #b69a71; box-shadow: 0 0 0 3px rgba(182,154,113,.13); }
        .count { color: #8e867b; font-size: 10px; }
        .book-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 13px; }
        .book-card { display: flex; align-items: flex-start; gap: 13px; padding: 16px; border: 1px solid #eee8dd; border-radius: 11px; background: var(--card); box-shadow: 0 5px 20px rgba(69,51,36,.035); }
        .book-card.selected { border-color: #b69a71; box-shadow: 0 0 0 3px rgba(182,154,113,.13); }
        .book-cover { width: 47px; height: 65px; display: grid; place-items: center; flex: 0 0 47px; border-radius: 4px 6px 6px 4px; color: #f4e8d2; background: #795b42; box-shadow: inset 4px 0 rgba(0,0,0,.12); font-family: Georgia, serif; font-size: 17px; }
        .book-card:nth-child(2) .book-cover { color: #f8eddf; background: #778064; }
        .book-card:nth-child(3) .book-cover { color: #f7ebdc; background: #a76d51; }
        .book-card:nth-child(4) .book-cover { color: #f4eadb; background: #867151; }
        .book-details { min-width: 0; flex: 1; }
        .book-details h2 { margin: 1px 0 4px; color: #403930; font-family: Georgia, "Times New Roman", serif; font-size: 16px; font-weight: 400; line-height: 1.35; }
        .author { margin: 0; color: #91897e; font-size: 10px; }
        .publisher { margin: 5px 0 0; color: #a0988d; font-size: 9px; }
        .card-bottom { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 13px; }
        .availability { display: inline-flex; align-items: center; gap: 6px; color: var(--green); font-size: 9px; }
        .availability::before { width: 6px; height: 6px; border-radius: 50%; background: #87956c; content: ""; }
        .availability.unavailable { color: var(--red); }
        .availability.unavailable::before { background: #b66b4e; }
        .borrow-button { min-height: 33px; padding: 0 10px; border: 1px solid var(--brown); border-radius: 7px; color: #fffaf3; background: var(--brown); font-size: 10px; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .borrow-button:hover { background: #624632; }
        .borrow-button:disabled { border-color: #d8d0c4; color: #968e81; background: #f2eee7; cursor: not-allowed; }
        .empty { display: none; padding: 30px 15px; border: 1px solid #eee8dd; border-radius: 10px; color: var(--muted); background: var(--card); font-size: 11px; text-align: center; }
        .fine-print { margin-top: 16px; color: #968e81; font-size: 10px; line-height: 1.55; }
        @media (max-width: 700px) {
            body { padding: 25px 14px; }
            .book-list { grid-template-columns: 1fr; }
        }
        @media (max-width: 430px) {
            .book-card { gap: 10px; padding: 13px 11px; }
            .book-cover { width: 40px; height: 56px; flex-basis: 40px; }
            .book-details h2 { font-size: 14px; }
            .card-bottom { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
    <main class="page">
        <a class="brand" href="dashboard.php" aria-label="Oak and Ink reader dashboard">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-1.2 8-1z"/><path d="M12 6.5v13"/></svg>
            </span>
            <span><span class="brand-name">Oak &amp; Ink</span><span class="brand-caption">Community Library</span></span>
        </a>

        <div class="member-page-actions">
            <a class="back-link" href="dashboard.php"><span aria-hidden="true">←</span> Back to reader dashboard</a>
            <?php require __DIR__ . '/../inc/logout_button.php'; ?>
        </div>
        <h1>Borrow a book</h1>
        <p class="intro">Request an available title. A librarian will approve your request before the book is checked out.</p>

        <?php if (is_string($message) && $message !== ''): ?>
            <p class="notice" role="status"><?php echo $escape($message); ?></p>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <p class="notice error" role="alert"><?php echo $escape($error); ?></p>
        <?php endif; ?>
        <?php if (!$isUser): ?>
            <p class="notice info" role="status">Sign in with a library member account before borrowing. Your member account is verified when you submit a request.</p>
        <?php endif; ?>
        <?php if ($databaseError !== null): ?>
            <p class="notice error" role="alert"><?php echo $escape($databaseError); ?></p>
        <?php endif; ?>

        <div class="toolbar">
            <label class="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                <input id="bookSearch" type="search" placeholder="Search by title, author, or publisher..." aria-label="Search books available for borrowing">
            </label>
            <span class="count" id="bookCount"><?php echo count($books); ?> titles</span>
        </div>

        <section class="book-list" id="bookList" aria-label="Library books">
            <?php foreach ($books as $index => $book): ?>
                <?php
                $available = (int) $book['available'];
                $bookName = (string) $book['name'];
                $author = (string) $book['author'];
                $publisher = (string) ($book['publisher'] ?? '');
                $searchText = strtolower($bookName . ' ' . $author . ' ' . $publisher);
                $coverLetter = strtoupper(substr($bookName, 0, 1));
                $isSelectedBook = isset($_GET['book_id']) && is_string($_GET['book_id']) && $_GET['book_id'] === (string) $book['id'];
                ?>
                <article class="book-card<?php echo $isSelectedBook ? ' selected' : ''; ?>" id="book-<?php echo (int) $book['id']; ?>" data-search="<?php echo $escape($searchText); ?>">
                    <span class="book-cover" aria-hidden="true"><?php echo $escape($coverLetter); ?></span>
                    <div class="book-details">
                        <h2><?php echo $escape($bookName); ?></h2>
                        <p class="author">by <?php echo $escape($author); ?></p>
                        <?php if ($publisher !== ''): ?>
                            <p class="publisher"><?php echo $escape($publisher); ?></p>
                        <?php endif; ?>
                        <div class="card-bottom">
                            <span class="availability<?php echo $available < 1 ? ' unavailable' : ''; ?>">
                                <?php if ($available > 0): ?>
                                    <?php echo $available; ?> of <?php echo (int) $book['quantity']; ?> available
                                <?php else: ?>
                                    No copies available
                                <?php endif; ?>
                            </span>
                            <?php if ($isUser && $available > 0 && $pdo !== null): ?>
                                <form method="post" action="borrow_book.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                    <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                    <button class="borrow-button" type="submit">Request to borrow</button>
                                </form>
                            <?php elseif (!$isUser && $available > 0 && $pdo !== null): ?>
                                <button class="borrow-button" type="button" disabled>Sign in to borrow</button>
                            <?php else: ?>
                                <button class="borrow-button" type="button" disabled><?php echo $available > 0 ? 'Currently unavailable' : 'Out of stock'; ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
        <div class="empty" id="emptyState"><?php echo $books === [] && $databaseError === null ? 'There are no books in the library catalog yet.' : 'No books match your search.'; ?></div>
        <p class="fine-print">A librarian reviews each request. Approved checkouts reduce the available quantity and are due 7 days after approval.</p>
    </main>
    <script>
        const search = document.getElementById('bookSearch');
        const cards = Array.from(document.querySelectorAll('.book-card'));
        const count = document.getElementById('bookCount');
        const empty = document.getElementById('emptyState');
        const filterBooks = () => {
            const query = search.value.trim().toLowerCase();
            let visible = 0;
            cards.forEach((card) => {
                card.hidden = !card.dataset.search.includes(query);
                if (!card.hidden) visible += 1;
            });
            count.textContent = `${visible} ${visible === 1 ? 'title' : 'titles'}`;
            empty.style.display = visible === 0 ? 'block' : 'none';
        };
        search.addEventListener('input', filterBooks);
        filterBooks();
    </script>
</body>
</html>