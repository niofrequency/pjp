<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_require_login();

$db = pjp_db();
$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pjp_check_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $db->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
        pjp_flash_set('success', 'Message deleted.');
        pjp_redirect('messages.php');
    }
}

$stmt = $db->prepare('SELECT * FROM messages WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m) {
    pjp_flash_set('error', 'That message no longer exists.');
    pjp_redirect('messages.php');
}

if (!$m['is_read']) {
    $db->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$id]);
}

$raw = json_decode((string) $m['raw_data'], true) ?: [];

$page_title = 'Message';
$active_nav = 'messages';
require __DIR__ . '/partials/header.php';
?>
<div class="admin-header-row">
  <div>
    <h1><?= h($m['name'] ?: 'Message') ?></h1>
    <p>Received <?= pjp_fmt_dt($m['created_at']) ?></p>
  </div>
  <div class="admin-table-actions">
    <a href="messages.php" class="btn btn-outline btn-sm">&larr; All Messages</a>
    <form method="POST" onsubmit="return confirm('Delete this message permanently?');">
      <?= pjp_csrf_field() ?>
      <button type="submit" name="action" value="delete" class="btn btn-outline btn-sm">Delete</button>
    </form>
  </div>
</div>

<div class="admin-card">
  <table class="admin-table" style="box-shadow:none; margin-bottom:1.5rem;">
    <tbody>
      <tr><th style="width:140px;">Name</th><td><?= h($m['name']) ?></td></tr>
      <tr><th>Email</th><td><a href="mailto:<?= h($m['email']) ?>"><?= h($m['email']) ?></a></td></tr>
      <?php if ($m['phone']): ?><tr><th>Phone</th><td><?= h($m['phone']) ?></td></tr><?php endif; ?>
      <?php if ($m['subject']): ?><tr><th>Service / Subject</th><td><?= h($m['subject']) ?></td></tr><?php endif; ?>
      <?php if ($m['source_page']): ?><tr><th>Submitted from</th><td class="muted"><?= h($m['source_page']) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
  <h4 style="margin-bottom:0.75rem;">Message</h4>
  <p style="white-space:pre-wrap; color:var(--text-main);"><?= h($m['message']) ?></p>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
