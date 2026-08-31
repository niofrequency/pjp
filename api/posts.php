<?php
/** Public, read-only: returns currently-visible admin-authored posts (published + inside display window). */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$now = pjp_now();
$limit = max(1, min(20, (int) ($_GET['limit'] ?? 6)));

$stmt = pjp_db()->prepare(
    "SELECT slug, title, excerpt, image, category, display_start FROM posts
     WHERE status = 'published' AND (display_start IS NULL OR display_start <= ?) AND (display_end IS NULL OR display_end >= ?)
     ORDER BY display_start DESC LIMIT $limit"
);
$stmt->execute([$now, $now]);

echo json_encode($stmt->fetchAll());
