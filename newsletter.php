<?php
/**
 * Public handler for the "Sign Up" newsletter form in the site footer
 * (present on every page). Stores the email in the subscribers table so
 * the admin can see who signed up and export the list for email
 * marketing — it does not send any email itself.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pjp_redirect('index.html');
}

function wants_json(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'xmlhttprequest';
}

function respond_success(): void {
    if (wants_json()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
    pjp_redirect('success.html');
}

function respond_error(string $msg): void {
    if (wants_json()) {
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $msg]);
        exit;
    }
    $ref = $_SERVER['HTTP_REFERER'] ?? 'index.html';
    $sep = str_contains($ref, '?') ? '&' : '?';
    pjp_redirect($ref . $sep . 'form_error=1');
}

// Honeypot: a hidden field real visitors never fill in (harmless if the
// form doesn't include it — this just quietly rejects bots that do).
if (!empty($_POST['website'])) {
    respond_success();
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond_error('Please enter a valid email address.');
}

try {
    $stmt = pjp_db()->prepare(
        'INSERT INTO subscribers (email, status, source, subscribed_at) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$email, 'active', 'website', pjp_now()]);
} catch (PDOException $e) {
    // SQLSTATE 23000 = unique constraint violation, i.e. already subscribed.
    // That's still a success from the visitor's point of view; anything
    // else is a real failure and should be reported.
    if ($e->getCode() !== '23000') {
        respond_error('Sorry, we could not sign you up right now. Please try again shortly.');
    }
} catch (Throwable $e) {
    respond_error('Sorry, we could not sign you up right now. Please try again shortly.');
}

respond_success();
