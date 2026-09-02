<?php
/** Small shared utilities used across public and admin pages. */

function h(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function pjp_redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function pjp_slugify(string $text): string {
    $text = trim($text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'post-' . substr(md5(microtime()), 0, 8);
}

/** CSRF token: one per session, checked on every admin POST. */
function pjp_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function pjp_csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(pjp_csrf_token()) . '">';
}

function pjp_check_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Security check failed. Please go back, refresh the page, and try again.');
    }
}

/** One-shot flash messages (survive exactly one redirect). */
function pjp_flash_set(string $type, string $text): void {
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function pjp_flash_get(): ?array {
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/** Human-friendly date for the admin UI; input is a stored 'Y-m-d H:i:s' UTC string. */
function pjp_fmt_dt(?string $value): string {
    if (!$value) {
        return '—';
    }
    $ts = strtotime($value . ' UTC');
    return $ts ? date('d M Y, H:i', $ts) . ' UTC' : h($value);
}

/** Convert a stored datetime string to the value a <input type="datetime-local"> expects. */
function pjp_dt_input_value(?string $value): string {
    if (!$value) {
        return '';
    }
    $ts = strtotime($value . ' UTC');
    return $ts ? gmdate('Y-m-d\TH:i', $ts) : '';
}

/** Convert a <input type="datetime-local"> submitted value back to our stored format (UTC). */
function pjp_dt_from_input(?string $value): ?string {
    if (!$value) {
        return null;
    }
    $ts = strtotime($value);
    return $ts ? gmdate('Y-m-d H:i:s', $ts) : null;
}
