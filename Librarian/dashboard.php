<?php
session_start();
require __DIR__ . '/../inc/authorization.php';
require_library_role(['librarian'], '../auth/login.php');
$today = date('l, F j, Y');
$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};
$librarianName = is_string($_SESSION['name'] ?? null) ? $_SESSION['name'] : 'Librarian';
$dashboardStats = ['titles' => 0, 'borrowed' => 0, 'overdue' => 0, 'pending' => 0];
$recentBorrowings = [];
$upcomingReturns = [];
$dashboardError = null;
$reviewCsrfToken = $_SESSION['borrowing_review_csrf'] ?? null;
if (!is_string($reviewCsrfToken) || !preg_match('/\A[a-f0-9]{64}\z/D', $reviewCsrfToken)) {
    $_SESSION['borrowing_review_csrf'] = bin2hex(random_bytes(32));
    $reviewCsrfToken = $_SESSION['borrowing_review_csrf'];
}
try {
    require __DIR__ . '/../inc/connection.php';
    $dashboardStats['titles'] = (int) $pdo->query('SELECT COUNT(*) FROM textbooks')->fetchColumn();
    $dashboardStats['borrowed'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM borrowings WHERE status = 'approved' AND date_returned IS NULL"
    )->fetchColumn();
    $dashboardStats['overdue'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM borrowings
         WHERE status = 'approved' AND date_returned IS NULL AND due_date < CURRENT_DATE"
    )->fetchColumn();
    $dashboardStats['pending'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM borrowings WHERE status = 'pending'"
    )->fetchColumn();
    $recentBorrowings = $pdo->query(
        'SELECT br.id, br.status, br.borrow_date, br.due_date, br.date_returned,
                b.name AS book_name, b.author, u.name AS member_name
         FROM borrowings AS br
         INNER JOIN textbooks AS b ON b.id = br.book_id
         INNER JOIN users AS u ON u.id = br.user_id
         ORDER BY br.id DESC
         LIMIT 8'
    )->fetchAll();
    $upcomingReturns = $pdo->query(
        "SELECT b.name AS book_name, u.name AS member_name, br.due_date
         FROM borrowings AS br
         INNER JOIN textbooks AS b ON b.id = br.book_id
         INNER JOIN users AS u ON u.id = br.user_id
         WHERE br.status = 'approved' AND br.date_returned IS NULL
           AND br.due_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
         ORDER BY br.due_date ASC, br.id ASC
         LIMIT 5"
    )->fetchAll();
} catch (PDOException $exception) {
    error_log('Unable to load librarian dashboard circulation data: ' . $exception->getMessage());
    $dashboardError = 'Circulation data is temporarily unavailable. Please check the database connection and schema.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <title>Dashboard | Oak &amp; Ink Library</title>
    <style>
        :root {
            color-scheme: light;
            --paper: #f6f2e9;
            --card: #fffdf8;
            --ink: #29251f;
            --muted: #8a8377;
            --line: #ebe5d9;
            --brown: #4b3528;
            --brown-light: #6f503b;
            --olive: #7f8561;
            --rust: #b66b4e;
            --gold: #c5a46d;
            --soft-green: #edf0e6;
            --soft-rust: #f7ebe5;
            --shadow: 0 8px 28px rgba(69, 51, 36, .045);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }
        button, input { font: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        a { color: inherit; text-decoration: none; }
        .app { min-height: 100vh; }

        .sidebar {
            display: none;
            width: 248px;
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 5;
            display: flex;
            flex-direction: column;
            padding: 29px 17px 20px;
            color: #f7f1e7;
            background: var(--brown);
        }
        .brand { display: flex; align-items: center; gap: 12px; padding: 0 10px 35px; }
        .brand-mark {
            width: 39px; height: 39px; display: grid; place-items: center;
            border: 1px solid rgba(235, 218, 188, .4); border-radius: 12px;
            color: #e9d5b0; background: rgba(255,255,255,.055);
        }
        .brand-mark svg { width: 22px; height: 22px; }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 18px; letter-spacing: -.3px; }
        .brand-caption { margin-top: 3px; color: #c9bbaa; font-size: 10px; letter-spacing: 1.25px; text-transform: uppercase; }
        .nav-label { padding: 0 11px; margin: 0 0 10px; color: #b9a998; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
        .nav-group + .nav-group { margin-top: 27px; }
        .nav-list { display: grid; gap: 5px; }
        .nav-link {
            display: flex; align-items: center; gap: 12px; min-height: 43px;
            padding: 0 12px; border-radius: 9px; color: #d8cabb;
            font-size: 13px; transition: background .18s, color .18s;
        }
        .nav-link:hover { color: #fffaf1; background: rgba(255,255,255,.075); }
        .nav-link.active { color: #fffaf1; background: #634a36; box-shadow: inset 3px 0 #d0b17e; }
        .nav-link svg { width: 17px; height: 17px; flex: 0 0 17px; opacity: .88; }
        .nav-count { margin-left: auto; color: #e5c99d; font-size: 11px; }
        .sidebar-bottom { margin-top: auto; }
        .help-card { padding: 15px; border: 1px solid rgba(239,225,204,.16); border-radius: 11px; background: rgba(255,255,255,.045); }
        .help-card strong { display: block; margin-bottom: 6px; font-family: Georgia, serif; font-size: 15px; font-weight: 400; }
        .help-card p { margin: 0 0 12px; color: #cbbbaa; font-size: 11px; line-height: 1.55; }
        .help-card a { color: #e7cb9d; font-size: 11px; font-weight: 600; }
        .profile { display: flex; align-items: center; gap: 10px; padding: 19px 5px 0; margin-top: 17px; border-top: 1px solid rgba(239,225,204,.15); }
        .avatar { width: 34px; height: 34px; display: grid; place-items: center; flex: 0 0 34px; border-radius: 50%; color: #4b3528; background: #e4c99e; font-family: Georgia, serif; font-weight: 700; }
        .profile-name { font-size: 12px; font-weight: 600; }
        .profile-role { margin-top: 3px; color: #c2b09f; font-size: 10px; }
        .profile-more { margin-left: auto; color: #d9cbb9; font-size: 18px; }

        .main { min-height: 100vh; margin-left: 0; }
        .topbar {
            height: 76px; display: flex; align-items: center; justify-content: space-between;
            padding: 0 42px; border-bottom: 1px solid #e9e3d8; background: rgba(255,253,248,.73);
        }
        .crumb { color: var(--muted); font-size: 12px; }
        .crumb span { padding: 0 8px; color: #c2b9ab; }
        .top-actions { display: flex; align-items: center; gap: 16px; }
        .search { position: relative; width: 228px; }
        .search svg { position: absolute; top: 11px; left: 12px; width: 16px; height: 16px; color: #92897d; }
        .search input { width: 100%; height: 38px; padding: 0 12px 0 37px; outline: none; border: 1px solid var(--line); border-radius: 8px; color: var(--ink); background: #fffdf9; font-size: 12px; }
        .search input:focus { border-color: #b69a71; box-shadow: 0 0 0 3px rgba(182,154,113,.13); }
        .icon-button { width: 37px; height: 37px; display: grid; place-items: center; position: relative; border: 1px solid var(--line); border-radius: 9px; color: #6c6256; background: #fffdf9; cursor: pointer; }
        .icon-button svg { width: 17px; height: 17px; }
        .notification-dot { position: absolute; top: 8px; right: 8px; width: 6px; height: 6px; border: 1px solid #fffdf9; border-radius: 50%; background: var(--rust); }
        .mobile-brand, .menu-toggle { display: none; }

        .content { max-width: 1500px; padding: 35px 42px 42px; margin: 0 auto; }
        .welcome { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin-bottom: 27px; }
        .eyebrow { margin: 0 0 9px; color: #978b79; font-size: 10px; font-weight: 700; letter-spacing: 1.45px; text-transform: uppercase; }
        h1 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: clamp(27px, 3vw, 35px); font-weight: 400; letter-spacing: -.7px; }
        .welcome-copy { margin: 9px 0 0; color: var(--muted); font-size: 13px; }
        .primary-button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px; padding: 0 15px; border: 0; border-radius: 8px; color: #fffaf3; background: var(--brown); font-size: 12px; font-weight: 600; cursor: pointer; transition: background .18s, transform .18s; }
        .primary-button:hover { background: #624632; transform: translateY(-1px); }
        .primary-button svg { width: 15px; height: 15px; }
        .secondary-button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px; padding: 0 14px; border: 1px solid #dcd2c2; border-radius: 8px; color: var(--brown); background: var(--card); font-size: 12px; font-weight: 600; transition: border-color .18s, background .18s; }
        .secondary-button:hover { border-color: #bca989; background: #faf6ed; }
        .secondary-button svg { width: 15px; height: 15px; }
        .welcome-actions { display: flex; align-items: center; gap: 9px; }

        .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card, .panel { border: 1px solid #eee8dd; border-radius: 12px; background: var(--card); box-shadow: var(--shadow); }
        .stat-card { min-height: 137px; padding: 17px 18px 15px; }
        .stat-top { display: flex; align-items: center; justify-content: space-between; color: #776f64; font-size: 11px; }
        .stat-icon { width: 31px; height: 31px; display: grid; place-items: center; border-radius: 9px; color: var(--brown-light); background: #f2ede3; }
        .stat-icon svg { width: 16px; height: 16px; }
        .stat-card:nth-child(2) .stat-icon { color: #65734f; background: var(--soft-green); }
        .stat-card:nth-child(3) .stat-icon { color: #ac6849; background: var(--soft-rust); }
        .stat-card:nth-child(4) .stat-icon { color: #937344; background: #f5efdf; }
        .stat-value { margin-top: 12px; font-family: Georgia, "Times New Roman", serif; font-size: 29px; letter-spacing: -.5px; }
        .stat-foot { display: flex; align-items: center; gap: 6px; margin-top: 6px; color: #8e867b; font-size: 10px; }
        .trend { color: #68764f; font-weight: 700; }
        .trend.down { color: #ab684a; }

        .overview-grid { display: grid; grid-template-columns: minmax(0, 1.75fr) minmax(250px, .9fr); gap: 18px; margin-bottom: 19px; }
        .panel { padding: 20px; }
        .panel-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; }
        .panel-title { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 17px; font-weight: 400; }
        .panel-subtitle { margin: 5px 0 0; color: var(--muted); font-size: 11px; }
        .select { padding: 7px 25px 7px 10px; border: 1px solid var(--line); border-radius: 7px; outline: none; color: #6e665b; background: #fffdf9; font-size: 10px; }
        .chart-legend { display: flex; align-items: center; gap: 15px; margin-top: 17px; color: #81796d; font-size: 10px; }
        .legend-key { display: inline-flex; align-items: center; gap: 6px; }
        .legend-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--brown-light); }
        .legend-dot.alt { background: #cbb58d; }
        .chart { display: block; width: 100%; height: 191px; margin-top: 8px; overflow: visible; }
        .chart text { fill: #9a9287; font-family: "Segoe UI", Arial, sans-serif; font-size: 10px; }
        .genre-list { display: grid; gap: 17px; margin-top: 24px; }
        .genre-head { display: flex; justify-content: space-between; margin-bottom: 8px; color: #645d53; font-size: 11px; }
        .genre-head strong { color: #453b30; font-weight: 600; }
        .track { height: 6px; overflow: hidden; border-radius: 9px; background: #f0ece4; }
        .fill { height: 100%; border-radius: inherit; background: #77583e; }
        .genre:nth-child(2) .fill { background: #9c8865; }
        .genre:nth-child(3) .fill { background: #9a7657; }
        .genre:nth-child(4) .fill { background: #a8a47e; }
        .genre-note { display: flex; gap: 7px; align-items: center; margin-top: 21px; padding-top: 14px; border-top: 1px solid var(--line); color: #858071; font-size: 10px; line-height: 1.5; }
        .genre-note svg { width: 14px; height: 14px; flex: 0 0 14px; color: var(--olive); }

        .lower-grid { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(260px, .9fr); gap: 18px; }
        .table-wrap { overflow-x: auto; margin: 16px -3px -4px; }
        table { width: 100%; min-width: 520px; border-collapse: collapse; text-align: left; }
        th { padding: 0 10px 10px; border-bottom: 1px solid var(--line); color: #968e81; font-size: 9px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
        td { padding: 12px 10px; border-bottom: 1px solid #f0ece5; color: #625b51; font-size: 11px; }
        tbody tr:last-child td { border-bottom: 0; }
        .book-cell { display: flex; align-items: center; gap: 9px; min-width: 160px; }
        .book-cover { width: 29px; height: 36px; display: grid; place-items: center; flex: 0 0 29px; border-radius: 3px 5px 5px 3px; color: #f4e8d2; background: #795b42; box-shadow: inset 3px 0 rgba(0,0,0,.12); font-family: Georgia, serif; font-size: 10px; }
        tr:nth-child(2) .book-cover { color: #f8eddf; background: #778064; }
        tr:nth-child(3) .book-cover { color: #f7ebdc; background: #a76d51; }
        tr:nth-child(4) .book-cover { color: #f4eadb; background: #867151; }
        .book-title { color: #403930; font-size: 11px; font-weight: 600; }
        .book-author { margin-top: 3px; color: #9a9286; font-size: 10px; }
        .status { display: inline-flex; align-items: center; gap: 5px; padding: 5px 8px; border-radius: 20px; color: #62704f; background: #edf0e7; font-size: 9px; white-space: nowrap; }
        .status.overdue { color: #a45d43; background: #f7ebe4; }
        .status.pending { color: #8d7145; background: #f5efdf; }
        .status.rejected, .status.returned { color: #766d62; background: #f0ece5; }
        .status::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ""; }
        .review-actions { display: flex; gap: 5px; }
        .review-actions form { margin: 0; }
        .review-button { min-height: 28px; padding: 0 8px; border: 1px solid #c9d2bb; border-radius: 6px; color: #536343; background: #f7f8f3; font: inherit; font-size: 9px; font-weight: 650; cursor: pointer; white-space: nowrap; }
        .review-button.reject { border-color: #ead2c8; color: #9f4f3d; background: #fcf7f4; }
        .dashboard-notice { padding: 11px 13px; margin: 0 0 15px; border: 1px solid #efd8cc; border-radius: 8px; color: #9f4f3d; background: #f7ebe4; font-size: 10px; }
        .text-link { color: #79593e; font-size: 10px; font-weight: 600; white-space: nowrap; }
        .text-link:hover { text-decoration: underline; }
        .edit-link { color: #79593e; font-size: 10px; font-weight: 600; white-space: nowrap; }
        .edit-link:hover { text-decoration: underline; }
        .returns-list { margin-top: 16px; }
        .return-item { display: flex; align-items: center; gap: 10px; padding: 12px 0; border-bottom: 1px solid #f0ece5; }
        .return-item:last-child { border-bottom: 0; }
        .return-date { width: 39px; height: 43px; display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 0 0 39px; border: 1px solid var(--line); border-radius: 7px; color: #756c5f; background: #fbf8f2; }
        .return-date strong { font-family: Georgia, serif; font-size: 15px; font-weight: 400; line-height: 1; }
        .return-date span { margin-top: 4px; color: #9b8d78; font-size: 8px; letter-spacing: .5px; text-transform: uppercase; }
        .return-copy { min-width: 0; flex: 1; }
        .return-book { overflow: hidden; color: #4d453b; font-size: 11px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .return-person { margin-top: 4px; color: #938b7f; font-size: 10px; }
        .return-tag { color: #8a816f; font-size: 9px; white-space: nowrap; }
        .return-tag.urgent { color: #ac6448; }
        .empty-state { display: none; padding: 22px 10px; color: var(--muted); text-align: center; font-size: 12px; }
        .footer-note { margin-top: 22px; color: #a39b90; font-size: 10px; text-align: center; }

        @media (max-width: 1120px) {
            .sidebar { width: 218px; }
            .main { margin-left: 218px; }
            .topbar { padding: 0 28px; }
            .content { padding: 30px 28px 36px; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 800px) {
            .sidebar { width: 248px; transform: translateX(-100%); transition: transform .22s ease; box-shadow: 8px 0 30px rgba(35,25,18,.15); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .menu-toggle { display: grid; }
            .mobile-brand { display: block; margin-left: 12px; font-family: Georgia, serif; font-size: 16px; }
            .topbar { justify-content: flex-start; padding: 0 20px 0 58px; }
            .crumb { display: none; }
            .top-actions { margin-left: auto; }
            .overview-grid, .lower-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .topbar { height: 65px; padding: 0 15px 0 58px; }
            .search { width: min(39vw, 185px); }
            .top-actions { gap: 9px; }
            .content { padding: 26px 15px 30px; }
            .welcome { align-items: flex-start; flex-direction: column; gap: 16px; margin-bottom: 21px; }
            .welcome-actions { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
            .welcome-actions a { min-width: 0; padding: 0 8px; }
            .stats { gap: 10px; margin-bottom: 13px; }
            .stat-card { min-height: 126px; padding: 13px; }
            .stat-top { align-items: flex-start; gap: 5px; font-size: 10px; }
            .stat-value { font-size: 25px; }
            .stat-foot { align-items: flex-start; flex-wrap: wrap; font-size: 9px; }
            .overview-grid, .lower-grid { gap: 13px; margin-bottom: 13px; }
            .panel { padding: 16px 14px; }
            .chart { height: 170px; }
            .select { max-width: 105px; }
        }
    </style>
</head>
<body>
<div class="app">
    <?php require __DIR__ . '/inc/sidebar.php'; ?>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="#" aria-label="Oak and Ink Library home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.2-2.5 8-1z"/><path d="M12 6.5v13"/></svg>
            </span>
            <span><span class="brand-name">Oak &amp; Ink</span><span class="brand-caption">Community Library</span></span>
        </a>

        <nav aria-label="Main navigation">
            <div class="nav-group">
                <p class="nav-label">Workspace</p>
                <div class="nav-list">
                    <a class="nav-link active" href="#" aria-current="page">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>
                        Dashboard
                    </a>
                    <a class="nav-link active" href="books.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.2-2.5 8-1z"/></svg>
                        Book collection <span class="nav-count">12.4k</span>
                    </a>
                    <a class="nav-link" href="borrowings.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5h14v16l-7-4-7 4z"/><path d="M8.5 8h7M8.5 11h7"/></svg>
                        Circulation
                    </a>
                    <a class="nav-link" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.5-3.3 2.8-5 6.5-5s6 1.7 6.5 5M16 5a3.5 3.5 0 0 1 0 6.8M17 15c2.8.4 4.3 2 4.5 5"/></svg>
                        Members
                    </a>
                </div>
            </div>
            <div class="nav-group">
                <p class="nav-label">Insights</p>
                <div class="nav-list">
                    <a class="nav-link" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5M4 19h17"/><path d="m7 15 4-4 3 2 6-7"/></svg>
                        Reports
                    </a>
                    <a class="nav-link" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 2.9-.2-.1a1.7 1.7 0 0 0-1.8.1 1.7 1.7 0 0 0-.9 1.5v.2h-3.4v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8-.1l-.2.1-1.7-2.9.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 6 14.1H5.8v-3.4H6a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 1.7-2.9.2.1a1.7 1.7 0 0 0 1.8-.1 1.7 1.7 0 0 0 .9-1.5v-.2h3.4v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8.1l.2-.1 1.7 2.9-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.2v3.4H21a1.7 1.7 0 0 0-1.6.9z" transform="translate(-1 -1) scale(.95)"/></svg>
                        Settings
                    </a>
                </div>
            </div>
        </nav>

        <div class="sidebar-bottom">
            <div class="help-card">
                <strong>A little help?</strong>
                <p>Find quick answers and tips for your library workspace.</p>
                <a href="#">Visit the help center &nbsp;→</a>
            </div>
            <div class="profile">
                <span class="avatar" aria-hidden="true">OR</span>
                <span><span class="profile-name">Olivia Reed</span><span class="profile-role">Head librarian</span></span>
                <span class="profile-more" aria-hidden="true">···</span>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="crumb">Workspace <span>/</span> Dashboard</div>
            <div class="top-actions">
                <label class="search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                    <input id="dashboardSearch" type="search" placeholder="Search recent activity..." aria-label="Search recent activity">
                </label>
                <button class="icon-button" type="button" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                    <span class="notification-dot"></span>
                </button>
            </div>
        </header>

        <div class="content">
            <section class="welcome">
                <div>
                    <p class="eyebrow"><?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?></p>
                    <h1>Good day, <?php echo $escape($librarianName); ?> <span aria-hidden="true">☀</span></h1>
                    <p class="welcome-copy">Here's what's happening around the library today.</p>
                </div>
                <div class="welcome-actions">
                    <a class="secondary-button" href="books.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.2-2.5 8-1z"/></svg>
                        View books
                    </a>
                    <a class="secondary-button" href="borrowings.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5h14v16l-7-4-7 4z"/><path d="M12 7v6M9 10h6"/></svg>
                        Review requests<?php if ($dashboardStats['pending'] > 0): ?> · <?php echo $dashboardStats['pending']; ?> pending<?php endif; ?>
                    </a>
                    <a class="primary-button" href="add_book.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add a new book
                    </a>
                </div>
            </section>

            <?php if ($dashboardError !== null): ?>
                <p class="dashboard-notice" role="alert"><?php echo $escape($dashboardError); ?></p>
            <?php endif; ?>
            <section class="stats" aria-label="Library statistics">
                <article class="stat-card">
                    <div class="stat-top"><span>Total books</span><span class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.2-2.5 8-1z"/></svg></span></div>
                    <div class="stat-value"><?php echo number_format($dashboardStats['titles']); ?></div>
                    <div class="stat-foot"><span>titles in catalog</span></div>
                </article>
                <article class="stat-card">
                    <div class="stat-top"><span>Pending requests</span><span class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/></svg></span></div>
                    <div class="stat-value"><?php echo number_format($dashboardStats['pending']); ?></div>
                    <div class="stat-foot"><a class="text-link" href="borrowings.php">Review requests →</a></div>
                </article>
                <article class="stat-card">
                    <div class="stat-top"><span>Currently borrowed</span><span class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5h14v16l-7-4-7 4z"/><path d="M8.5 8h7M8.5 11h7"/></svg></span></div>
                    <div class="stat-value"><?php echo number_format($dashboardStats['borrowed']); ?></div>
                    <div class="stat-foot"><span>approved checkouts</span></div>
                </article>
                <article class="stat-card">
                    <div class="stat-top"><span>Overdue returns</span><span class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3.5 2"/></svg></span></div>
                    <div class="stat-value"><?php echo number_format($dashboardStats['overdue']); ?></div>
                    <div class="stat-foot"><span>approved checkouts past due</span></div>
                </article>
            </section>

            <section class="overview-grid" aria-label="Library overview">
                <article class="panel">
                    <div class="panel-header">
                        <div><h2 class="panel-title">Circulation activity</h2><p class="panel-subtitle">Books checked out and returned</p></div>
                        <select class="select" aria-label="Activity date range">
                            <option>This week</option><option>This month</option><option>This year</option>
                        </select>
                    </div>
                    <div class="chart-legend"><span class="legend-key"><i class="legend-dot"></i>Checkouts</span><span class="legend-key"><i class="legend-dot alt"></i>Returns</span></div>
                    <svg class="chart" viewBox="0 0 620 205" role="img" aria-label="Bar chart of checkouts and returns from Monday to Sunday">
                        <g stroke="#eee9e0" stroke-width="1">
                            <line x1="45" y1="24" x2="610" y2="24"/><line x1="45" y1="65" x2="610" y2="65"/>
                            <line x1="45" y1="106" x2="610" y2="106"/><line x1="45" y1="147" x2="610" y2="147"/>
                        </g>
                        <g><text x="3" y="28">120</text><text x="9" y="69">80</text><text x="9" y="110">40</text><text x="17" y="151">0</text></g>
                        <g fill="#77583e">
                            <rect x="73" y="75" width="17" height="72" rx="3"/><rect x="151" y="58" width="17" height="89" rx="3"/><rect x="229" y="88" width="17" height="59" rx="3"/>
                            <rect x="307" y="45" width="17" height="102" rx="3"/><rect x="385" y="67" width="17" height="80" rx="3"/><rect x="463" y="53" width="17" height="94" rx="3"/><rect x="541" y="98" width="17" height="49" rx="3"/>
                        </g>
                        <g fill="#cbb58d">
                            <rect x="93" y="103" width="17" height="44" rx="3"/><rect x="171" y="85" width="17" height="62" rx="3"/><rect x="249" y="112" width="17" height="35" rx="3"/>
                            <rect x="327" y="91" width="17" height="56" rx="3"/><rect x="405" y="77" width="17" height="70" rx="3"/><rect x="483" y="96" width="17" height="51" rx="3"/><rect x="561" y="118" width="17" height="29" rx="3"/>
                        </g>
                        <g text-anchor="middle"><text x="91" y="174">Mon</text><text x="169" y="174">Tue</text><text x="247" y="174">Wed</text><text x="325" y="174">Thu</text><text x="403" y="174">Fri</text><text x="481" y="174">Sat</text><text x="559" y="174">Sun</text></g>
                    </svg>
                </article>
                <article class="panel">
                    <div class="panel-header"><div><h2 class="panel-title">Popular genres</h2><p class="panel-subtitle">Borrowed this month</p></div><a class="text-link" href="#">View report →</a></div>
                    <div class="genre-list">
                        <div class="genre"><div class="genre-head"><span>Literary fiction</span><strong>36%</strong></div><div class="track"><div class="fill" style="width: 36%"></div></div></div>
                        <div class="genre"><div class="genre-head"><span>Mystery &amp; thriller</span><strong>28%</strong></div><div class="track"><div class="fill" style="width: 28%"></div></div></div>
                        <div class="genre"><div class="genre-head"><span>History</span><strong>21%</strong></div><div class="track"><div class="fill" style="width: 21%"></div></div></div>
                        <div class="genre"><div class="genre-head"><span>Children's</span><strong>15%</strong></div><div class="track"><div class="fill" style="width: 15%"></div></div></div>
                    </div>
                    <div class="genre-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M7 8l5-5 5 5"/><path d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/></svg>
                        Literary fiction leads the shelves again this month.
                    </div>
                </article>
            </section>

            <section class="lower-grid" aria-label="Recent activity and upcoming returns">
                <article class="panel">
                    <div class="panel-header">
                        <div><h2 class="panel-title">Recent circulation</h2><p class="panel-subtitle">The latest checkouts from your library</p></div>
                        <a class="text-link" href="borrowings.php">Review all →</a>
                    </div>
                    <div class="table-wrap">
                        <table id="activityTable">
                            <thead><tr><th>Book</th><th>Member</th><th>Requested / borrowed</th><th>Due date</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentBorrowings as $borrowing): ?>
                                    <?php
                                    $borrowingStatus = (string) $borrowing['status'];
                                    $statusLabel = ucfirst($borrowingStatus);
                                    $statusClass = $borrowingStatus;
                                    if ($borrowingStatus === 'approved' && $borrowing['date_returned'] !== null) {
                                        $statusLabel = 'Returned';
                                        $statusClass = 'returned';
                                    } elseif ($borrowingStatus === 'approved' && $borrowing['due_date'] !== null &&
                                        new DateTimeImmutable($borrowing['due_date']) < new DateTimeImmutable('today')) {
                                        $statusLabel = 'Overdue';
                                        $statusClass = 'overdue';
                                    } elseif ($borrowingStatus === 'approved') {
                                        $statusLabel = 'Borrowed';
                                        $statusClass = '';
                                    }
                                    $bookTitle = (string) $borrowing['book_name'];
                                    ?>
                                    <tr>
                                        <td><div class="book-cell"><span class="book-cover"><?php echo htmlspecialchars(strtoupper(substr($bookTitle, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span><span><span class="book-title"><?php echo htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8'); ?></span><span class="book-author"><?php echo htmlspecialchars((string) $borrowing['author'], ENT_QUOTES, 'UTF-8'); ?></span></span></div></td>
                                        <td><?php echo htmlspecialchars((string) $borrowing['member_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((new DateTimeImmutable($borrowing['borrow_date']))->format('M j, Y'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo $borrowing['due_date'] !== null ? htmlspecialchars((new DateTimeImmutable($borrowing['due_date']))->format('M j, Y'), ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                        <td><span class="status<?php echo $statusClass !== '' ? ' ' . htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') : ''; ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td>
                                            <?php if ($borrowingStatus === 'pending'): ?>
                                                <div class="review-actions">
                                                    <form method="post" action="process_borrowing.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($reviewCsrfToken, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="borrowing_id" value="<?php echo (int) $borrowing['id']; ?>"><button class="review-button" type="submit" name="decision" value="approve">Approve</button></form>
                                                    <form method="post" action="process_borrowing.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($reviewCsrfToken, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="borrowing_id" value="<?php echo (int) $borrowing['id']; ?>"><button class="review-button reject" type="submit" name="decision" value="reject">Decline</button></form>
                                                </div>
                                            <?php else: ?>
                                                <a class="edit-link" href="borrowings.php">Details</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="empty-state" id="emptyState" style="<?php echo $recentBorrowings === [] ? 'display:block' : ''; ?>"><?php echo $recentBorrowings === [] ? 'No circulation requests or checkouts have been recorded yet.' : 'No circulation records match your search.'; ?></div>
                    </div>
                </article>
                <article class="panel">
                    <div class="panel-header"><div><h2 class="panel-title">Upcoming returns</h2><p class="panel-subtitle">Due back in the next 7 days</p></div><a class="text-link" href="borrowings.php">See all →</a></div>
                    <div class="returns-list">
                        <?php if ($upcomingReturns === []): ?>
                            <p class="panel-subtitle">No approved checkouts are due in the next 7 days.</p>
                        <?php else: ?>
                            <?php foreach ($upcomingReturns as $upcomingReturn): ?>
                                <?php
                                $dueDate = new DateTimeImmutable($upcomingReturn['due_date']);
                                $daysUntilDue = (int) (new DateTimeImmutable('today'))->diff($dueDate)->format('%r%a');
                                $dueLabel = $daysUntilDue === 0 ? 'Today' : ($daysUntilDue === 1 ? 'Tomorrow' : 'In ' . $daysUntilDue . ' days');
                                ?>
                                <div class="return-item">
                                    <div class="return-date"><strong><?php echo $escape($dueDate->format('d')); ?></strong><span><?php echo $escape($dueDate->format('M')); ?></span></div>
                                    <div class="return-copy"><div class="return-book"><?php echo htmlspecialchars((string) $upcomingReturn['book_name'], ENT_QUOTES, 'UTF-8'); ?></div><div class="return-person"><?php echo htmlspecialchars((string) $upcomingReturn['member_name'], ENT_QUOTES, 'UTF-8'); ?></div></div>
                                    <span class="return-tag<?php echo $daysUntilDue <= 1 ? ' urgent' : ''; ?>"><?php echo $escape($dueLabel); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>
            </section>
            <p class="footer-note">A good day starts with a good book. &nbsp;·&nbsp; Oak &amp; Ink Library</p>
        </div>
    </main>
</div>
<script>
    const search = document.getElementById('dashboardSearch');
    const rows = Array.from(document.querySelectorAll('#activityTable tbody tr'));
    const emptyState = document.getElementById('emptyState');
    search.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        let visibleRows = 0;
        rows.forEach((row) => {
            const isMatch = row.textContent.toLowerCase().includes(query);
            row.hidden = !isMatch;
            if (isMatch) visibleRows += 1;
        });
        emptyState.style.display = visibleRows === 0 ? 'block' : 'none';
    });
</script>
</body>
</html>