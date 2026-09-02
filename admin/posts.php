<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_require_login();

$db = pjp_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pjp_check_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete' && $id) {
        $db->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        pjp_flash_set('success', 'Post deleted.');
    }
    pjp_redirect('posts.php');
}

$posts = $db->query('SELECT * FROM posts ORDER BY created_at DESC')->fetchAll();
$now = pjp_now();

function post_status_badge(array $p, string $now): string {
    if ($p['status'] !== 'published') {
        return '<span class="admin-badge admin-badge-draft">Draft</span>';
    }
    if ($p['display_start'] && $p['display_start'] > $now) {
        return '<span class="admin-badge admin-badge-scheduled">Scheduled</span>';
    }
    if ($p['display_end'] && $p['display_end'] < $now) {
        return '<span class="admin-badge admin-badge-expired">Expired</span>';
    }
    return '<span class="admin-badge admin-badge-published">Live</span>';
}

$page_title = 'Blog Posts';
$active_nav = 'posts';
require __DIR__ . '/partials/header.php';
?>
<div class="admin-header-row">
  <div>
    <h1>Blog Posts</h1>
    <p>Posts you write here appear at <a href="../blog.php" target="_blank">/blog.php</a> on the live site, only during the display window you set.</p>
  </div>
  <a href="post-edit.php" class="btn btn-primary">+ New Post</a>
</div>

<?php if (!$posts): ?>
  <div class="admin-card"><p class="admin-empty">No posts yet. Click "New Post" to write your first one.</p></div>
<?php else: ?>
  <table class="admin-table">
    <thead><tr><th>Title</th><th>Status</th><th>Display Window</th><th>Updated</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($posts as $p): ?>
      <tr>
        <td><strong><?= h($p['title']) ?></strong><br><span class="muted">/blog.php?slug=<?= h($p['slug']) ?></span></td>
        <td><?= post_status_badge($p, $now) ?></td>
        <td class="muted">
          From <?= pjp_fmt_dt($p['display_start']) ?><br>
          Until <?= $p['display_end'] ? pjp_fmt_dt($p['display_end']) : 'no end date' ?>
        </td>
        <td class="muted"><?= pjp_fmt_dt($p['updated_at']) ?></td>
        <td>
          <div class="admin-table-actions">
            <a href="post-edit.php?id=<?= (int) $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="POST" onsubmit="return confirm('Delete this post permanently?');">
              <?= pjp_csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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
