<?php
$sidebarPage = basename($_SERVER['PHP_SELF'] ?? '');
if (in_array($sidebarPage, ['add_book.php', 'edit_book.php', 'view_book.php'], true)) {
    $sidebarPage = 'books.php';
}
?>
<style>
    .librarian-sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        z-index: 30;
        width: 248px;
        display: flex;
        flex-direction: column;
        padding: 26px 16px 18px;
        color: #f7f1e7;
        background: #4b3528;
        font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
    }
    .librarian-sidebar a { color: inherit; text-decoration: none; }
    .librarian-sidebar-brand { display: flex; align-items: center; gap: 11px; padding: 0 9px 29px; }
    .librarian-sidebar-mark {
        width: 39px;
        height: 39px;
        display: grid;
        place-items: center;
        flex: 0 0 39px;
        border: 1px solid rgba(235, 218, 188, .4);
        border-radius: 12px;
        color: #e9d5b0;
        background: rgba(255,255,255,.055);
    }
    .librarian-sidebar-mark svg { width: 22px; height: 22px; }
    .librarian-sidebar-name { font-family: Georgia, "Times New Roman", serif; font-size: 18px; letter-spacing: -.3px; }
    .librarian-sidebar-caption { margin-top: 3px; color: #c9bbaa; font-size: 9px; letter-spacing: 1.2px; text-transform: uppercase; }
    .librarian-sidebar-label { padding: 0 11px; margin: 0 0 9px; color: #b9a998; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
    .librarian-sidebar-group + .librarian-sidebar-group { margin-top: 23px; }
    .librarian-sidebar-links { display: grid; gap: 5px; }
    .librarian-sidebar-link {
        min-height: 42px;
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 0 12px;
        border-radius: 8px;
        color: #d8cabb !important;
        font-size: 12px;
        transition: background .18s, color .18s;
    }
    .librarian-sidebar-link:hover { color: #fffaf1 !important; background: rgba(255,255,255,.075); }
    .librarian-sidebar-link[aria-current="page"] { color: #fffaf1 !important; background: #634a36; box-shadow: inset 3px 0 #d0b17e; }
    .librarian-sidebar-link svg { width: 17px; height: 17px; flex: 0 0 17px; opacity: .9; }
    .librarian-sidebar-bottom { margin-top: auto; }
    .librarian-sidebar-help { padding: 14px; border: 1px solid rgba(239,225,204,.16); border-radius: 10px; background: rgba(255,255,255,.045); }
    .librarian-sidebar-help strong { display: block; margin-bottom: 6px; font-family: Georgia, "Times New Roman", serif; font-size: 15px; font-weight: 400; }
    .librarian-sidebar-help p { margin: 0 0 10px; color: #cbbbaa; font-size: 10px; line-height: 1.55; }
    .librarian-sidebar-help a { color: #e7cb9d; font-size: 10px; font-weight: 600; }
    .librarian-sidebar-profile { display: flex; align-items: center; gap: 9px; padding: 16px 4px 0; margin-top: 15px; border-top: 1px solid rgba(239,225,204,.15); }
    .librarian-sidebar-avatar { width: 33px; height: 33px; display: grid; place-items: center; flex: 0 0 33px; border-radius: 50%; color: #4b3528; background: #e4c99e; font-family: Georgia, serif; font-size: 11px; font-weight: 700; }
    .librarian-sidebar-person { color: #f7f1e7; font-size: 11px; font-weight: 600; }
    .librarian-sidebar-role { margin-top: 3px; color: #c2b09f; font-size: 9px; }
    .librarian-sidebar-toggle, .librarian-sidebar-backdrop { display: none; }
    @media (min-width: 801px) {
        body { padding-left: 248px !important; }
    }
    @media (max-width: 800px) {
        .page { padding-top: 24px; }
        .librarian-sidebar {
            padding-top: 58px;
            box-shadow: 8px 0 30px rgba(35,25,18,.15);
            transform: translateX(-105%);
            transition: transform .22s ease;
        }
        .librarian-sidebar.open { transform: translateX(0); }
        .librarian-sidebar-toggle {
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 32;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border: 1px solid #dcd2c2;
            border-radius: 8px;
            color: #4b3528;
            background: #fffdf8;
            box-shadow: 0 3px 12px rgba(69, 51, 36, .08);
            cursor: pointer;
        }
        .librarian-sidebar-toggle svg { width: 18px; height: 18px; }
        .librarian-sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 29;
            display: block;
            border: 0;
            background: rgba(35, 25, 18, .28);
        }
        .librarian-sidebar-backdrop[hidden] { display: none; }
        body { padding-left: 20px !important; }
    }
</style>
<button class="librarian-sidebar-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="librarianSidebar">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>
<button class="librarian-sidebar-backdrop" type="button" aria-label="Close navigation" hidden></button>
<aside class="librarian-sidebar" id="librarianSidebar">
    <a class="librarian-sidebar-brand" href="dashboard.php" aria-label="Oak and Ink Library dashboard">
        <span class="librarian-sidebar-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.2-2.5 8-1z"/><path d="M12 6.5v13"/></svg>
        </span>
        <span><span class="librarian-sidebar-name">Oak &amp; Ink</span><span class="librarian-sidebar-caption">Community Library</span></span>
    </a>
    <nav aria-label="Librarian navigation">
        <div class="librarian-sidebar-group">
            <p class="librarian-sidebar-label">Workspace</p>
            <div class="librarian-sidebar-links">
                <a class="librarian-sidebar-link" href="dashboard.php"<?php echo $sidebarPage === 'dashboard.php' ? ' aria-current="page"' : ''; ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>
                    Dashboard
                </a>
                <a class="librarian-sidebar-link" href="books.php"<?php echo $sidebarPage === 'books.php' ? ' aria-current="page"' : ''; ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5c2.8-1.5 5.5-1.2 8 1v13c-2.5-2.2-5.2-2.5-8-1z"/><path d="M20 5.5c-2.8-1.5-5.5-1.2-8 1v13c2.5-2.2 5.5-1.2 8-1z"/></svg>
                    Book collection
                </a>
                <a class="librarian-sidebar-link" href="borrowings.php"<?php echo $sidebarPage === 'borrowings.php' ? ' aria-current="page"' : ''; ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5h14v16l-7-4-7 4z"/><path d="M12 7v6M9 10h6"/></svg>
                    Borrowings
                </a>
            </div>
        </div>
        <div class="librarian-sidebar-group">
            <p class="librarian-sidebar-label">Book tools</p>
            <div class="librarian-sidebar-links">
                <a class="librarian-sidebar-link" href="add_book.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add a new book
                </a>
            </div>
        </div>
    </nav>
    <div class="librarian-sidebar-bottom">
        <div class="librarian-sidebar-help">
            <strong>A little help?</strong>
            <p>Find quick answers and tips for your library workspace.</p>
            <a href="books.php">Browse the book collection →</a>
        </div>
        <div class="librarian-sidebar-profile">
            <span class="librarian-sidebar-avatar" aria-hidden="true">OR</span>
            <span><span class="librarian-sidebar-person">Olivia Reed</span><span class="librarian-sidebar-role">Head librarian</span></span>
        </div>
        <?php require __DIR__ . '/../../inc/logout_button.php'; ?>
    </div>
</aside>
<script>
    (() => {
        const sidebar = document.getElementById('librarianSidebar');
        const toggle = document.querySelector('.librarian-sidebar-toggle');
        const backdrop = document.querySelector('.librarian-sidebar-backdrop');
        if (!sidebar || !toggle || !backdrop) return;
        const setOpen = (isOpen) => {
            sidebar.classList.toggle('open', isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
            toggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            backdrop.hidden = !isOpen;
        };
        toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('open')));
        backdrop.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setOpen(false);
        });
    })();
</script>