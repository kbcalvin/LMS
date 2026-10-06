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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f2e9">
    <title>Edit book | Oak &amp; Ink Library</title>
    <style>
        :root {
            --paper: #f6f2e9;
            --card: #fffdf8;
            --ink: #29251f;
            --muted: #8a8377;
            --line: #ebe5d9;
            --brown: #4b3528;
            --brown-light: #6f503b;
            --green: #62704f;
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
        .page { width: min(760px, 100%); margin: 0 auto; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 30px; }
        .brand-mark {
            width: 38px; height: 38px; display: grid; place-items: center;
            border-radius: 11px; color: #e9d5b0; background: var(--brown);
        }
        .brand-mark svg { width: 21px; height: 21px; }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 17px; }
        .brand-caption { margin-top: 3px; color: var(--muted); font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }
        .back-link { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 17px; color: var(--brown-light); font-size: 12px; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
        h1 { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 32px; font-weight: 400; letter-spacing: -.5px; }
        .intro { margin: 8px 0 24px; color: var(--muted); font-size: 13px; }
        .form-card { padding: 25px; border: 1px solid #eee8dd; border-radius: 13px; background: var(--card); box-shadow: 0 8px 28px rgba(69, 51, 36, .045); }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 16px; }
        .field { display: grid; gap: 7px; }
        .field.full { grid-column: 1 / -1; }
        label { color: #51483d; font-size: 11px; font-weight: 600; }
        label span { color: #a69b8b; font-weight: 400; }
        input, select, textarea {
            width: 100%; min-height: 41px; padding: 10px 11px;
            border: 1px solid var(--line); border-radius: 7px; outline: none;
            color: var(--ink); background: #fffefa; font: inherit; font-size: 12px;
        }
        textarea { min-height: 92px; resize: vertical; }
        input:focus, select:focus, textarea:focus { border-color: #b69a71; box-shadow: 0 0 0 3px rgba(182,154,113,.13); }
        input::placeholder, textarea::placeholder { color: #b0a89d; }
        .form-note { margin: 18px 0 0; padding: 11px 12px; border-radius: 7px; color: #756b5e; background: #f5f0e6; font-size: 11px; line-height: 1.5; }
        .form-note.success { color: #536343; background: #edf0e7; }
        .form-note.error { color: var(--red); background: #f7ebe4; }
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; padding-top: 21px; margin-top: 21px; border-top: 1px solid var(--line); }
        .button { min-height: 39px; display: inline-flex; align-items: center; justify-content: center; padding: 0 15px; border: 1px solid var(--line); border-radius: 7px; color: #6f665b; background: transparent; font: inherit; font-size: 12px; cursor: pointer; }
        .button.primary { border-color: var(--brown); color: #fffaf3; background: var(--brown); font-weight: 600; }
        .button.primary:hover { background: #624632; }
        .not-found { padding: 22px; border: 1px solid #eee8dd; border-radius: 12px; color: #675d50; background: var(--card); line-height: 1.6; }
        @media (max-width: 560px) {
            body { padding: 24px 14px; }
            .brand { margin-bottom: 24px; }
            h1 { font-size: 28px; }
            .form-card { padding: 18px 15px; }
            .form-grid { grid-template-columns: 1fr; gap: 15px; }
            .field.full { grid-column: auto; }
            .form-actions { display: grid; grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/inc/sidebar.php'; ?>
    <main class="page">
        <?php if ($book === null): ?>
            <h1>Book not found</h1>
            <p class="intro">Choose a book from the dashboard to edit its details.</p>
            <div class="not-found"><a class="back-link" href="dashboard.php">Return to dashboard →</a></div>
        <?php else: ?>
            <h1>Edit book</h1>
            <p class="intro">Update the catalog details for this title.</p>

            <form class="form-card" id="editBookForm">
                <input type="hidden" name="id" value="<?php echo $escape($bookId); ?>">
                <div class="form-grid">
                    <div class="field full">
                        <label for="title">Book title</label>
                        <input id="title" name="title" type="text" value="<?php echo $escape($book['title']); ?>" required>
                    </div>
                    <div class="field">
                        <label for="author">Author</label>
                        <input id="author" name="author" type="text" value="<?php echo $escape($book['author']); ?>" required>
                    </div>
                    <div class="field">
                        <label for="isbn">ISBN <span>(optional)</span></label>
                        <input id="isbn" name="isbn" type="text" value="<?php echo $escape($book['isbn']); ?>" placeholder="ISBN-13">
                    </div>
                    <div class="field">
                        <label for="genre">Genre</label>
                        <select id="genre" name="genre" required>
                            <?php foreach (['Fiction', 'Mystery & thriller', 'History', 'Children\'s', 'Biography', 'Science', 'Other'] as $genre): ?>
                                <option value="<?php echo $escape($genre); ?>"<?php echo $book['genre'] === $genre ? ' selected' : ''; ?>><?php echo $escape($genre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="publisher">Publisher <span>(optional)</span></label>
                        <input id="publisher" name="publisher" type="text" value="<?php echo $escape($book['publisher']); ?>" placeholder="Publisher name">
                    </div>
                    <div class="field">
                        <label for="year">Publication year <span>(optional)</span></label>
                        <input id="year" name="year" type="number" min="1" max="2100" value="<?php echo $escape($book['year']); ?>" placeholder="e.g. 2024">
                    </div>
                    <div class="field">
                        <label for="copies">Number of copies</label>
                        <input id="copies" name="copies" type="number" min="1" value="<?php echo $escape($book['copies']); ?>" required>
                    </div>
                    <div class="field full">
                        <label for="notes">Notes <span>(optional)</span></label>
                        <textarea id="notes" name="notes" placeholder="Add a short description or catalog note"><?php echo $escape($book['notes']); ?></textarea>
                    </div>
                </div>
                <p class="form-note" id="storageNotice" role="status">Edits are saved in this browser only; they are not written to a shared library catalog.</p>
                <div class="form-actions">
                    <a class="button" href="dashboard.php">Cancel</a>
                    <button class="button primary" type="submit">Save changes</button>
                </div>
            </form>
            <script>
                const form = document.getElementById('editBookForm');
                const notice = document.getElementById('storageNotice');
                const bookId = form.elements.id.value;
                const fields = ['title', 'author', 'isbn', 'genre', 'publisher', 'year', 'copies', 'notes'];
                try {
                    const savedBooks = JSON.parse(localStorage.getItem('oakInkBookEdits') || '{}');
                    const savedBook = savedBooks[bookId];
                    if (savedBook) {
                        fields.forEach((field) => {
                            if (typeof savedBook[field] === 'string') form.elements[field].value = savedBook[field];
                        });
                    }
                } catch (error) {
                    console.error('Unable to load saved book edits from this browser.', error);
                    notice.classList.add('error');
                    notice.textContent = 'Saved edits could not be loaded from this browser. You can still edit the fields, but saving may not work.';
                }
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    const editedBook = {};
                    fields.forEach((field) => { editedBook[field] = form.elements[field].value.trim(); });
                    try {
                        const savedBooks = JSON.parse(localStorage.getItem('oakInkBookEdits') || '{}');
                        savedBooks[bookId] = editedBook;
                        localStorage.setItem('oakInkBookEdits', JSON.stringify(savedBooks));
                        notice.classList.remove('error');
                        notice.classList.add('success');
                        notice.textContent = 'Book details saved in this browser. Return to the dashboard to see the updated title and author.';
                    } catch (error) {
                        console.error('Unable to save book edits in this browser.', error);
                        notice.classList.remove('success');
                        notice.classList.add('error');
                        notice.textContent = 'Could not save these edits in this browser. Check browser storage settings and try again.';
                    }
                });
            </script>
        <?php endif; ?>
    </main>
</body>
</html>