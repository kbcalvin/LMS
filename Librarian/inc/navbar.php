<?php
$navbarPage = basename($_SERVER['PHP_SELF'] ?? '');
$navbarLinks = [
    'dashboard.php' => ['label' => 'Dashboard', 'href' => 'dashboard.php'],
    'books.php' => ['label' => 'Book collection', 'href' => 'books.php'],
    'borrowings.php' => ['label' => 'Borrowings', 'href' => 'borrowings.php'],
];
if (in_array($navbarPage, ['add_book.php', 'edit_book.php', 'view_book.php'], true)) {
    $navbarPage = 'books.php';
}
?>
<style>
    .librarian-navbar {
        position: relative;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        width: 100%;
        max-width: 1500px;
        min-height: 70px;
        padding: 11px 28px;
        margin: 0 auto 24px;
        border: 1px solid #e9e3d8;
        border-radius: 11px;
        color: #29251f;
        background: #fffdf8;
        box-shadow: 0 5px 20px rgba(69, 51, 36, .035);
        font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
    }
    .librarian-navbar a { color: inherit; text-decoration: none; }
    .librarian-navbar-brand { display: flex; align-items: center; gap: 10px; flex: 0 0 auto; }
    .librarian-navbar-mark {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 11px;
        color: #e9d5b0;
        background: #4b3528;
    }
    .librarian-navbar-mark svg { width: 21px; height: 21px; }
    .librarian-navbar-name { font-family: Georgia, "Times New Roman", serif; font-size: 17px; }
    .librarian-navbar-caption { margin-top: 3px; color: #8a8377; font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }
    .librarian-navbar-menu { display: flex; align-items: center; gap: 5px; }
    .librarian-navbar-link {
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        padding: 0 13px;
        border-radius: 7px;
        color: #6f665b !important;
        font-size: 12px;
        transition: color .18s, background .18s;
    }
    .librarian-navbar-link:hover { color: #4b3528 !important; background: #f5f0e6; }
    .librarian-navbar-link[aria-current="page"] { color: #fffaf3 !important; background: #4b3528; }
    .librarian-navbar-toggle {
        width: 38px;
        height: 38px;
        display: none;
        place-items: center;
        border: 1px solid #ebe5d9;
        border-radius: 8px;
        color: #4b3528;
        background: #fffdf8;
        cursor: pointer;
    }
    .librarian-navbar-toggle svg { width: 18px; height: 18px; }
    @media (max-width: 620px) {
        .librarian-navbar { min-height: 62px; padding: 10px 13px; margin-bottom: 22px; }
        .librarian-navbar-toggle { display: grid; }
        .librarian-navbar-menu {
            position: absolute;
            top: calc(100% + 7px);
            right: 0;
            left: 0;
            display: none;
            align-items: stretch;
            flex-direction: column;
            gap: 3px;
            padding: 7px;
            border: 1px solid #e9e3d8;
            border-radius: 9px;
            background: #fffdf8;
            box-shadow: 0 10px 24px rgba(69, 51, 36, .1);
        }
        .librarian-navbar-menu.open { display: flex; }
        .librarian-navbar-link { min-height: 42px; }
    }
</style>
<header class="librarian-navbar">
    <a class="librarian-navbar-brand" href="dashboard.php" aria-label="Oak and Ink Library dashboard">
        <span class="librarian-navbar-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.2-2.5 8-1z"/><path d="M12 6.5v13"/></svg>
        </span>
        <span><span class="librarian-navbar-name">Oak &amp; Ink</span><span class="librarian-navbar-caption">Community Library</span></span>
    </a>
    <button class="librarian-navbar-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="librarianNavigation">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <nav class="librarian-navbar-menu" id="librarianNavigation" aria-label="Main navigation">
        <?php foreach ($navbarLinks as $file => $link): ?>
            <a class="librarian-navbar-link" href="<?php echo htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $navbarPage === $file ? ' aria-current="page"' : ''; ?>><?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<script>
    (() => {
        const toggle = document.querySelector('.librarian-navbar-toggle');
        const menu = document.getElementById('librarianNavigation');
        if (!toggle || !menu) return;
        toggle.addEventListener('click', () => {
            const isOpen = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', String(isOpen));
            toggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
        });
    })();
</script>