<?php
/** Download all active subscribers as a CSV for use in an email marketing tool. */
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_require_login();

$rows = pjp_db()->query("SELECT email, status, source, subscribed_at FROM subscribers ORDER BY subscribed_at DESC")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="pjp-subscribers-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['email', 'status', 'source', 'subscribed_at']);
foreach ($rows as $r) {
    fputcsv($out, [$r['email'], $r['status'], $r['source'], $r['subscribed_at']]);
}
fclose($out);
