<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_require_login();

$db = pjp_db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$post = null;
if ($id) {
    $stmt = $db->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) {
        pjp_flash_set('error', 'That post no longer exists.');
        pjp_redirect('posts.php');
    }
}

$errors = [];
$values = [
    'title' => $post['title'] ?? '',
    'slug' => $post['slug'] ?? '',
    'category' => $post['category'] ?? '',
    'excerpt' => $post['excerpt'] ?? '',
    'body' => $post['body'] ?? '',
    'image' => $post['image'] ?? '',
    'status' => $post['status'] ?? 'draft',
    'display_start' => $post ? pjp_dt_input_value($post['display_start']) : date('Y-m-d\TH:i'),
    'display_end' => $post ? pjp_dt_input_value($post['display_end']) : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pjp_check_csrf();

    $values['title'] = trim($_POST['title'] ?? '');
    $values['slug'] = trim($_POST['slug'] ?? '');
    $values['category'] = trim($_POST['category'] ?? '');
    $values['excerpt'] = trim($_POST['excerpt'] ?? '');
    $values['body'] = trim($_POST['body'] ?? '');
    $values['image'] = trim($_POST['image'] ?? '');
    $values['status'] = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $values['display_start'] = $_POST['display_start'] ?? '';
    $values['display_end'] = $_POST['display_end'] ?? '';

    if ($values['title'] === '') {
        $errors[] = 'Title is required.';
    }
    if ($values['body'] === '') {
        $errors[] = 'Body is required.';
    }

    $slug = $values['slug'] !== '' ? pjp_slugify($values['slug']) : pjp_slugify($values['title']);
    // Ensure slug uniqueness (excluding this post if editing).
    $checkStmt = $db->prepare('SELECT id FROM posts WHERE slug = ? AND id != ?');
    $checkStmt->execute([$slug, $id ?: 0]);
    if ($checkStmt->fetch()) {
        $slug .= '-' . substr(md5((string) microtime()), 0, 5);
    }

    $displayStart = pjp_dt_from_input($values['display_start']) ?? pjp_now();
    $displayEnd = pjp_dt_from_input($values['display_end']);

    if (!$errors) {
        $now = pjp_now();
        if ($post) {
            $stmt = $db->prepare(
                'UPDATE posts SET title=?, slug=?, category=?, excerpt=?, body=?, image=?, status=?, display_start=?, display_end=?, updated_at=? WHERE id=?'
            );
            $stmt->execute([
                $values['title'], $slug, $values['category'], $values['excerpt'], $values['body'],
                $values['image'], $values['status'], $displayStart, $displayEnd, $now, $id,
            ]);
            pjp_flash_set('success', 'Post updated.');
        } else {
            $stmt = $db->prepare(
                'INSERT INTO posts (slug, title, excerpt, body, image, category, status, display_start, display_end, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $slug, $values['title'], $values['excerpt'], $values['body'], $values['image'],
                $values['category'], $values['status'], $displayStart, $displayEnd, $now, $now,
            ]);
            pjp_flash_set('success', 'Post created.');
        }
        pjp_redirect('posts.php');
    }
}

$page_title = $post ? 'Edit Post' : 'New Post';
$active_nav = 'posts';
require __DIR__ . '/partials/header.php';
?>
<div class="admin-header-row">
  <div>
    <h1><?= $post ? 'Edit Post' : 'New Post' ?></h1>
    <p>Set a display window if you only want this visible for a limited time — leave the end date blank to keep it up until you delete it.</p>
  </div>
  <a href="posts.php" class="btn btn-outline btn-sm">&larr; All Posts</a>
</div>

<?php foreach ($errors as $e): ?><div class="admin-alert admin-alert-error"><?= h($e) ?></div><?php endforeach; ?>

<div class="admin-card">
  <form method="POST" class="admin-form">
    <?= pjp_csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="field">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" required value="<?= h($values['title']) ?>">
    </div>

    <div class="field-row-3">
      <div class="field">
        <label for="slug">URL slug (optional)</label>
        <input type="text" id="slug" name="slug" placeholder="auto-generated from title if left blank" value="<?= h($values['slug']) ?>">
      </div>
      <div class="field">
        <label for="category">Category tag (optional)</label>
        <input type="text" id="category" name="category" placeholder="e.g. Announcement" value="<?= h($values['category']) ?>">
      </div>
    </div>

    <div class="field">
      <label for="image">Image path (optional)</label>
      <input type="text" id="image" name="image" placeholder="e.g. img/your-photo.jpg — same as other site images" value="<?= h($values['image']) ?>">
    </div>

    <div class="field">
      <label for="excerpt">Short excerpt (shown in listings, optional)</label>
      <textarea id="excerpt" name="excerpt" rows="2"><?= h($values['excerpt']) ?></textarea>
    </div>

    <div class="field">
      <label for="body">Body</label>
      <textarea id="body" name="body" required><?= h($values['body']) ?></textarea>
    </div>
    <p class="hint">Plain text or simple HTML (e.g. &lt;p&gt;, &lt;strong&gt;, &lt;a href="..."&gt;) — it's inserted into the page as-is.</p>

    <div class="field-row-3">
      <div class="field">
        <label for="display_start">Display from</label>
        <input type="datetime-local" id="display_start" name="display_start" value="<?= h($values['display_start']) ?>">
      </div>
      <div class="field">
        <label for="display_end">Display until (optional)</label>
        <input type="datetime-local" id="display_end" name="display_end" value="<?= h($values['display_end']) ?>">
        <div class="admin-quickpick">
          <button type="button" data-days="1">+1 day</button>
          <button type="button" data-days="7">+7 days</button>
          <button type="button" data-days="30">+30 days</button>
          <button type="button" data-clear="1">No end date</button>
        </div>
      </div>
    </div>

    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Draft (never shown on site)</option>
        <option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Published (shown during the display window above)</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary"><?= $post ? 'Save Changes' : 'Create Post' ?></button>
  </form>
</div>

<script>
  document.querySelectorAll('.admin-quickpick button[data-days]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var days = parseInt(btn.getAttribute('data-days'), 10);
      var start = document.getElementById('display_start').value || new Date().toISOString().slice(0, 16);
      var startDate = new Date(start);
      startDate.setDate(startDate.getDate() + days);
      var pad = function (n) { return String(n).padStart(2, '0'); };
      var value = startDate.getFullYear() + '-' + pad(startDate.getMonth() + 1) + '-' + pad(startDate.getDate()) + 'T' + pad(startDate.getHours()) + ':' + pad(startDate.getMinutes());
      document.getElementById('display_end').value = value;
    });
  });
  var clearBtn = document.querySelector('.admin-quickpick button[data-clear]');
  if (clearBtn) clearBtn.addEventListener('click', function () { document.getElementById('display_end').value = ''; });
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
