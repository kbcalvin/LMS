<?php
session_start();
$today = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <title>Reader dashboard | Oak &amp; Ink Library</title>
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
            --olive: #68764f;
            --soft-green: #edf0e6;
            --gold: #c5a46d;
            --shadow: 0 8px 28px rgba(69, 51, 36, .045);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            background: var(--paper);
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 10;
            width: 238px;
            display: flex;
            flex-direction: column;
            padding: 26px 16px 19px;
            color: #f7f1e7;
            background: var(--brown);
        }
        .brand { display: flex; align-items: center; gap: 11px; padding: 0 9px 34px; }
        .brand-mark { width: 39px; height: 39px; display: grid; place-items: center; flex: 0 0 39px; border: 1px solid rgba(235,218,188,.4); border-radius: 12px; color: #e9d5b0; background: rgba(255,255,255,.055); }
        .brand-mark svg { width: 22px; height: 22px; }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 18px; letter-spacing: -.3px; }
        .brand-caption { margin-top: 3px; color: #c9bbaa; font-size: 9px; letter-spacing: 1.2px; text-transform: uppercase; }
        .nav-label { padding: 0 11px; margin: 0 0 9px; color: #b9a998; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
        .nav-list { display: grid; gap: 5px; }
        .nav-link { min-height: 42px; display: flex; align-items: center; gap: 11px; padding: 0 12px; border-radius: 8px; color: #d8cabb; font-size: 12px; transition: background .18s, color .18s; }
        .nav-link:hover { color: #fffaf1; background: rgba(255,255,255,.075); }
        .nav-link.active { color: #fffaf1; background: #634a36; box-shadow: inset 3px 0 #d0b17e; }
        .nav-link svg { width: 17px; height: 17px; flex: 0 0 17px; opacity: .9; }
        .sidebar-note { margin-top: auto; padding: 14px; border: 1px solid rgba(239,225,204,.16); border-radius: 10px; background: rgba(255,255,255,.045); }
        .sidebar-note strong { display: block; margin-bottom: 6px; font-family: Georgia, "Times New Roman", serif; font-size: 15px; font-weight: 400; }
        .sidebar-note p { margin: 0; color: #cbbbaa; font-size: 10px; line-height: 1.55; }
        .main { min-height: 100vh; margin-left: 238px; }
        .topbar { min-height: 69px; display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 0 38px; border-bottom: 1px solid #e9e3d8; background: rgba(255,253,248,.73); }
        .crumb { color: var(--muted); font-size: 11px; }
        .topbar-right { display: flex; align-items: center; gap: 13px; }
        .top-search { position: relative; width: 230px; }
        .top-search svg { position: absolute; top: 10px; left: 11px; width: 16px; height: 16px; color: #92897d; }
        .top-search input { width: 100%; height: 37px; padding: 0 11px 0 34px; border: 1px solid var(--line); border-radius: 8px; outline: none; color: var(--ink); background: var(--card); font-size: 11px; }
        .top-search input:focus, .catalog-search input:focus { border-color: #b69a71; box-shadow: 0 0 0 3px rgba(182,154,113,.13); }
        .avatar { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 50%; color: var(--brown); background: #e4c99e; font-family: Georgia, serif; font-size: 11px; font-weight: 700; }
        .menu-toggle { display: none; }
        .content { width: min(1420px, 100%); padding: 32px 38px 42px; margin: 0 auto; }
        .welcome { display: flex; align-items: flex-end; justify-content: space-between; gap: 22px; padding: 25px 27px; margin-bottom: 19px; overflow: hidden; position: relative; border: 1px solid #e6dccb; border-radius: 13px; background: linear-gradient(112deg, #fffdf8 0%, #fffdf8 67%, #f0e6d4 100%); box-shadow: var(--shadow); }
        .welcome::after { width: 190px; height: 190px; position: absolute; top: -105px; right: 130px; border: 1px solid rgba(163,132,90,.14); border-radius: 50%; content: ""; }
        .eyebrow { margin: 0 0 9px; color: #978b79; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: clamp(27px, 3vw, 35px); font-weight: 400; letter-spacing: -.7px; }
        .welcome-copy { margin: 8px 0 0; color: var(--muted); font-size: 12px; }
        .welcome-actions { z-index: 1; display: flex; gap: 9px; }
        .button { min-height: 39px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 14px; border: 1px solid #dcd2c2; border-radius: 8px; color: var(--brown); background: var(--card); font-size: 11px; font-weight: 600; transition: background .18s, transform .18s; }
        .button:hover { background: #faf6ed; transform: translateY(-1px); }
        .button.primary { border-color: var(--brown); color: #fffaf3; background: var(--brown); }
        .button.primary:hover { background: #624632; }
        .button svg { width: 15px; height: 15px; }
        .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 19px; }
        .stat-card { min-height: 109px; padding: 16px 18px; border: 1px solid #eee8dd; border-radius: 11px; background: var(--card); box-shadow: var(--shadow); }
        .stat-label { display: flex; align-items: center; justify-content: space-between; gap: 10px; color: #776f64; font-size: 10px; }
        .stat-icon { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; color: var(--brown-light); background: #f2ede3; }
        .stat-icon.green { color: var(--olive); background: var(--soft-green); }
        .stat-icon svg { width: 16px; height: 16px; }
        .stat-value { margin-top: 10px; color: var(--brown); font-family: Georgia, "Times New Roman", serif; font-size: 23px; }
        .stat-detail { margin-top: 4px; color: #969083; font-size: 9px; }
        .section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 14px; margin-bottom: 14px; }
        .section-title { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 19px; font-weight: 400; }
        .section-subtitle { margin: 5px 0 0; color: var(--muted); font-size: 11px; }
        .text-link { color: #79593e; font-size: 10px; font-weight: 600; white-space: nowrap; }
        .text-link:hover { text-decoration: underline; }
        .catalog { padding: 20px; border: 1px solid #eee8dd; border-radius: 12px; background: var(--card); box-shadow: var(--shadow); }
        .catalog-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 14px; }
        .catalog-search { position: relative; width: min(300px, 100%); }
        .catalog-search svg { position: absolute; top: 10px; left: 11px; width: 16px; height: 16px; color: #92897d; }
        .catalog-search input { width: 100%; height: 37px; padding: 0 11px 0 34px; border: 1px solid var(--line); border-radius: 7px; outline: none; color: var(--ink); background: #fffefa; font-size: 11px; }
        .sample-label { color: #9a907f; font-size: 9px; }
        .book-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 13px; }
        .book-card { min-height: 164px; display: flex; gap: 12px; padding: 14px; border: 1px solid #eee8dd; border-radius: 10px; background: #fffefa; transition: border-color .18s, transform .18s; }
        .book-card:hover { border-color: #d5c5aa; transform: translateY(-2px); }
        .book-cover { width: 53px; height: 76px; display: grid; place-items: center; flex: 0 0 53px; border-radius: 4px 7px 7px 4px; color: #f4e8d2; background: #795b42; box-shadow: inset 4px 0 rgba(0,0,0,.12), 3px 4px 8px rgba(49,35,23,.1); font-family: Georgia, serif; font-size: 19px; }
        .book-card:nth-child(2) .book-cover { color: #f8eddf; background: #778064; }
        .book-card:nth-child(3) .book-cover { color: #f7ebdc; background: #a76d51; }
        .book-info { min-width: 0; display: flex; flex-direction: column; align-items: flex-start; }
        .genre { display: inline-flex; padding: 4px 7px; border-radius: 20px; color: #70614d; background: #f2ede3; font-size: 8px; }
        .book-title { margin: 8px 0 0; color: #403930; font-family: Georgia, "Times New Roman", serif; font-size: 14px; line-height: 1.3; }
        .book-author { margin-top: 4px; color: #938a7e; font-size: 10px; }
        .availability { display: flex; align-items: center; gap: 5px; margin-top: auto; padding-top: 12px; color: #64704f; font-size: 9px; }
        .availability::before { width: 6px; height: 6px; border-radius: 50%; background: #87956c; content: ""; }
        .borrow-cta { display: inline-flex; align-items: center; justify-content: center; min-height: 29px; padding: 0 9px; margin-top: 9px; border: 1px solid #dcd2c2; border-radius: 6px; color: var(--brown); background: #fffdf8; font-size: 9px; font-weight: 600; }
        .borrow-cta:hover { background: #f5f0e6; }
        .no-results { display: none; padding: 25px; color: var(--muted); font-size: 11px; text-align: center; }
        .lower-grid { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(240px, .8fr); gap: 14px; margin-top: 19px; }
        .panel { padding: 19px; border: 1px solid #eee8dd; border-radius: 11px; background: var(--card); box-shadow: var(--shadow); }
        .loan-empty { display: flex; align-items: center; gap: 13px; padding: 14px; margin-top: 13px; border-radius: 9px; background: #f8f5ee; }
        .loan-icon { width: 38px; height: 38px; display: grid; place-items: center; flex: 0 0 38px; border-radius: 10px; color: var(--brown-light); background: #eee5d7; }
        .loan-icon svg { width: 19px; height: 19px; }
        .loan-empty strong { color: #51483d; font-family: Georgia, "Times New Roman", serif; font-size: 13px; font-weight: 400; }
        .loan-empty p { margin: 5px 0 0; color: #92897d; font-size: 10px; line-height: 1.5; }
        .how-list { display: grid; gap: 13px; margin-top: 15px; }
        .how-item { display: flex; align-items: flex-start; gap: 10px; color: #746b60; font-size: 10px; line-height: 1.5; }
        .step { width: 22px; height: 22px; display: grid; place-items: center; flex: 0 0 22px; border-radius: 50%; color: #6c5638; background: #f2ede3; font-family: Georgia, serif; font-size: 11px; }
        .footer { padding-top: 20px; color: #a39b90; font-size: 9px; text-align: center; }
        @media (max-width: 1100px) {
            .book-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .content { padding-right: 27px; padding-left: 27px; }
            .topbar { padding: 0 27px; }
        }
        @media (max-width: 760px) {
            .sidebar { width: 248px; transform: translateX(-105%); transition: transform .22s ease; box-shadow: 8px 0 30px rgba(35,25,18,.15); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-backdrop { position: fixed; inset: 0; z-index: 9; border: 0; background: rgba(35,25,18,.28); }
            .sidebar-backdrop[hidden] { display: none; }
            .menu-toggle { width: 36px; height: 36px; display: grid; place-items: center; border: 1px solid var(--line); border-radius: 8px; color: var(--brown); background: var(--card); cursor: pointer; }
            .menu-toggle svg { width: 17px; height: 17px; }
            .topbar { justify-content: flex-start; padding: 0 18px; }
            .topbar-right { margin-left: auto; }
            .main { margin-left: 0; }
            .lower-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .topbar { min-height: 62px; padding: 0 13px; }
            .crumb { display: none; }
            .top-search { width: min(47vw, 210px); }
            .content { padding: 20px 13px 30px; }
            .welcome { align-items: flex-start; flex-direction: column; padding: 21px 17px; }
            .welcome-actions { width: 100%; }
            .welcome-actions .button { flex: 1; padding: 0 8px; }
            .stats { gap: 8px; }
            .stat-card { min-height: 100px; padding: 12px 10px; }
            .stat-label { align-items: flex-start; flex-direction: column-reverse; gap: 5px; font-size: 9px; }
            .stat-icon { width: 25px; height: 25px; }
            .stat-value { margin-top: 6px; font-size: 20px; }
            .stat-detail { font-size: 8px; line-height: 1.4; }
            .catalog { padding: 15px 12px; }
            .book-grid { grid-template-columns: 1fr; }
            .book-card { min-height: 138px; }
            .section-heading { align-items: flex-start; }
        }
    </style>
</head>
<body>
    <button class="sidebar-backdrop" type="button" aria-label="Close navigation" hidden></button>
    <aside class="sidebar" id="readerSidebar">
        <a class="brand" href="dashboard.php" aria-label="Oak and Ink reader dashboard">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-1.2 8-1z"/><path d="M12 6.5v13"/></svg>
            </span>
            <span><span class="brand-name">Oak &amp; Ink</span><span class="brand-caption">Community Library</span></span>
        </a>
        <nav aria-label="Reader navigation">
            <p class="nav-label">My library</p>
            <div class="nav-list">
                <a class="nav-link active" href="dashboard.php" aria-current="page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>
                    Dashboard
                </a>
                <a class="nav-link" href="borrow_book.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-1.2 8-1z"/></svg>
                    Browse books
                </a>
                <a class="nav-link" href="borrowed_books.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5h14v16l-7-4-7 4z"/><path d="M12 7v6M9 10h6"/></svg>
                    My borrowings
                </a>
            </div>
        </nav>
        <div class="sidebar-note">
            <strong>Make time to read</strong>
            <p>A few quiet pages can make an ordinary day a little more special.</p>
        </div>
        <?php require __DIR__ . '/../inc/logout_button.php'; ?>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open navigation" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="crumb">My library <span aria-hidden="true">/</span> Dashboard</div>
            <div class="topbar-right">
                <label class="top-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                    <input id="headerSearch" type="search" placeholder="Find a book..." aria-label="Find a book">
                </label>
                <span class="avatar" aria-label="Reader account">R</span>
            </div>
        </header>

        <div class="content">
            <section class="welcome">
                <div>
                    <p class="eyebrow"><?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?></p>
                    <h1>Welcome, reader <span aria-hidden="true">✦</span></h1>
                    <p class="welcome-copy">A good story is waiting for you. See what you might enjoy today.</p>
                </div>
                <div class="welcome-actions">
                    <a class="button primary" href="borrow_book.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                        Browse the collection
                    </a>
                </div>
            </section>

            <section class="stats" aria-label="Reader account overview">
                <article class="stat-card">
                    <div class="stat-label"><span>Books borrowed</span><span class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5h14v16l-7-4-7 4z"/><path d="M12 7v6M9 10h6"/></svg></span></div>
                    <div class="stat-value">—</div>
                    <div class="stat-detail">Your account activity will appear here</div>
                </article>
                <article class="stat-card">
                    <div class="stat-label"><span>Due soon</span><span class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3.5 2"/></svg></span></div>
                    <div class="stat-value">—</div>
                    <div class="stat-detail">Due dates need a linked account</div>
                </article>
                <article class="stat-card">
                    <div class="stat-label"><span>Saved for later</span><span class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5h14v16l-7-4-7 4z"/></svg></span></div>
                    <div class="stat-value">—</div>
                    <div class="stat-detail">Personal reading lists aren't connected yet</div>
                </article>
            </section>

            <section id="browse-books" aria-labelledby="browseTitle">
                <div class="section-heading">
                    <div>
                        <h2 class="section-title" id="browseTitle">Find your next read</h2>
                        <p class="section-subtitle">A few titles from the library collection</p>
                    </div>
                    <span class="text-link">Explore the shelves</span>
                </div>
                <div class="catalog">
                    <div class="catalog-toolbar">
                        <label class="catalog-search">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                            <input id="catalogSearch" type="search" placeholder="Search by title or author..." aria-label="Search sample books">
                        </label>
                        <span class="sample-label">Sample collection</span>
                    </div>
                    <div class="book-grid" id="bookGrid">
                        <article class="book-card" data-search="learning python goddie mavata thomas nelson">
                            <span class="book-cover" aria-hidden="true">Py</span>
                            <div class="book-info">
                                <span class="genre">Technology</span>
                                <h3 class="book-title">Learning Python</h3>
                                <p class="book-author">Goddie Mavata</p>
                                <span class="availability">19 of 20 available</span>
                                <a class="borrow-cta" href="borrow_book.php?book_id=1#book-1">Borrow this book</a>
                            </div>
                        </article>
                        <article class="book-card" data-search="doing technology book 8 calvin k b bamusi press">
                            <span class="book-cover" aria-hidden="true">D</span>
                            <div class="book-info">
                                <span class="genre">Technology</span>
                                <h3 class="book-title">Doing Technology Book 8</h3>
                                <p class="book-author">Calvin K. B</p>
                                <span class="availability">19 of 20 available</span>
                                <a class="borrow-cta" href="borrow_book.php?book_id=3#book-3">Borrow this book</a>
                            </div>
                        </article>
                        <article class="book-card" data-search="geography davids college press">
                            <span class="book-cover" aria-hidden="true">G</span>
                            <div class="book-info">
                                <span class="genre">Geography</span>
                                <h3 class="book-title">Geography</h3>
                                <p class="book-author">Davids</p>
                                <span class="availability">9 of 10 available</span>
                                <a class="borrow-cta" href="borrow_book.php?book_id=4#book-4">Borrow this book</a>
                            </div>
                        </article>
                    </div>
                    <div class="no-results" id="noResults">No sample titles match your search.</div>
                </div>
            </section>

            <section class="lower-grid">
                <article class="panel" id="my-loans">
                    <div class="section-heading">
                        <div>
                            <h2 class="section-title">My borrowings</h2>
                            <p class="section-subtitle">Keep track of books and due dates</p>
                        </div>
                        <a class="text-link" href="borrowed_books.php">View all →</a>
                    </div>
                    <div class="loan-empty">
                        <span class="loan-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5h14v16l-7-4-7 4z"/><path d="M12 7v6M9 10h6"/></svg>
                        </span>
                        <div>
                            <strong>Check your borrowed books</strong>
                            <p>See your checkouts, due dates, and returned-book history in one place.</p>
                        </div>
                    </div>
                    <a class="text-link" href="borrowed_books.php">View my borrowed books →</a>
                </article>
                <article class="panel">
                    <h2 class="section-title">How to borrow</h2>
                    <p class="section-subtitle">Your next library visit, made easy</p>
                    <div class="how-list">
                        <div class="how-item"><span class="step">1</span><span>Browse the shelves and choose a book that catches your eye.</span></div>
                        <div class="how-item"><span class="step">2</span><span>Choose an available title and select “Borrow this book.”</span></div>
                        <div class="how-item"><span class="step">3</span><span>Sign in with your member account; the checkout is recorded with a due date.</span></div>
                    </div>
                </article>
            </section>
            <p class="footer">Oak &amp; Ink Community Library &nbsp;·&nbsp; Happy reading</p>
        </div>
    </main>

    <script>
        const sidebar = document.getElementById('readerSidebar');
        const menuToggle = document.getElementById('menuToggle');
        const backdrop = document.querySelector('.sidebar-backdrop');
        const setMenuOpen = (isOpen) => {
            sidebar.classList.toggle('open', isOpen);
            menuToggle.setAttribute('aria-expanded', String(isOpen));
            menuToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            backdrop.hidden = !isOpen;
        };
        menuToggle.addEventListener('click', () => setMenuOpen(!sidebar.classList.contains('open')));
        backdrop.addEventListener('click', () => setMenuOpen(false));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setMenuOpen(false);
        });

        const catalogSearch = document.getElementById('catalogSearch');
        const headerSearch = document.getElementById('headerSearch');
        const cards = Array.from(document.querySelectorAll('.book-card'));
        const noResults = document.getElementById('noResults');
        const filterBooks = (query) => {
            const normalizedQuery = query.trim().toLowerCase();
            let visibleCards = 0;
            cards.forEach((card) => {
                const matches = card.dataset.search.includes(normalizedQuery);
                card.hidden = !matches;
                if (matches) visibleCards += 1;
            });
            noResults.style.display = visibleCards === 0 ? 'block' : 'none';
        };
        catalogSearch.addEventListener('input', () => {
            headerSearch.value = catalogSearch.value;
            filterBooks(catalogSearch.value);
        });
        headerSearch.addEventListener('input', () => {
            catalogSearch.value = headerSearch.value;
            filterBooks(headerSearch.value);
        });
    </script>
</body>
</html>