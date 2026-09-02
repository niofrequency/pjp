<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ga4.php';
pjp_start_session();
pjp_require_login();

$db = pjp_db();
$ga4 = ga4_dashboard_summary();
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

<?php if ($ga4): ?>
  <div class="admin-card" style="margin-bottom:2rem;">
    <div class="admin-header-row" style="margin-bottom:1.25rem;">
      <h3 style="margin:0;">Website Traffic <span class="muted" style="font-weight:500; font-size:0.85rem;">(last 28 days)</span></h3>
      <a href="analytics.php" class="card-link">Full report &rarr;</a>
    </div>
    <div class="admin-stat-grid">
      <div class="admin-stat-card">
        <div class="num"><?= number_format($ga4['activeUsers']) ?></div>
        <div class="label">Active Users</div>
      </div>
      <div class="admin-stat-card">
        <div class="num"><?= number_format($ga4['views']) ?></div>
        <div class="label">Page Views</div>
      </div>
      <div class="admin-stat-card">
        <div class="num"><?= number_format($ga4['engagedSessions']) ?></div>
        <div class="label">Engaged Sessions</div>
      </div>
      <div class="admin-stat-card">
        <div class="num"><?= number_format($ga4['eventCount']) ?></div>
        <div class="label">Events</div>
      </div>
    </div>
  </div>
<?php elseif (ga4_enabled()): ?>
  <div class="admin-card" style="margin-bottom:2rem;">
    <h3 style="margin-bottom:0.5rem;">Website Traffic</h3>
    <p class="muted">Connected, but couldn't fetch data just now. See <a href="analytics.php">Analytics</a> to check the setup.</p>
  </div>
<?php else: ?>
  <div class="admin-card" style="margin-bottom:2rem;">
    <h3 style="margin-bottom:0.5rem;">Website Traffic</h3>
    <p class="muted">Not connected yet. See <a href="analytics.php">Analytics</a> for setup steps to show your Google Analytics data here.</p>
  </div>
<?php endif; ?>

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
