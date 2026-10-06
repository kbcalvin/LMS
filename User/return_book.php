<?php
session_start();

$sessionUserId = $_SESSION['user_id'] ?? null;
$userId = is_string($sessionUserId) || is_int($sessionUserId)
    ? filter_var($sessionUserId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
$isUser = $userId !== false && $userId !== null;
if ($isUser && isset($_SESSION['role']) && $_SESSION['role'] !== 'user') {
    $isUser = false;
}

$feedback = static function (string $type, string $message): void {
    $_SESSION['return_feedback'] = ['type' => $type, 'message' => $message];
    header('Location: borrowed_books.php');
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: borrowed_books.php');
    exit;
}

if (!$isUser) {
    $feedback('error', 'Sign in with a library member account before returning a book.');
}

$csrfToken = $_SESSION['return_csrf_token'] ?? null;
if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) ||
    !is_string($csrfToken) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
    $feedback('error', 'Your session could not be verified. Refresh the page and try again.');
}

$submittedBorrowingId = $_POST['borrowing_id'] ?? null;
$borrowingId = is_string($submittedBorrowingId)
    ? filter_var($submittedBorrowingId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
if ($borrowingId === false || $borrowingId === null) {
    $feedback('error', 'Choose a valid borrowed book to return.');
}

try {
    require __DIR__ . '/../inc/connection.php';
    $pdo->beginTransaction();

    $memberQuery = $pdo->prepare(
        "SELECT id FROM users WHERE id = :user_id AND role = 'user' FOR UPDATE"
    );
    $memberQuery->execute(['user_id' => $userId]);
    if (!$memberQuery->fetch()) {
        $pdo->rollBack();
        $feedback('error', 'Your library member account could not be verified. Please contact a librarian.');
    }

    $borrowingQuery = $pdo->prepare(
        "SELECT book_id FROM borrowings
         WHERE id = :borrowing_id AND user_id = :user_id
           AND status = 'approved' AND date_returned IS NULL
         FOR UPDATE"
    );
    $borrowingQuery->execute([
        'borrowing_id' => $borrowingId,
        'user_id' => $userId,
    ]);
    $borrowing = $borrowingQuery->fetch();

    if (!$borrowing) {
        $pdo->rollBack();
        $feedback('error', 'This book is not currently borrowed on your account, or it has already been returned.');
    }

    $bookQuery = $pdo->prepare(
        'SELECT id, name, quantity, available FROM textbooks WHERE id = :book_id FOR UPDATE'
    );
    $bookQuery->execute(['book_id' => $borrowing['book_id']]);
    $book = $bookQuery->fetch();

    if (!$book) {
        $pdo->rollBack();
        $feedback('error', 'The book record could not be found. Please ask a librarian for help.');
    }

    $restoreAvailability = $pdo->prepare(
        'UPDATE textbooks
         SET available = available + 1
         WHERE id = :book_id AND available < quantity'
    );
    $restoreAvailability->execute(['book_id' => $book['id']]);
    if ($restoreAvailability->rowCount() !== 1) {
        $pdo->rollBack();
        $feedback('error', 'The available-copy count could not be updated safely. Please ask a librarian for help.');
    }

    $markReturned = $pdo->prepare(
        'UPDATE borrowings SET date_returned = CURRENT_DATE
         WHERE id = :borrowing_id AND user_id = :user_id AND date_returned IS NULL'
    );
    $markReturned->execute([
        'borrowing_id' => $borrowingId,
        'user_id' => $userId,
    ]);
    if ($markReturned->rowCount() !== 1) {
        $pdo->rollBack();
        $feedback('error', 'This borrowing was already returned or changed. Refresh your borrowing list and try again.');
    }

    $pdo->commit();
    $feedback('success', 'Thank you for returning "' . $book['name'] . '" to the library.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Unable to process member book return: ' . $exception->getMessage());
    $feedback('error', 'The return could not be recorded. Please try again or ask a librarian for help.');
}