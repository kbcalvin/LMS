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
if (!isset($_SESSION['return_csrf_token'])) {
    $_SESSION['return_csrf_token'] = bin2hex(random_bytes(32));
}
$returnCsrfToken = $_SESSION['return_csrf_token'];
$returnFeedback = $_SESSION['return_feedback'] ?? null;
unset($_SESSION['return_feedback']);

$borrowings = [];
$error = null;
$pdo = null;

if ($isUser) {
    try {
        require __DIR__ . '/../inc/connection.php';
        $query = $pdo->prepare(
            'SELECT br.id, b.id AS book_id, b.name, b.author, b.publisher,
                    br.borrow_date, br.due_date, br.date_returned, br.status
             FROM borrowings AS br
             INNER JOIN textbooks AS b ON b.id = br.book_id
             WHERE br.user_id = :user_id
             ORDER BY br.borrow_date DESC, br.id DESC'
        );
        $query->execute(['user_id' => $userId]);
        $borrowings = $query->fetchAll();
    } catch (PDOException $exception) {
        error_log('Unable to load member borrowing history: ' . $exception->getMessage());
        $error = 'Your borrowing history could not be loaded. Please try again later.';
    }
}

$today = new DateTimeImmutable('today');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <title>My borrowed books | Oak &amp; Ink Library</title>
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
            --red: #a45d43;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            padding: 32px 20px;
            color: var(--ink);
            background: var(--paper);
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
        }
        a { color: inherit; text-decoration: none; }
        .page { width: min(1000px, 100%); margin: 0 auto; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
        .brand-mark { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; color: #e9d5b0; background: var(--brown); }
        .brand-mark svg { width: 21px; height: 21px; }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 17px; }
        .brand-caption { margin-top: 3px; color: var(--muted); font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }
        .back-link { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 14px; color: var(--brown-light); font-size: 11px; font-weight: 600; }
        .back-link:hover, .text-link:hover { text-decoration: underline; }
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 15px; margin-bottom: 19px; }
        .eyebrow { margin: 0 0 8px; color: #978b79; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: clamp(27px, 3vw, 34px); font-weight: 400; letter-spacing: -.5px; }
        .intro { margin: 8px 0 0; color: var(--muted); font-size: 12px; }
        .button { min-height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 0 13px; border: 1px solid var(--brown); border-radius: 7px; color: #fffaf3; background: var(--brown); font-size: 10px; font-weight: 600; white-space: nowrap; }
        .button:hover { background: #624632; }
        .button svg { width: 15px; height: 15px; }
        .summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
        .summary-card { padding: 14px 16px; border: 1px solid #eee8dd; border-radius: 10px; background: var(--card); box-shadow: 0 5px 20px rgba(69,51,36,.035); }
        .summary-label { color: #81796d; font-size: 10px; }
        .summary-value { margin-top: 7px; color: var(--brown); font-family: Georgia, "Times New Roman", serif; font-size: 23px; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 15px; border: 1px solid #eee8dd; border-bottom: 0; border-radius: 10px 10px 0 0; background: var(--card); }
        .search { position: relative; width: min(310px, 100%); }
        .search svg { position: absolute; top: 10px; left: 10px; width: 16px; height: 16px; color: #92897d; }
        .search input { width: 100%; height: 36px; padding: 0 10px 0 33px; border: 1px solid var(--line); border-radius: 7px; outline: none; color: var(--ink); background: #fffefa; font: inherit; font-size: 11px; }
        .search input:focus { border-color: #b69a71; box-shadow: 0 0 0 3px rgba(182,154,113,.13); }
        .count { color: #8e867b; font-size: 10px; white-space: nowrap; }
        .table-card { overflow: hidden; border: 1px solid #eee8dd; border-radius: 0 0 10px 10px; background: var(--card); box-shadow: 0 8px 28px rgba(69,51,36,.04); }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; min-width: 700px; border-collapse: collapse; text-align: left; }
        th { padding: 12px 15px; border-bottom: 1px solid var(--line); color: #968e81; font-size: 9px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
        td { padding: 13px 15px; border-bottom: 1px solid #f0ece5; color: #625b51; font-size: 10px; }
        tbody tr:last-child td { border-bottom: 0; }
        .book-title { color: #403930; font-size: 11px; font-weight: 600; }
        .book-meta { margin-top: 4px; color: #9a9286; font-size: 9px; }
        .status { display: inline-flex; align-items: center; gap: 5px; padding: 5px 8px; border-radius: 20px; color: var(--green); background: #edf0e7; font-size: 9px; white-space: nowrap; }
        .status::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ""; }
        .status.overdue { color: var(--red); background: #f7ebe4; }
        .status.returned { color: #766d62; background: #f0ece5; }
        .status.due-soon { color: #8d7145; background: #f5efdf; }
        .notice { padding: 12px 14px; margin-bottom: 14px; border: 1px solid #efd8cc; border-radius: 8px; color: var(--red); background: #f7ebe4; font-size: 11px; line-height: 1.55; }
        .notice.success { border-color: #dfe5d4; color: #536343; background: #edf0e7; }
        .return-form { margin: 0; }
        .return-button { min-height: 30px; padding: 0 9px; border: 1px solid #d8c9b7; border-radius: 6px; color: #79593e; background: #fffdf8; font: inherit; font-size: 9px; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .return-button:hover { border-color: #b69a71; background: #faf6ed; }
        .returned-label { color: #8b8378; font-size: 9px; white-space: nowrap; }
        .empty { display: none; padding: 34px 18px; color: var(--muted); text-align: center; }
        .empty strong { display: block; margin-bottom: 6px; color: #51483d; font-family: Georgia, "Times New Roman", serif; font-size: 16px; font-weight: 400; }
        .empty p { margin: 0 0 14px; font-size: 10px; line-height: 1.5; }
        .text-link { color: var(--brown-light); font-size: 10px; font-weight: 600; }
        .footer { margin-top: 19px; color: #a39b90; font-size: 9px; text-align: center; }
        @media (max-width: 620px) {
            body { padding: 23px 13px; }
            .brand { margin-bottom: 23px; }
            .page-heading { align-items: flex-start; flex-direction: column; }
            .summary { gap: 7px; }
            .summary-card { padding: 12px 10px; }
            .summary-label { font-size: 9px; line-height: 1.35; }
            .summary-value { font-size: 20px; }
            .toolbar { align-items: stretch; flex-direction: column; }
            .search { width: 100%; }
            .count { align-self: flex-end; }
        }
    </style>
</head>
<body>
    <main class="page">
        <a class="brand" href="dashboard.php" aria-label="Oak and Ink reader dashboard">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-2.5 8-1z"/><path d="M12 6.5v13"/></svg>
            </span>
            <span><span class="brand-name">Oak &amp; Ink</span><span class="brand-caption">Community Library</span></span>
        </a>
        <div class="member-page-actions">
            <a class="back-link" href="dashboard.php"><span aria-hidden="true">←</span> Back to reader dashboard</a>
            <?php require __DIR__ . '/../inc/logout_button.php'; ?>
        </div>

        <header class="page-heading">
            <div>
                <p class="eyebrow">Your reading shelf</p>
                <h1>My borrowed books</h1>
                <p class="intro">See current checkouts, due dates, and your returned-book history.</p>
            </div>
            <a class="button" href="borrow_book.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5h14v16l-7-4-7 4z"/><path d="M12 7v6M9 10h6"/></svg>
                Borrow another book
            </a>
        </header>

        <?php if (is_array($returnFeedback) && isset($returnFeedback['message'], $returnFeedback['type'])): ?>
            <p class="notice<?php echo $returnFeedback['type'] === 'success' ? ' success' : ''; ?>" role="<?php echo $returnFeedback['type'] === 'success' ? 'status' : 'alert'; ?>"><?php echo $escape((string) $returnFeedback['message']); ?></p>
        <?php endif; ?>
        <?php if (!$isUser): ?>
            <p class="notice" role="alert">Sign in with a library member account to view your borrowing history.</p>
        <?php elseif ($error !== null): ?>
            <p class="notice" role="alert"><?php echo $escape($error); ?></p>
        <?php else: ?>
            <?php
            $activeCount = 0;
            $overdueCount = 0;
            $pendingCount = 0;
            foreach ($borrowings as $borrowing) {
                if ($borrowing['status'] === 'pending') {
                    $pendingCount++;
                } elseif ($borrowing['status'] === 'approved' && $borrowing['date_returned'] === null) {
                    $activeCount++;
                    if ($borrowing['due_date'] !== null && new DateTimeImmutable($borrowing['due_date']) < $today) {
                        $overdueCount++;
                    }
                }
            }
            ?>
            <section class="summary" aria-label="Borrowing summary">
                <article class="summary-card"><div class="summary-label">Requests &amp; checkouts</div><div class="summary-value"><?php echo count($borrowings); ?></div></article>
                <article class="summary-card"><div class="summary-label">Currently borrowed</div><div class="summary-value"><?php echo $activeCount; ?></div></article>
                <article class="summary-card"><div class="summary-label">Pending requests</div><div class="summary-value"><?php echo $pendingCount; ?></div></article>
                <article class="summary-card"><div class="summary-label">Overdue</div><div class="summary-value"><?php echo $overdueCount; ?></div></article>
            </section>
            <div class="toolbar">
                <label class="search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                    <input id="borrowingSearch" type="search" placeholder="Search requests and checkouts..." aria-label="Search borrowing history">
                </label>
                <span class="count" id="borrowingCount"><?php echo count($borrowings); ?> records</span>
            </div>
            <section class="table-card" aria-label="Borrowing history">
                <?php if ($borrowings === []): ?>
                    <div class="empty" style="display: block">
                        <strong>No requests or checkouts yet</strong>
                        <p>Your requests and checkouts will appear here with their current status.</p>
                        <a class="text-link" href="borrow_book.php">Browse available books →</a>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table id="borrowingsTable">
                            <thead><tr><th>Book</th><th>Requested / borrowed</th><th>Due date</th><th>Returned</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($borrowings as $borrowing): ?>
                                    <?php
                                    $dueDate = $borrowing['due_date'] !== null
                                        ? new DateTimeImmutable($borrowing['due_date'])
                                        : null;
                                    $returnedDate = $borrowing['date_returned'] !== null
                                        ? new DateTimeImmutable($borrowing['date_returned'])
                                        : null;
                                    if ($borrowing['status'] === 'pending') {
                                        $status = 'Awaiting approval';
                                        $statusClass = 'due-soon';
                                    } elseif ($borrowing['status'] === 'rejected') {
                                        $status = 'Declined';
                                        $statusClass = 'returned';
                                    } elseif ($returnedDate !== null) {
                                        $status = 'Returned';
                                        $statusClass = 'returned';
                                    } elseif ($dueDate !== null && $dueDate < $today) {
                                        $status = 'Overdue';
                                        $statusClass = 'overdue';
                                    } elseif ($dueDate !== null && $dueDate <= $today->modify('+2 days')) {
                                        $status = 'Due soon';
                                        $statusClass = 'due-soon';
                                    } else {
                                        $status = 'Borrowed';
                                        $statusClass = '';
                                    }
                                    ?>
                                    <tr>
                                        <td><div class="book-title"><?php echo $escape((string) $borrowing['name']); ?></div><div class="book-meta"><?php echo $escape((string) $borrowing['author']); ?><?php if (!empty($borrowing['publisher'])): ?> · <?php echo $escape((string) $borrowing['publisher']); ?><?php endif; ?></div></td>
                                        <td><?php echo $borrowing['status'] === 'rejected' ? '—' : $escape((new DateTimeImmutable($borrowing['borrow_date']))->format('M j, Y')); ?></td>
                                        <td><?php echo $dueDate ? $escape($dueDate->format('M j, Y')) : 'Not set'; ?></td>
                                        <td><?php echo $returnedDate ? $escape($returnedDate->format('M j, Y')) : '—'; ?></td>
                                        <td><span class="status<?php echo $statusClass !== '' ? ' ' . $statusClass : ''; ?>"><?php echo $status; ?></span></td>
                                        <td>
                                            <?php if ($borrowing['status'] === 'approved' && $returnedDate === null): ?>
                                                <form class="return-form" method="post" action="return_book.php" onsubmit="return confirm('Confirm that you are returning <?php echo $escape((string) $borrowing['name']); ?> to the library?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $escape($returnCsrfToken); ?>">
                                                    <input type="hidden" name="borrowing_id" value="<?php echo (int) $borrowing['id']; ?>">
                                                    <button class="return-button" type="submit">Return book</button>
                                                </form>
                                            <?php elseif ($borrowing['status'] === 'pending'): ?>
                                                <span class="returned-label">Awaiting approval</span>
                                            <?php elseif ($borrowing['status'] === 'rejected'): ?>
                                                <span class="returned-label">No action</span>
                                            <?php else: ?>
                                                <span class="returned-label">Returned</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="empty" id="emptyState">No borrowing records match your search.</div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        <p class="footer">Oak &amp; Ink Community Library &nbsp;·&nbsp; Happy reading</p>
    </main>
    <script>
        const search = document.getElementById('borrowingSearch');
        const rows = Array.from(document.querySelectorAll('#borrowingsTable tbody tr'));
        const count = document.getElementById('borrowingCount');
        const empty = document.getElementById('emptyState');
        if (search && count && empty) {
            search.addEventListener('input', () => {
                const query = search.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach((row) => {
                    row.hidden = !row.textContent.toLowerCase().includes(query);
                    if (!row.hidden) visible++;
                });
                count.textContent = `${visible} ${visible === 1 ? 'record' : 'records'}`;
                empty.style.display = visible === 0 ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>