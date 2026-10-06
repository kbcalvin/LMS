<?php
session_start();
require __DIR__ . '/../inc/authorization.php';
require_library_role(['librarian'], '../auth/login.php');

$flash = static function (string $type, string $message): void {
    $_SESSION['borrowing_review_feedback'] = ['type' => $type, 'message' => $message];
    header('Location: borrowings.php');
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use the borrowing review form.');
}

$csrfToken = $_SESSION['borrowing_review_csrf'] ?? null;
$submittedToken = $_POST['csrf_token'] ?? null;
if (!is_string($csrfToken) || !is_string($submittedToken) ||
    !hash_equals($csrfToken, $submittedToken)) {
    $flash('error', 'Your session could not be verified. Refresh the page and try again.');
}

$requestId = isset($_POST['borrowing_id']) && is_string($_POST['borrowing_id'])
    ? filter_var($_POST['borrowing_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
$decision = $_POST['decision'] ?? null;
if ($requestId === false || !in_array($decision, ['approve', 'reject'], true)) {
    $flash('error', 'Choose a valid borrowing request and decision.');
}

try {
    require __DIR__ . '/../inc/connection.php';
    $pdo->beginTransaction();

    $requestQuery = $pdo->prepare(
        'SELECT id, user_id, book_id, status
         FROM borrowings
         WHERE id = :borrowing_id
         FOR UPDATE'
    );
    $requestQuery->execute(['borrowing_id' => $requestId]);
    $request = $requestQuery->fetch();
    if (!$request || $request['status'] !== 'pending') {
        $pdo->rollBack();
        $flash('error', 'This request has already been reviewed or no longer exists.');
    }

    $memberQuery = $pdo->prepare(
        "SELECT id FROM users WHERE id = :user_id AND role = 'user' FOR UPDATE"
    );
    $memberQuery->execute(['user_id' => $request['user_id']]);
    if (!$memberQuery->fetch()) {
        $pdo->rollBack();
        $flash('error', 'The requesting member account could not be verified.');
    }

    if ($decision === 'reject') {
        $update = $pdo->prepare(
            "UPDATE borrowings SET status = 'rejected'
             WHERE id = :borrowing_id AND status = 'pending'"
        );
        $update->execute(['borrowing_id' => $requestId]);
        if ($update->rowCount() !== 1) {
            $pdo->rollBack();
            $flash('error', 'This request was changed before it could be declined. Refresh and try again.');
        }
        $pdo->commit();
        $flash('success', 'Borrowing request declined. No book copies were removed from availability.');
    }

    $bookQuery = $pdo->prepare(
        'SELECT id, name, available FROM textbooks WHERE id = :book_id FOR UPDATE'
    );
    $bookQuery->execute(['book_id' => $request['book_id']]);
    $book = $bookQuery->fetch();
    if (!$book || (int) $book['available'] < 1) {
        $pdo->rollBack();
        $flash('error', 'No copies of this book are available now, so the request could not be approved.');
    }

    $updateAvailability = $pdo->prepare(
        'UPDATE textbooks SET available = available - 1
         WHERE id = :book_id AND available > 0'
    );
    $updateAvailability->execute(['book_id' => $book['id']]);
    if ($updateAvailability->rowCount() !== 1) {
        $pdo->rollBack();
        $flash('error', 'The book is no longer available. Refresh the request list and try again.');
    }

    $approve = $pdo->prepare(
        "UPDATE borrowings
         SET status = 'approved', borrow_date = CURRENT_DATE,
             due_date = DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
         WHERE id = :borrowing_id AND status = 'pending'"
    );
    $approve->execute(['borrowing_id' => $requestId]);
    if ($approve->rowCount() !== 1) {
        $pdo->rollBack();
        $flash('error', 'The request changed before it could be approved. Refresh and try again.');
    }

    $pdo->commit();
    $flash('success', 'Request approved for "' . $book['name'] . '". Availability is updated and the 7-day due date has started.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Unable to review library borrowing request: ' . $exception->getMessage());
    $flash('error', 'The request could not be reviewed. Please try again or ask a system administrator for help.');
}
