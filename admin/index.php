<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_require_login();

$db = pjp_db();
$unread = (int) $db->query('SELECT COUNT(*) c FROM messages WHERE is_read = 0')->fetch()['c'];
$totalMessages = (int) $db->query('SELECT COUNT(*) c FROM messages')->fetch()['c'];
$totalPosts = (int) $db->query("SELECT COUNT(*) c FROM posts")->fetch()['c'];
$totalSubscribers = (int) $db->query("SELECT COUNT(*) c FROM subscribers WHERE status = 'active'")->fetch()['c'];
$now = pjp_now();
$activeNotifs = (int) $db->prepare("SELECT COUNT(*) c FROM notifications WHERE active = 1 AND (start_at IS NULL OR start_at <= ?) AND (end_at IS NULL OR end_at >= ?)")
    ->execute([$now, $now]) ? $db->query("SELECT COUNT(*) c FROM notifications WHERE active = 1 AND (start_at IS NULL OR start_at <= '$now') AND (end_at IS NULL OR end_at >= '$now')")->fetch()['c'] : 0;

$recentMessages = $db->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 5')->fetchAll();

$page_title = 'Dashboard';
$active_nav = 'dashboard';
require __DIR__ . '/partials/header.php';
?>
<div class="admin-header-row">
  <div>
    <h1>Dashboard</h1>
    <p>A quick look at what's happening on the site.</p>
  </div>
</div>

<div class="admin-stat-grid">
  <div class="admin-stat-card">
    <div class="num"><?= $unread ?></div>
    <div class="label">Unread Messages</div>
  </div>
  <div class="admin-stat-card">
    <div class="num"><?= $totalSubscribers ?></div>
    <div class="label">Newsletter Subscribers</div>
  </div>
  <div class="admin-stat-card">
    <div class="num"><?= $totalPosts ?></div>
    <div class="label">Blog Posts</div>
  </div>
  <div class="admin-stat-card">
    <div class="num"><?= $activeNotifs ?></div>
    <div class="label">Active Notifications</div>
  </div>
</div>

<div class="admin-card">
  <h3 style="margin-bottom:1.25rem;">Recent Messages</h3>
  <?php if (!$recentMessages): ?>
    <p class="admin-empty">No messages yet. When someone submits a contact or quote form on the site, it'll show up here.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>From</th><th>Message</th><th>Received</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($recentMessages as $m): ?>
        <tr class="<?= $m['is_read'] ? '' : 'is-unread' ?>">
          <td><strong><?= h($m['name'] ?: 'Unknown') ?></strong><br><span class="muted"><?= h($m['email']) ?></span></td>
          <td><?= h(mb_strimwidth((string) $m['message'], 0, 90, '…')) ?></td>
          <td class="muted"><?= pjp_fmt_dt($m['created_at']) ?></td>
          <td><a href="message-view.php?id=<?= (int) $m['id'] ?>" class="card-link">View &rarr;</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p style="margin-top:1.25rem;"><a href="messages.php" class="card-link">See all messages &rarr;</a></p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
