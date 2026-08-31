<?php
/**
 * Public form handler for every contact/quote form on the site.
 * Accepts both the English field names (Name, Email, Phone, Service, Message)
 * used by the quote forms, and the plain lowercase names (name, email,
 * subject, message) used by contact.html — plus their Indonesian
 * equivalents (Nama, Telepon, Layanan, Pesan) so one endpoint serves the
 * whole bilingual site without needing to know which page it came from.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pjp_redirect('contact.html');
}

function first_present(array $data, array $keys): string {
    foreach ($keys as $k) {
        if (!empty($data[$k])) {
            return trim((string) $data[$k]);
        }
    }
    return '';
}

$name = first_present($_POST, ['name', 'Name', 'Nama']);
$email = first_present($_POST, ['email', 'Email']);
$phone = first_present($_POST, ['phone', 'Phone', 'Telepon']);
$service = first_present($_POST, ['subject', 'Subject', 'Service', 'Layanan', 'Company', 'Perusahaan']);
$message = first_present($_POST, ['message', 'Message', 'Pesan']);
$sourcePage = trim((string) ($_POST['source_page'] ?? ($_SERVER['HTTP_REFERER'] ?? '')));

// Basic honeypot: a hidden field named "website" that real visitors never fill in.
if (!empty($_POST['website'])) {
    // Silently pretend success to the bot; don't store anything.
    respond_success();
}

$errors = [];
if ($name === '') {
    $errors[] = 'name';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email';
}
if ($message === '') {
    $errors[] = 'message';
}

if ($errors) {
    respond_error('Please fill in a valid name, email, and message.');
}

$stmt = pjp_db()->prepare(
    'INSERT INTO messages (created_at, name, email, phone, subject, message, source_page, raw_data, is_read)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)'
);
$stmt->execute([
    pjp_now(),
    $name,
    $email,
    $phone,
    $service,
    $message,
    $sourcePage,
    json_encode($_POST, JSON_UNESCAPED_UNICODE),
]);

respond_success();

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
    // Non-JS fallback: send the visitor back with a query flag the page can read if desired.
    $ref = $_SERVER['HTTP_REFERER'] ?? 'contact.html';
    $sep = str_contains($ref, '?') ? '&' : '?';
    pjp_redirect($ref . $sep . 'form_error=1');
}
