<?php
session_start();

$sessionUserId = $_SESSION['user_id'] ?? null;
$sessionRole = $_SESSION['role'] ?? null;
if ((is_string($sessionUserId) || is_int($sessionUserId)) &&
    filter_var($sessionUserId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false &&
    in_array($sessionRole, ['user', 'librarian'], true)) {
    $dashboard = $sessionRole === 'librarian'
        ? 'Librarian/dashboard.php'
        : 'User/dashboard.php';
    header('Location: ' . $dashboard);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <meta name="description" content="Oak &amp; Ink is your community library for discovering books, borrowing with ease, and keeping your reading in order.">
    <title>Oak &amp; Ink Community Library</title>
    <style>
        :root {
            color-scheme: light;
            --paper: #f6f2e9;
            --card: #fffdf8;
            --ink: #29251f;
            --muted: #81786c;
            --line: #e8dfd1;
            --brown: #4b3528;
            --brown-light: #6f503b;
            --gold: #c5a46d;
            --olive: #77805e;
        }
        * { box-sizing: border-box; }
        body {
            min-width: 320px;
            margin: 0;
            color: var(--ink);
            background:
                linear-gradient(rgba(246,242,233,.84), rgba(246,242,233,.88)),
                url("auth/uploads/lib.jpg") center / cover fixed;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .page-shell { width: min(1120px, calc(100% - 48px)); margin: 0 auto; }
        .site-header { display: flex; min-height: 82px; align-items: center; justify-content: space-between; gap: 24px; border-bottom: 1px solid rgba(75,53,40,.12); }
        .brand { display: inline-flex; align-items: center; gap: 11px; }
        .brand-mark { display: grid; width: 40px; height: 40px; flex: 0 0 40px; place-items: center; border-radius: 12px; color: #ead6b3; background: var(--brown); }
        .brand-mark svg { width: 22px; height: 22px; }
        .brand-name { display: block; font-family: Georgia, "Times New Roman", serif; font-size: 18px; }
        .brand-caption { display: block; margin-top: 3px; color: var(--muted); font-size: 9px; letter-spacing: 1.1px; text-transform: uppercase; }
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .text-link { color: var(--brown); font-size: 12px; font-weight: 600; }
        .text-link:hover { text-decoration: underline; text-underline-offset: 4px; }
        .button { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 9px; padding: 0 17px; border: 1px solid transparent; border-radius: 7px; font-size: 11px; font-weight: 650; transition: transform .18s, background .18s, border-color .18s; }
        .button:hover { transform: translateY(-1px); }
        .button-primary { color: #fffaf3; background: var(--brown); }
        .button-primary:hover { background: #624632; }
        .button-outline { border-color: #d9cbb7; color: var(--brown); background: var(--card); }
        .button-outline:hover { border-color: #bca989; background: #faf6ed; }
        .hero { display: grid; min-height: 495px; grid-template-columns: 1.08fr .92fr; align-items: center; gap: 62px; padding: 62px 0 68px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; margin: 0 0 19px; color: var(--olive); font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
        .eyebrow::before { width: 22px; height: 1px; background: var(--gold); content: ""; }
        h1 { max-width: 580px; margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: clamp(43px, 5.5vw, 68px); font-weight: 400; letter-spacing: -2.1px; line-height: 1.05; }
        h1 em { color: var(--brown-light); font-weight: 400; }
        .hero-copy { max-width: 460px; margin: 20px 0 27px; color: var(--muted); font-size: 14px; line-height: 1.8; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 11px; }
        .welcome-note { display: flex; align-items: center; gap: 9px; margin-top: 23px; color: #8a8175; font-size: 10px; }
        .welcome-note svg { width: 16px; height: 16px; color: var(--olive); }
        .art-card { position: relative; display: grid; min-height: 330px; overflow: hidden; place-items: center; border: 1px solid #e9decd; border-radius: 18px; background: linear-gradient(145deg, #efe5d4, #e5d6be); }
        .art-card::before, .art-card::after { position: absolute; border: 1px solid rgba(111,80,59,.12); border-radius: 50%; content: ""; }
        .art-card::before { width: 280px; height: 280px; }
        .art-card::after { width: 220px; height: 220px; }
        .book-illustration { position: relative; z-index: 1; width: min(100%, 340px); height: auto; }
        .art-caption { position: absolute; right: 18px; bottom: 17px; left: 18px; display: flex; align-items: center; justify-content: space-between; color: #6e5b49; font-size: 9px; letter-spacing: .6px; text-transform: uppercase; }
        .art-caption span:last-child { color: #897b68; }
        .section-heading { display: flex; align-items: end; justify-content: space-between; gap: 24px; padding: 0 0 17px; border-bottom: 1px solid var(--line); }
        .section-heading h2 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 25px; font-weight: 400; }
        .section-heading p { margin: 0; color: var(--muted); font-size: 11px; }
        .features { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 15px; padding: 20px 0 59px; }
        .feature-card { min-height: 154px; padding: 20px; border: 1px solid var(--line); border-radius: 10px; background: rgba(255,253,248,.72); }
        .feature-icon { display: grid; width: 35px; height: 35px; place-items: center; border-radius: 9px; color: var(--brown-light); background: #f1e9dc; }
        .feature-icon svg { width: 18px; height: 18px; }
        .feature-card h3 { margin: 15px 0 6px; font-family: Georgia, "Times New Roman", serif; font-size: 16px; font-weight: 400; }
        .feature-card p { margin: 0; color: var(--muted); font-size: 10px; line-height: 1.65; }
        .site-footer { display: flex; justify-content: space-between; gap: 16px; padding: 18px 0 24px; border-top: 1px solid var(--line); color: #948a7d; font-size: 9px; }
        @media (max-width: 760px) {
            .hero { min-height: 0; grid-template-columns: 1fr; gap: 34px; padding: 53px 0 44px; }
            .hero-copy { max-width: 540px; }
            .art-card { min-height: 280px; }
            .book-illustration { width: min(100%, 310px); }
        }
        @media (max-width: 560px) {
            .page-shell { width: min(100% - 32px, 440px); }
            .site-header { min-height: 72px; gap: 10px; }
            .header-actions { gap: 12px; }
            .header-actions .button { min-height: 36px; padding: 0 11px; }
            .brand-mark { width: 36px; height: 36px; flex-basis: 36px; }
            .brand-name { font-size: 16px; }
            .brand-caption { font-size: 8px; }
            h1 { font-size: clamp(42px, 12vw, 56px); letter-spacing: -1.4px; }
            .hero { gap: 27px; padding-top: 42px; }
            .hero-copy { margin: 16px 0 22px; font-size: 13px; }
            .hero-actions { display: grid; grid-template-columns: 1fr 1fr; }
            .hero-actions .button { padding: 0 10px; }
            .art-card { min-height: 244px; }
            .section-heading { align-items: start; flex-direction: column; gap: 7px; }
            .features { grid-template-columns: 1fr; gap: 10px; padding-bottom: 38px; }
            .feature-card { min-height: 0; }
            .feature-card h3 { margin-top: 11px; }
            .site-footer { flex-direction: column; gap: 6px; }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header class="site-header">
            <a class="brand" href="index.php" aria-label="Oak and Ink Community Library home">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-2.5 8-1z"/><path d="M12 6.5v13"/></svg>
                </span>
                <span><span class="brand-name">Oak &amp; Ink</span><span class="brand-caption">Community Library</span></span>
            </a>
            <nav class="header-actions" aria-label="Account">
                <a class="text-link" href="auth/login.php">Log in</a>
                <a class="button button-outline" href="auth/register.php">Join the library</a>
            </nav>
        </header>

        <main>
            <section class="hero" aria-labelledby="welcomeHeading">
                <div>
                    <p class="eyebrow">A little more room to read</p>
                    <h1 id="welcomeHeading">Good books.<br><em>Good company.</em></h1>
                    <p class="hero-copy">Find your next favorite, borrow books with ease, and keep your reading life in one welcoming place. Your community library is always open for a new chapter.</p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="auth/register.php">
                            Create a free account
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                        <a class="button button-outline" href="auth/login.php">Member or librarian login</a>
                    </div>
                    <p class="welcome-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-8-4.4-8-11a4.5 4.5 0 0 1 8-2.9A4.5 4.5 0 0 1 20 10c0 6.6-8 11-8 11z"/></svg>
                        A friendly place for curious readers of every age.
                    </p>
                </div>
                <div class="art-card" aria-label="Illustration of a stack of books">
                    <svg class="book-illustration" viewBox="0 0 360 270" fill="none" aria-hidden="true">
                        <ellipse cx="181" cy="231" rx="116" ry="13" fill="#8D755A" fill-opacity=".14"/>
                        <path d="M89 193h181v29H89z" fill="#72533D"/>
                        <path d="M99 198h160v4H99z" fill="#C6A775"/>
                        <path d="M103 145h158v48H103z" fill="#A65F48"/>
                        <path d="M113 150h138v5H113z" fill="#D29B72"/>
                        <path d="M115 155v32m133-32v32" stroke="#E8C8A6" stroke-opacity=".72" stroke-width="2"/>
                        <path d="M112 109h143a7 7 0 0 1 7 7v29H105v-29a7 7 0 0 1 7-7z" fill="#E9DDC8"/>
                        <path d="M112 109h143v6H112z" fill="#B29A75"/>
                        <path d="M126 115v34m115-34v34" stroke="#B9A482" stroke-opacity=".55" stroke-width="2"/>
                        <path d="M119 77h128v32H119z" fill="#566348"/>
                        <path d="M119 77h128v5H119z" fill="#A6A982"/>
                        <path d="M129 83v22m108-22v22" stroke="#D9D5AF" stroke-opacity=".7" stroke-width="2"/>
                        <path d="M142 76c2-22 20-38 41-38s39 16 41 38h-82z" fill="#F5EFE2"/>
                        <path d="M143 76c2-22 19-38 40-38 11 0 21 4 28 11-15 2-27 14-29 27h-39z" fill="#FFFDF8"/>
                        <path d="M131 67h89" stroke="#C6A775" stroke-width="2"/>
                        <circle cx="216" cy="50" r="3" fill="#C6A775"/>
                        <path d="M84 222h192" stroke="#8D755A" stroke-opacity=".3" stroke-width="2" stroke-linecap="round"/>
                        <path d="M76 119c-8-14-7-30 4-42m195 21c8-10 10-22 6-34" stroke="#9A9E78" stroke-opacity=".75" stroke-width="2" stroke-linecap="round"/>
                        <path d="M78 106c-9-3-13-10-12-18 9 2 14 8 12 18zm201-24c8-7 10-15 7-23-8 5-11 13-7 23z" fill="#87906B"/>
                    </svg>
                    <div class="art-caption"><span>Open a new chapter</span><span>Since day one, for everyone</span></div>
                </div>
            </section>

            <section aria-labelledby="featuresHeading">
                <div class="section-heading">
                    <h2 id="featuresHeading">Your library, made simple</h2>
                    <p>Everything you need for a smoother reading journey.</p>
                </div>
                <div class="features">
                    <article class="feature-card">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-2.5 8-1z"/><path d="M12 6.5v13"/></svg>
                        </span>
                        <h3>Discover your next read</h3>
                        <p>Explore the collection and see what is ready to come home with you.</p>
                    </article>
                    <article class="feature-card">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/><path d="M16 3h5v5M10 14 21 3"/><path d="M8 8h3"/></svg>
                        </span>
                        <h3>Borrow without the fuss</h3>
                        <p>Request available books and keep track of due dates from your account.</p>
                    </article>
                    <article class="feature-card">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg>
                        </span>
                        <h3>Stay in the know</h3>
                        <p>See your borrowing history and return books when you are finished.</p>
                    </article>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <span>Oak &amp; Ink Community Library</span>
            <span>Made for readers, supported by librarians.</span>
        </footer>
    </div>
</body>
</html>