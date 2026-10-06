<?php
session_start();
require __DIR__ . '/../inc/authorization.php';
require_library_role(['librarian'], '../auth/login.php');

$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};
$csrfToken = $_SESSION['borrowing_review_csrf'] ?? null;
if (!is_string($csrfToken) || !preg_match('/\A[a-f0-9]{64}\z/D', $csrfToken)) {
    $_SESSION['borrowing_review_csrf'] = bin2hex(random_bytes(32));
    $csrfToken = $_SESSION['borrowing_review_csrf'];
}
$feedback = $_SESSION['borrowing_review_feedback'] ?? null;
unset($_SESSION['borrowing_review_feedback']);
$borrowings = [];
$databaseError = null;
$pendingCount = 0;

try {
    require __DIR__ . '/../inc/connection.php';
    $pendingCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM borrowings WHERE status = 'pending'"
    )->fetchColumn();
    $borrowings = $pdo->query(
        'SELECT br.id, br.status, br.borrow_date, br.due_date, br.date_returned,
                b.name AS book_name, b.author, b.available,
                u.name AS member_name, u.username
         FROM borrowings AS br
         INNER JOIN textbooks AS b ON b.id = br.book_id
         INNER JOIN users AS u ON u.id = br.user_id
         ORDER BY (br.status = \'pending\') DESC, br.id DESC
         LIMIT 200'
    )->fetchAll();
} catch (PDOException $exception) {
    error_log('Unable to load librarian borrowing queue: ' . $exception->getMessage());
    $databaseError = 'Borrowing records could not be loaded. Please check the database connection and schema.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <title>Borrowing requests | Oak &amp; Ink Library</title>
    <style>
        :root { --paper:#f6f2e9; --card:#fffdf8; --ink:#29251f; --muted:#81786c; --line:#e8dfd1; --brown:#4b3528; --brown-light:#6f503b; --green:#64734f; --red:#9f4f3d; }
        * { box-sizing:border-box; }
        body { min-width:320px; margin:0; padding:32px 20px; color:var(--ink); background:var(--paper); font-family:"Segoe UI","Helvetica Neue",Arial,sans-serif; }
        a { color:inherit; text-decoration:none; }
        .page { width:min(1120px,100%); margin:0 auto; }
        .topline { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:28px; }
        .brand { display:flex; align-items:center; gap:10px; color:var(--brown); font-family:Georgia,"Times New Roman",serif; font-size:17px; }
        .mark { display:grid; width:38px; height:38px; place-items:center; border-radius:11px; color:#e9d5b0; background:var(--brown); }
        .mark svg { width:21px; height:21px; }
        .back { color:var(--brown-light); font-size:11px; font-weight:600; }
        h1 { margin:0; font-family:Georgia,"Times New Roman",serif; font-size:31px; font-weight:400; }
        .intro { margin:8px 0 20px; color:var(--muted); font-size:12px; line-height:1.6; }
        .summary { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 16px; margin-bottom:14px; border:1px solid var(--line); border-radius:9px; background:var(--card); color:var(--muted); font-size:11px; }
        .summary strong { color:var(--brown); }
        .search { width:min(340px,100%); min-height:38px; padding:8px 11px; border:1px solid var(--line); border-radius:7px; outline:none; color:var(--ink); background:var(--card); font:inherit; font-size:11px; }
        .search:focus { border-color:#b69a71; box-shadow:0 0 0 3px rgba(182,154,113,.13); }
        .table-card { overflow:hidden; border:1px solid var(--line); border-radius:10px; background:var(--card); }
        .table-scroll { overflow-x:auto; }
        table { width:100%; min-width:890px; border-collapse:collapse; text-align:left; }
        th,td { padding:12px 13px; border-bottom:1px solid #f0ece5; vertical-align:middle; }
        th { color:#968e81; font-size:9px; letter-spacing:.7px; text-transform:uppercase; }
        td { color:#625b51; font-size:10px; }
        tbody tr:last-child td { border-bottom:0; }
        .book { color:#403930; font-weight:600; }
        .sub { margin-top:4px; color:#9a9286; font-size:9px; }
        .status { display:inline-flex; padding:5px 8px; border-radius:18px; color:#766d62; background:#f0ece5; font-size:9px; white-space:nowrap; }
        .status.pending { color:#8d7145; background:#f5efdf; }
        .status.approved { color:var(--green); background:#edf0e7; }
        .status.rejected { color:var(--red); background:#f7ebe4; }
        .review-actions { display:flex; gap:6px; }
        .review-actions form { margin:0; }
        .review-button { min-height:30px; padding:0 9px; border:1px solid #c9d2bb; border-radius:6px; color:#536343; background:#f7f8f3; font:inherit; font-size:9px; font-weight:650; cursor:pointer; }
        .review-button.reject { border-color:#ead2c8; color:#9f4f3d; background:#fcf7f4; }
        .review-button:hover { filter:brightness(.97); }
        .notice { padding:11px 13px; margin:0 0 14px; border:1px solid #efd8cc; border-radius:8px; color:var(--red); background:#f7ebe4; font-size:10px; line-height:1.5; }
        .notice.success { border-color:#dfe5d4; color:#536343; background:#edf0e7; }
        .empty { padding:30px 16px; color:var(--muted); text-align:center; font-size:11px; }
        .footer { margin-top:17px; color:#a39b90; font-size:9px; text-align:center; }
        @media(max-width:620px) { body{padding:23px 13px}.topline{align-items:flex-start}.summary{align-items:flex-start;flex-direction:column}h1{font-size:27px} }
    </style>
</head>
<body>
    <?php require __DIR__ . '/inc/sidebar.php'; ?>
    <main class="page">
        <div class="topline">
            <a class="brand" href="dashboard.php"><span class="mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-2.5 8-1z"/><path d="M12 6.5v13"/></svg></span>Oak &amp; Ink · Circulation</a>
            <a class="back" href="dashboard.php">← Dashboard</a>
        </div>
        <h1>Borrowing requests</h1>
        <p class="intro">Review member requests. Approving checks out one copy and starts its 7-day return period; declining leaves stock unchanged.</p>
        <?php if (is_array($feedback) && isset($feedback['message'], $feedback['type'])): ?>
            <p class="notice<?php echo $feedback['type'] === 'success' ? ' success' : ''; ?>" role="<?php echo $feedback['type'] === 'success' ? 'status' : 'alert'; ?>"><?php echo $escape((string) $feedback['message']); ?></p>
        <?php endif; ?>
        <?php if ($databaseError !== null): ?>
            <p class="notice" role="alert"><?php echo $escape($databaseError); ?></p>
        <?php else: ?>
            <div class="summary">
                <span><strong><?php echo $pendingCount; ?></strong> request<?php echo $pendingCount === 1 ? '' : 's'; ?> awaiting review</span>
                <input class="search" id="requestSearch" type="search" placeholder="Search member or book..." aria-label="Search borrowing requests">
            </div>
            <section class="table-card" aria-label="Member borrowing requests">
                <?php if ($borrowings === []): ?>
                    <p class="empty">No borrowing requests or checkouts have been recorded yet.</p>
                <?php else: ?>
                    <div class="table-scroll"><table id="requestsTable">
                        <thead><tr><th>Book</th><th>Member</th><th>Requested / borrowed</th><th>Due date</th><th>Available</th><th>Status</th><th>Review</th></tr></thead>
                        <tbody>
                        <?php foreach ($borrowings as $borrowing): ?>
                            <?php
                            $status = (string) $borrowing['status'];
                            $borrowDate = $status === 'rejected'
                                ? '—'
                                : (new DateTimeImmutable($borrowing['borrow_date']))->format('M j, Y');
                            ?>
                            <tr>
                                <td><span class="book"><?php echo $escape((string) $borrowing['book_name']); ?></span><div class="sub"><?php echo $escape((string) $borrowing['author']); ?></div></td>
                                <td><?php echo $escape((string) $borrowing['member_name']); ?><div class="sub">@<?php echo $escape((string) $borrowing['username']); ?></div></td>
                                <td><?php echo $escape($borrowDate); ?></td>
                                <td><?php echo $borrowing['due_date'] !== null ? $escape((new DateTimeImmutable($borrowing['due_date']))->format('M j, Y')) : '—'; ?></td>
                                <td><?php echo (int) $borrowing['available']; ?></td>
                                <td><span class="status <?php echo $escape($status); ?>"><?php echo $escape(ucfirst($status)); ?></span></td>
                                <td>
                                    <?php if ($status === 'pending'): ?>
                                        <div class="review-actions">
                                            <form method="post" action="process_borrowing.php"><input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>"><input type="hidden" name="borrowing_id" value="<?php echo (int) $borrowing['id']; ?>"><button class="review-button" type="submit" name="decision" value="approve">Approve</button></form>
                                            <form method="post" action="process_borrowing.php"><input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>"><input type="hidden" name="borrowing_id" value="<?php echo (int) $borrowing['id']; ?>"><button class="review-button reject" type="submit" name="decision" value="reject">Decline</button></form>
                                        </div>
                                    <?php elseif ($status === 'approved' && $borrowing['date_returned'] !== null): ?>
                                        Returned
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table><p class="empty" id="emptyState" hidden>No requests match your search.</p></div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        <p class="footer">Oak &amp; Ink Community Library · Circulation review</p>
    </main>
    <?php if ($databaseError === null && $borrowings !== []): ?>
    <script>
        const search = document.getElementById('requestSearch');
        const rows = Array.from(document.querySelectorAll('#requestsTable tbody tr'));
        const empty = document.getElementById('emptyState');
        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            let visible = 0;
            rows.forEach((row) => {
                row.hidden = !row.textContent.toLowerCase().includes(query);
                if (!row.hidden) visible += 1;
            });
            empty.hidden = visible !== 0;
        });
    </script>
    <?php endif; ?>
</body>
</html>
