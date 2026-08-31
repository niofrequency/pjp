<?php
/** Public, read-only: returns the single currently-active notification, or null. */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$now = pjp_now();
$stmt = pjp_db()->prepare(
    "SELECT id, message, link_url, link_text, style FROM notifications
     WHERE active = 1 AND (start_at IS NULL OR start_at <= ?) AND (end_at IS NULL OR end_at >= ?)
     ORDER BY id DESC LIMIT 1"
);
$stmt->execute([$now, $now]);
$notif = $stmt->fetch();

echo json_encode($notif ?: null);
