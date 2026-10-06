<?php
session_start();
require __DIR__ . '/../inc/authorization.php';
require_library_role(['librarian'], '../auth/login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <title>Book collection | Oak &amp; Ink Library</title>
    <style>
        :root {
            --paper: #f6f2e9;
            --card: #fffdf8;
            --ink: #29251f;
            --muted: #8a8377;
            --line: #ebe5d9;
            --brown: #4b3528;
            --brown-light: #6f503b;
            --olive: #68764f;
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
        .topline { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 30px; }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand-mark { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; color: #e9d5b0; background: var(--brown); }
        .brand-mark svg { width: 21px; height: 21px; }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 17px; }
        .brand-caption { margin-top: 3px; color: var(--muted); font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }
        .top-actions { display: flex; align-items: center; gap: 9px; }
        .back-link { color: var(--brown-light); font-size: 11px; font-weight: 600; }
        .back-link:hover, .edit-link:hover, .view-link:hover { text-decoration: underline; }
        h1 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 32px; font-weight: 400; letter-spacing: -.5px; }
        .intro { margin: 8px 0 22px; color: var(--muted); font-size: 13px; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 15px; }
        .filters { display: flex; align-items: center; gap: 9px; }
        .search-wrap { position: relative; width: min(300px, 44vw); }
        .search-wrap svg { position: absolute; top: 11px; left: 11px; width: 16px; height: 16px; color: #92897d; }
        input, select { height: 39px; padding: 0 11px; border: 1px solid var(--line); border-radius: 7px; outline: none; color: var(--ink); background: var(--card); font: inherit; font-size: 12px; }
        .search-wrap input { width: 100%; padding-left: 35px; }
        select { min-width: 132px; }
        input:focus, select:focus { border-color: #b69a71; box-shadow: 0 0 0 3px rgba(182,154,113,.13); }
        .button { min-height: 39px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 0 14px; border: 1px solid var(--brown); border-radius: 7px; color: #fffaf3; background: var(--brown); font-size: 11px; font-weight: 600; white-space: nowrap; }
        .button:hover { background: #624632; }
        .button svg { width: 15px; height: 15px; }
        .catalog-card { overflow: hidden; border: 1px solid #eee8dd; border-radius: 12px; background: var(--card); box-shadow: 0 8px 28px rgba(69, 51, 36, .045); }
        .catalog-meta { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--line); color: #81796d; font-size: 10px; }
        .catalog-meta strong { color: #51483d; font-weight: 600; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; min-width: 670px; border-collapse: collapse; text-align: left; }
        th { padding: 12px 17px; border-bottom: 1px solid var(--line); color: #968e81; font-size: 9px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
        td { padding: 13px 17px; border-bottom: 1px solid #f0ece5; color: #625b51; font-size: 11px; }
        tbody tr:last-child td { border-bottom: 0; }
        .book-cell { display: flex; align-items: center; gap: 10px; min-width: 220px; }
        .book-cover { width: 31px; height: 40px; display: grid; place-items: center; flex: 0 0 31px; border-radius: 3px 5px 5px 3px; color: #f4e8d2; background: #795b42; box-shadow: inset 3px 0 rgba(0,0,0,.12); font-family: Georgia, serif; font-size: 11px; }
        tr:nth-child(2) .book-cover { color: #f8eddf; background: #778064; }
        tr:nth-child(3) .book-cover { color: #f7ebdc; background: #a76d51; }
        tr:nth-child(4) .book-cover { color: #f4eadb; background: #867151; }
        .book-title { color: #403930; font-size: 11px; font-weight: 600; }
        .book-author { margin-top: 4px; color: #9a9286; font-size: 10px; }
        .genre-tag { display: inline-flex; padding: 5px 8px; border-radius: 20px; color: #70614d; background: #f2ede3; font-size: 9px; white-space: nowrap; }
        .copies { color: #625b51; font-variant-numeric: tabular-nums; }
        .edit-link { color: #79593e; font-size: 10px; font-weight: 600; white-space: nowrap; }
        .book-actions { display: flex; align-items: center; gap: 11px; }
        .view-link { color: #79593e; font-size: 10px; font-weight: 600; white-space: nowrap; }
        .empty-state { display: none; padding: 35px 15px; color: var(--muted); text-align: center; font-size: 12px; }
        .note { margin: 14px 2px 0; color: #938a7d; font-size: 10px; line-height: 1.55; }
        @media (max-width: 600px) {
            body { padding: 24px 14px; }
            .topline { align-items: flex-start; margin-bottom: 26px; }
            .top-actions { align-items: flex-end; flex-direction: column-reverse; }
            h1 { font-size: 28px; }
            .toolbar { align-items: stretch; flex-direction: column; }
            .filters { display: grid; grid-template-columns: 1fr 1fr; }
            .search-wrap { width: 100%; grid-column: 1 / -1; }
            select { width: 100%; }
            .toolbar > .button { align-self: flex-start; }
            .catalog-meta { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/inc/sidebar.php'; ?>
    <main class="page">
        <h1>Book collection</h1>
        <p class="intro">Browse and manage books represented in the library catalog.</p>

        <div class="toolbar">
            <div class="filters">
                <label class="search-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                    <input id="bookSearch" type="search" placeholder="Search titles or authors..." aria-label="Search books">
                </label>
                <select id="genreFilter" aria-label="Filter by genre">
                    <option value="">All genres</option>
                    <option value="fiction">Fiction</option>
                    <option value="mystery & thriller">Mystery &amp; thriller</option>
                    <option value="history">History</option>
                    <option value="children's">Children's</option>
                    <option value="biography">Biography</option>
                    <option value="science">Science</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <a class="button" href="add_book.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Add a new book
            </a>
        </div>

        <section class="catalog-card" aria-label="Books">
            <div class="catalog-meta"><span><strong id="bookCount">4 titles</strong> shown</span><span>Demonstration catalog</span></div>
            <div class="table-wrap">
                <table id="booksTable">
                    <thead><tr><th>Book</th><th>Genre</th><th>Copies listed</th><th>Actions</th></tr></thead>
                    <tbody>
                        <tr data-book-id="wind-willows" data-genre="fiction"><td><div class="book-cell"><span class="book-cover">W</span><span><span class="book-title">The Wind in the Willows</span><span class="book-author">Kenneth Grahame</span></span></div></td><td><span class="genre-tag">Fiction</span></td><td class="copies">1</td><td><div class="book-actions"><a class="view-link" href="view_book.php?id=wind-willows">View details</a><a class="edit-link" href="edit_book.php?id=wind-willows">Edit</a></div></td></tr>
                        <tr data-book-id="garden-evening-mists" data-genre="fiction"><td><div class="book-cell"><span class="book-cover">G</span><span><span class="book-title">The Garden of Evening Mists</span><span class="book-author">Tan Twan Eng</span></span></div></td><td><span class="genre-tag">Fiction</span></td><td class="copies">1</td><td><div class="book-actions"><a class="view-link" href="view_book.php?id=garden-evening-mists">View details</a><a class="edit-link" href="edit_book.php?id=garden-evening-mists">Edit</a></div></td></tr>
                        <tr data-book-id="small-things-like-these" data-genre="fiction"><td><div class="book-cell"><span class="book-cover">S</span><span><span class="book-title">Small Things Like These</span><span class="book-author">Claire Keegan</span></span></div></td><td><span class="genre-tag">Fiction</span></td><td class="copies">1</td><td><div class="book-actions"><a class="view-link" href="view_book.php?id=small-things-like-these">View details</a><a class="edit-link" href="edit_book.php?id=small-things-like-these">Edit</a></div></td></tr>
                        <tr data-book-id="history-world" data-genre="history"><td><div class="book-cell"><span class="book-cover">H</span><span><span class="book-title">A History of the World</span><span class="book-author">Andrew Marr</span></span></div></td><td><span class="genre-tag">History</span></td><td class="copies">1</td><td><div class="book-actions"><a class="view-link" href="view_book.php?id=history-world">View details</a><a class="edit-link" href="edit_book.php?id=history-world">Edit</a></div></td></tr>
                    </tbody>
                </table>
                <div class="empty-state" id="emptyState">No books match your search.</div>
            </div>
        </section>
        <p class="note">This demonstration catalog lists the four sample titles currently represented in the librarian dashboard. The project does not yet have a shared book database.</p>
    </main>
    <script>
        const rows = Array.from(document.querySelectorAll('#booksTable tbody tr'));
        const search = document.getElementById('bookSearch');
        const genreFilter = document.getElementById('genreFilter');
        const bookCount = document.getElementById('bookCount');
        const emptyState = document.getElementById('emptyState');

        try {
            const savedBooks = JSON.parse(localStorage.getItem('oakInkBookEdits') || '{}');
            rows.forEach((row) => {
                const book = savedBooks[row.dataset.bookId];
                if (!book) return;
                const title = row.querySelector('.book-title');
                const author = row.querySelector('.book-author');
                const genre = row.querySelector('.genre-tag');
                const copies = row.querySelector('.copies');
                if (title && typeof book.title === 'string') title.textContent = book.title;
                if (author && typeof book.author === 'string') author.textContent = book.author;
                if (genre && typeof book.genre === 'string') {
                    genre.textContent = book.genre;
                    row.dataset.genre = book.genre.toLowerCase();
                }
                if (copies && typeof book.copies === 'string') copies.textContent = book.copies;
            });
        } catch (error) {
            console.error('Unable to load saved book edits from this browser.', error);
        }

        function filterBooks() {
            const query = search.value.trim().toLowerCase();
            const selectedGenre = genreFilter.value;
            let visible = 0;
            rows.forEach((row) => {
                const matchesQuery = row.textContent.toLowerCase().includes(query);
                const matchesGenre = !selectedGenre || row.dataset.genre === selectedGenre;
                row.hidden = !(matchesQuery && matchesGenre);
                if (!row.hidden) visible += 1;
            });
            bookCount.textContent = `${visible} ${visible === 1 ? 'title' : 'titles'}`;
            emptyState.style.display = visible === 0 ? 'block' : 'none';
        }

        search.addEventListener('input', filterBooks);
        genreFilter.addEventListener('change', filterBooks);
        filterBooks();
    </script>
</body>
</html>