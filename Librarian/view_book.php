<?php
session_start();
require __DIR__ . '/../inc/authorization.php';
require_library_role(['librarian'], '../auth/login.php');
$books = [
    'wind-willows' => [
        'title' => 'The Wind in the Willows',
        'author' => 'Kenneth Grahame',
        'isbn' => '',
        'genre' => 'Fiction',
        'publisher' => '',
        'year' => '',
        'copies' => '1',
        'notes' => '',
    ],
    'garden-evening-mists' => [
        'title' => 'The Garden of Evening Mists',
        'author' => 'Tan Twan Eng',
        'isbn' => '',
        'genre' => 'Fiction',
        'publisher' => '',
        'year' => '',
        'copies' => '1',
        'notes' => '',
    ],
    'small-things-like-these' => [
        'title' => 'Small Things Like These',
        'author' => 'Claire Keegan',
        'isbn' => '',
        'genre' => 'Fiction',
        'publisher' => '',
        'year' => '',
        'copies' => '1',
        'notes' => '',
    ],
    'history-world' => [
        'title' => 'A History of the World',
        'author' => 'Andrew Marr',
        'isbn' => '',
        'genre' => 'History',
        'publisher' => '',
        'year' => '',
        'copies' => '1',
        'notes' => '',
    ],
];
$bookId = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '';
$book = $books[$bookId] ?? null;
$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};
$display = static function (string $value) use ($escape): string {
    return $value === '' ? 'Not provided' : $escape($value);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <title><?php echo $book === null ? 'Book not found' : $escape($book['title']); ?> | Oak &amp; Ink Library</title>
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
        .page { width: min(850px, 100%); margin: 0 auto; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 30px; }
        .brand-mark { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; color: #e9d5b0; background: var(--brown); }
        .brand-mark svg { width: 21px; height: 21px; }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 17px; }
        .brand-caption { margin-top: 3px; color: var(--muted); font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }
        .back-link { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 17px; color: var(--brown-light); font-size: 12px; font-weight: 600; }
        .back-link:hover, .text-link:hover { text-decoration: underline; }
        .book-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 22px; }
        .eyebrow { margin: 0 0 8px; color: #978b79; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: clamp(27px, 4vw, 35px); font-weight: 400; letter-spacing: -.5px; }
        .author { margin: 8px 0 0; color: var(--muted); font-size: 13px; }
        .actions { display: flex; flex: 0 0 auto; gap: 9px; }
        .button { min-height: 39px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 0 14px; border: 1px solid #dcd2c2; border-radius: 7px; color: var(--brown); background: var(--card); font-size: 11px; font-weight: 600; }
        .button:hover { background: #faf6ed; }
        .button.primary { border-color: var(--brown); color: #fffaf3; background: var(--brown); }
        .button.primary:hover { background: #624632; }
        .button svg { width: 15px; height: 15px; }
        .details-card { overflow: hidden; border: 1px solid #eee8dd; border-radius: 13px; background: var(--card); box-shadow: 0 8px 28px rgba(69, 51, 36, .045); }
        .quantity-panel { display: grid; grid-template-columns: repeat(3, 1fr); border-bottom: 1px solid var(--line); background: #fbf8f1; }
        .quantity-item { padding: 19px 22px; }
        .quantity-item + .quantity-item { border-left: 1px solid var(--line); }
        .quantity-value { color: var(--brown); font-family: Georgia, "Times New Roman", serif; font-size: 25px; }
        .quantity-label { margin-top: 5px; color: #81796d; font-size: 10px; }
        .detail-content { padding: 23px; }
        .section-title { margin: 0 0 16px; font-family: Georgia, "Times New Roman", serif; font-size: 17px; font-weight: 400; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 22px; }
        .detail { min-height: 67px; padding: 14px 0; border-bottom: 1px solid #f0ece5; }
        .detail-label { margin-bottom: 7px; color: #968e81; font-size: 9px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
        .detail-value { overflow-wrap: anywhere; color: #51483d; font-size: 12px; line-height: 1.55; white-space: pre-wrap; }
        .inventory-note { display: flex; align-items: flex-start; gap: 8px; padding: 12px 13px; margin-top: 19px; border-radius: 7px; color: #756b5e; background: #f5f0e6; font-size: 10px; line-height: 1.55; }
        .inventory-note svg { width: 15px; height: 15px; flex: 0 0 15px; color: var(--olive); }
        .not-found { padding: 22px; border: 1px solid #eee8dd; border-radius: 12px; color: #675d50; background: var(--card); line-height: 1.6; }
        @media (max-width: 600px) {
            body { padding: 24px 14px; }
            .brand { margin-bottom: 24px; }
            .book-header { flex-direction: column; }
            .actions { width: 100%; }
            .actions .button { flex: 1; padding: 0 8px; }
            .quantity-item { padding: 16px 12px; }
            .quantity-value { font-size: 22px; }
            .quantity-label { font-size: 9px; line-height: 1.4; }
            .detail-content { padding: 18px 15px; }
            .detail-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/inc/sidebar.php'; ?>
    <main class="page">
        <a class="back-link" href="books.php"><span aria-hidden="true">←</span> Back to book collection</a>
        <?php if ($book === null): ?>
            <h1>Book not found</h1>
            <p class="author">Choose a title from the book collection to view its details.</p>
            <div class="not-found"><a class="back-link" href="books.php">Return to book collection →</a></div>
        <?php else: ?>
            <header class="book-header">
                <div>
                    <p class="eyebrow">Book details</p>
                    <h1 id="bookTitle"><?php echo $escape($book['title']); ?></h1>
                    <p class="author">by <span id="bookAuthor"><?php echo $escape($book['author']); ?></span></p>
                </div>
                <div class="actions">
                    <a class="button" href="edit_book.php?id=<?php echo rawurlencode($bookId); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"/></svg>
                        Edit book
                    </a>
                    <a class="button primary" href="books.php">All books</a>
                </div>
            </header>

            <section class="details-card" aria-label="Book information">
                <div class="quantity-panel" aria-label="Collection quantity">
                    <div class="quantity-item"><div class="quantity-value" id="bookCopies"><?php echo $escape($book['copies']); ?></div><div class="quantity-label">Copies in collection</div></div>
                    <div class="quantity-item"><div class="quantity-value">—</div><div class="quantity-label">Copies available</div></div>
                    <div class="quantity-item"><div class="quantity-value">—</div><div class="quantity-label">Copies on loan</div></div>
                </div>
                <div class="detail-content">
                    <h2 class="section-title">Catalog information</h2>
                    <div class="detail-grid">
                        <div class="detail"><div class="detail-label">Title</div><div class="detail-value" id="detailTitle"><?php echo $escape($book['title']); ?></div></div>
                        <div class="detail"><div class="detail-label">Author</div><div class="detail-value" id="detailAuthor"><?php echo $escape($book['author']); ?></div></div>
                        <div class="detail"><div class="detail-label">ISBN</div><div class="detail-value" id="detailIsbn"><?php echo $display($book['isbn']); ?></div></div>
                        <div class="detail"><div class="detail-label">Genre</div><div class="detail-value" id="detailGenre"><?php echo $escape($book['genre']); ?></div></div>
                        <div class="detail"><div class="detail-label">Publisher</div><div class="detail-value" id="detailPublisher"><?php echo $display($book['publisher']); ?></div></div>
                        <div class="detail"><div class="detail-label">Publication year</div><div class="detail-value" id="detailYear"><?php echo $display($book['year']); ?></div></div>
                        <div class="detail"><div class="detail-label">Copies in collection</div><div class="detail-value" id="detailCopies"><?php echo $escape($book['copies']); ?></div></div>
                        <div class="detail"><div class="detail-label">Catalog ID</div><div class="detail-value"><?php echo $escape($bookId); ?></div></div>
                        <div class="detail"><div class="detail-label">Notes</div><div class="detail-value" id="detailNotes"><?php echo $display($book['notes']); ?></div></div>
                    </div>
                    <p class="inventory-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                        The total copy quantity comes from this book's catalog details. Available and on-loan counts are not tracked because circulation records are not connected to a shared inventory.
                    </p>
                </div>
            </section>
            <script>
                const bookId = <?php echo json_encode($bookId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                const savedDetailFields = {
                    title: ['detailTitle', 'bookTitle'],
                    author: ['detailAuthor', 'bookAuthor'],
                    isbn: ['detailIsbn'],
                    genre: ['detailGenre'],
                    publisher: ['detailPublisher'],
                    year: ['detailYear'],
                    copies: ['detailCopies', 'bookCopies'],
                    notes: ['detailNotes']
                };
                try {
                    const savedBooks = JSON.parse(localStorage.getItem('oakInkBookEdits') || '{}');
                    const savedBook = savedBooks[bookId];
                    if (savedBook && typeof savedBook === 'object') {
                        Object.entries(savedDetailFields).forEach(([field, elementIds]) => {
                            if (typeof savedBook[field] !== 'string') return;
                            elementIds.forEach((elementId) => {
                                const element = document.getElementById(elementId);
                                if (element) element.textContent = savedBook[field] || 'Not provided';
                            });
                        });
                    }
                } catch (error) {
                    console.error('Unable to load saved book details from this browser.', error);
                }
            </script>
        <?php endif; ?>
    </main>
</body>
</html>