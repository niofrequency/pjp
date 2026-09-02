<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_require_login();

$db = pjp_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pjp_check_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete' && $id) {
        $db->prepare('DELETE FROM notifications WHERE id = ?')->execute([$id]);
        pjp_flash_set('success', 'Notification deleted.');
    }
    pjp_redirect('notifications.php');
}

$notifications = $db->query('SELECT * FROM notifications ORDER BY created_at DESC')->fetchAll();
$now = pjp_now();

function notif_status_badge(array $n, string $now): string {
    if (!$n['active']) {
        return '<span class="admin-badge admin-badge-inactive">Off</span>';
    }
    if ($n['start_at'] && $n['start_at'] > $now) {
        return '<span class="admin-badge admin-badge-scheduled">Scheduled</span>';
    }
    if ($n['end_at'] && $n['end_at'] < $now) {
        return '<span class="admin-badge admin-badge-expired">Expired</span>';
    }
    return '<span class="admin-badge admin-badge-active">Live</span>';
}

$page_title = 'Notifications';
$active_nav = 'notifications';
require __DIR__ . '/partials/header.php';
?>
<div class="admin-header-row">
  <div>
    <h1>Notifications</h1>
    <p>A banner shown at the top of every page on the site, only during the display window you set.</p>
  </div>
  <a href="notification-edit.php" class="btn btn-primary">+ New Notification</a>
</div>

<?php if (!$notifications): ?>
  <div class="admin-card"><p class="admin-empty">No notifications yet.</p></div>
<?php else: ?>
  <table class="admin-table">
    <thead><tr><th>Message</th><th>Status</th><th>Display Window</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($notifications as $n): ?>
      <tr>
        <td><?= h(mb_strimwidth((string) $n['message'], 0, 90, '…')) ?></td>
        <td><?= notif_status_badge($n, $now) ?></td>
        <td class="muted">
          From <?= pjp_fmt_dt($n['start_at']) ?><br>
          Until <?= $n['end_at'] ? pjp_fmt_dt($n['end_at']) : 'no end date' ?>
        </td>
        <td>
          <div class="admin-table-actions">
            <a href="notification-edit.php?id=<?= (int) $n['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="POST" onsubmit="return confirm('Delete this notification permanently?');">
              <?= pjp_csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
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
