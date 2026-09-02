<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_require_login();

$db = pjp_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pjp_check_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete' && $id) {
        $db->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
        pjp_flash_set('success', 'Message deleted.');
    }
    if ($action === 'toggle_read' && $id) {
        $stmt = $db->prepare('SELECT is_read FROM messages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $db->prepare('UPDATE messages SET is_read = ? WHERE id = ?')->execute([$row['is_read'] ? 0 : 1, $id]);
        }
    }
    pjp_redirect('messages.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$statusFilter = $_GET['status'] ?? '';
if ($statusFilter === 'unread') {
    $messages = $db->query('SELECT * FROM messages WHERE is_read = 0 ORDER BY created_at DESC')->fetchAll();
} elseif ($statusFilter === 'read') {
    $messages = $db->query('SELECT * FROM messages WHERE is_read = 1 ORDER BY created_at DESC')->fetchAll();
} else {
    $messages = $db->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();
}
$unreadCount = (int) $db->query('SELECT COUNT(*) c FROM messages WHERE is_read = 0')->fetch()['c'];

$page_title = 'Messages';
$active_nav = 'messages';
require __DIR__ . '/partials/header.php';
?>
<div class="admin-header-row">
  <div>
    <h1>Messages</h1>
    <p>Everything submitted through the site's contact and quote forms.<?= $unreadCount ? ' ' . $unreadCount . ' unread.' : '' ?></p>
  </div>
  <div class="admin-table-actions">
    <a href="messages.php" class="btn btn-sm <?= $statusFilter === '' ? 'btn-primary' : 'btn-outline' ?>">All</a>
    <a href="messages.php?status=unread" class="btn btn-sm <?= $statusFilter === 'unread' ? 'btn-primary' : 'btn-outline' ?>">Unread</a>
    <a href="messages.php?status=read" class="btn btn-sm <?= $statusFilter === 'read' ? 'btn-primary' : 'btn-outline' ?>">Read</a>
  </div>
</div>

<?php if (!$messages): ?>
  <div class="admin-card"><p class="admin-empty">No messages<?= $statusFilter ? ' in this view' : '' ?> yet.</p></div>
<?php else: ?>
  <table class="admin-table">
    <thead><tr><th>From</th><th>Service / Subject</th><th>Message</th><th>Received</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($messages as $m): ?>
      <tr class="<?= $m['is_read'] ? '' : 'is-unread' ?>">
        <td>
          <strong><?= h($m['name'] ?: 'Unknown') ?></strong><br>
          <span class="muted"><?= h($m['email']) ?></span>
          <?php if ($m['phone']): ?><br><span class="muted"><?= h($m['phone']) ?></span><?php endif; ?>
        </td>
        <td><?= h($m['subject']) ?: '<span class="muted">—</span>' ?></td>
        <td><?= h(mb_strimwidth((string) $m['message'], 0, 110, '…')) ?></td>
        <td class="muted"><?= pjp_fmt_dt($m['created_at']) ?><?php if ($m['source_page']): ?><br><span title="<?= h($m['source_page']) ?>">from site</span><?php endif; ?></td>
        <td>
          <div class="admin-table-actions">
            <a href="message-view.php?id=<?= (int) $m['id'] ?>" class="btn btn-outline btn-sm">View</a>
            <form method="POST">
              <?= pjp_csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <button type="submit" name="action" value="toggle_read" class="btn btn-outline btn-sm">
                <?= $m['is_read'] ? 'Mark Unread' : 'Mark Read' ?>
              </button>
            </form>
            <form method="POST" onsubmit="return confirm('Delete this message permanently?');">
              <?= pjp_csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <button type="submit" name="action" value="delete" class="btn btn-outline btn-sm">Delete</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
