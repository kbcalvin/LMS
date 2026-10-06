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
    <title>Add a book | Oak &amp; Ink Library</title>
    <style>
        :root {
            --paper: #f6f2e9;
            --card: #fffdf8;
            --ink: #29251f;
            --muted: #8a8377;
            --line: #ebe5d9;
            --brown: #4b3528;
            --brown-light: #6f503b;
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
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; padding-top: 21px; margin-top: 21px; border-top: 1px solid var(--line); }
        .button { min-height: 39px; display: inline-flex; align-items: center; justify-content: center; padding: 0 15px; border: 1px solid var(--line); border-radius: 7px; color: #6f665b; background: transparent; font: inherit; font-size: 12px; cursor: pointer; }
        .button.primary { border-color: var(--brown); color: #fffaf3; background: var(--brown); font-weight: 600; }
        .button.primary:hover { background: #624632; }
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
        <h1>Add a new book</h1>
        <p class="intro">Add a title to your library collection.</p>

        <form class="form-card" onsubmit="event.preventDefault(); document.getElementById('storageNotice').hidden = false;">
            <div class="form-grid">
                <div class="field full">
                    <label for="title">Book title</label>
                    <input id="title" name="title" type="text" placeholder="e.g. The Secret Garden" required>
                </div>
                <div class="field">
                    <label for="author">Author</label>
                    <input id="author" name="author" type="text" placeholder="Author's full name" required>
                </div>
                <div class="field">
                    <label for="isbn">ISBN <span>(optional)</span></label>
                    <input id="isbn" name="isbn" type="text" placeholder="ISBN-13">
                </div>
                <div class="field">
                    <label for="genre">Genre</label>
                    <select id="genre" name="genre" required>
                        <option value="">Choose a genre</option>
                        <option>Fiction</option>
                        <option>Mystery &amp; thriller</option>
                        <option>History</option>
                        <option>Children's</option>
                        <option>Biography</option>
                        <option>Science</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="field">
                    <label for="publisher">Publisher <span>(optional)</span></label>
                    <input id="publisher" name="publisher" type="text" placeholder="Publisher name">
                </div>
                <div class="field">
                    <label for="year">Publication year <span>(optional)</span></label>
                    <input id="year" name="year" type="number" min="1" max="2100" placeholder="e.g. 2024">
                </div>
                <div class="field">
                    <label for="copies">Number of copies</label>
                    <input id="copies" name="copies" type="number" min="1" value="1" required>
                </div>
                <div class="field full">
                    <label for="description">Notes <span>(optional)</span></label>
                    <textarea id="description" name="description" placeholder="Add a short description or catalog note"></textarea>
                </div>
            </div>
            <p class="form-note" id="storageNotice" role="status" hidden>Book details are not saved yet because catalog storage is not connected.</p>
            <div class="form-actions">
                <a class="button" href="dashboard.php">Cancel</a>
                <button class="button primary" type="submit">Add book</button>
            </div>
        </form>
    </main>
</body>
</html>