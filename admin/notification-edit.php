<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_require_login();

$db = pjp_db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$notif = null;
if ($id) {
    $stmt = $db->prepare('SELECT * FROM notifications WHERE id = ?');
    $stmt->execute([$id]);
    $notif = $stmt->fetch();
    if (!$notif) {
        pjp_flash_set('error', 'That notification no longer exists.');
        pjp_redirect('notifications.php');
    }
}

$errors = [];
$values = [
    'message' => $notif['message'] ?? '',
    'link_url' => $notif['link_url'] ?? '',
    'link_text' => $notif['link_text'] ?? '',
    'style' => $notif['style'] ?? 'info',
    'active' => $notif ? (bool) $notif['active'] : true,
    'start_at' => $notif ? pjp_dt_input_value($notif['start_at']) : date('Y-m-d\TH:i'),
    'end_at' => $notif ? pjp_dt_input_value($notif['end_at']) : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pjp_check_csrf();

    $values['message'] = trim($_POST['message'] ?? '');
    $values['link_url'] = trim($_POST['link_url'] ?? '');
    $values['link_text'] = trim($_POST['link_text'] ?? '');
    $values['style'] = in_array($_POST['style'] ?? '', ['info', 'success', 'warning'], true) ? $_POST['style'] : 'info';
    $values['active'] = !empty($_POST['active']);
    $values['start_at'] = $_POST['start_at'] ?? '';
    $values['end_at'] = $_POST['end_at'] ?? '';

    if ($values['message'] === '') {
        $errors[] = 'Message text is required.';
    }

    $startAt = pjp_dt_from_input($values['start_at']) ?? pjp_now();
    $endAt = pjp_dt_from_input($values['end_at']);

    if (!$errors) {
        if ($notif) {
            $stmt = $db->prepare(
                'UPDATE notifications SET message=?, link_url=?, link_text=?, style=?, start_at=?, end_at=?, active=? WHERE id=?'
            );
            $stmt->execute([
                $values['message'], $values['link_url'], $values['link_text'], $values['style'],
                $startAt, $endAt, $values['active'] ? 1 : 0, $id,
            ]);
            pjp_flash_set('success', 'Notification updated.');
        } else {
            $stmt = $db->prepare(
                'INSERT INTO notifications (message, link_url, link_text, style, start_at, end_at, active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $values['message'], $values['link_url'], $values['link_text'], $values['style'],
                $startAt, $endAt, $values['active'] ? 1 : 0, pjp_now(),
            ]);
            pjp_flash_set('success', 'Notification created.');
        }
        pjp_redirect('notifications.php');
    }
}

$page_title = $notif ? 'Edit Notification' : 'New Notification';
$active_nav = 'notifications';
require __DIR__ . '/partials/header.php';
?>
<div class="admin-header-row">
  <div>
    <h1><?= $notif ? 'Edit Notification' : 'New Notification' ?></h1>
    <p>Shown as a banner at the top of every page while it's active and inside its display window.</p>
  </div>
  <a href="notifications.php" class="btn btn-outline btn-sm">&larr; All Notifications</a>
</div>

<?php foreach ($errors as $e): ?><div class="admin-alert admin-alert-error"><?= h($e) ?></div><?php endforeach; ?>

<div class="admin-card">
  <form method="POST" class="admin-form">
    <?= pjp_csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="field">
      <label for="message">Message</label>
      <textarea id="message" name="message" rows="2" required><?= h($values['message']) ?></textarea>
    </div>

    <div class="field-row-3">
      <div class="field">
        <label for="link_url">Link URL (optional)</label>
        <input type="text" id="link_url" name="link_url" placeholder="e.g. service.html" value="<?= h($values['link_url']) ?>">
      </div>
      <div class="field">
        <label for="link_text">Link text (optional)</label>
        <input type="text" id="link_text" name="link_text" placeholder="e.g. Learn more" value="<?= h($values['link_text']) ?>">
      </div>
    </div>

    <div class="field">
      <label for="style">Style</label>
      <select id="style" name="style">
        <option value="info" <?= $values['style'] === 'info' ? 'selected' : '' ?>>Info (navy)</option>
        <option value="success" <?= $values['style'] === 'success' ? 'selected' : '' ?>>Success (green)</option>
        <option value="warning" <?= $values['style'] === 'warning' ? 'selected' : '' ?>>Warning (amber)</option>
      </select>
    </div>

    <div class="field-row-3">
      <div class="field">
        <label for="start_at">Display from</label>
        <input type="datetime-local" id="start_at" name="start_at" value="<?= h($values['start_at']) ?>">
      </div>
      <div class="field">
        <label for="end_at">Display until (optional)</label>
        <input type="datetime-local" id="end_at" name="end_at" value="<?= h($values['end_at']) ?>">
        <div class="admin-quickpick">
          <button type="button" data-hours="24">+1 day</button>
          <button type="button" data-hours="168">+7 days</button>
          <button type="button" data-hours="720">+30 days</button>
          <button type="button" data-clear="1">No end date</button>
        </div>
      </div>
    </div>

    <div class="field">
      <label><input type="checkbox" name="active" value="1" <?= $values['active'] ? 'checked' : '' ?> style="width:auto; margin-right:0.5rem;"> Active</label>
    </div>

    <button type="submit" class="btn btn-primary"><?= $notif ? 'Save Changes' : 'Create Notification' ?></button>
  </form>
</div>

<script>
  document.querySelectorAll('.admin-quickpick button[data-hours]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var hours = parseInt(btn.getAttribute('data-hours'), 10);
      var start = document.getElementById('start_at').value || new Date().toISOString().slice(0, 16);
      var startDate = new Date(start);
      startDate.setHours(startDate.getHours() + hours);
      var pad = function (n) { return String(n).padStart(2, '0'); };
      var value = startDate.getFullYear() + '-' + pad(startDate.getMonth() + 1) + '-' + pad(startDate.getDate()) + 'T' + pad(startDate.getHours()) + ':' + pad(startDate.getMinutes());
      document.getElementById('end_at').value = value;
    });
  });
  var clearBtn = document.querySelector('.admin-quickpick button[data-clear]');
  if (clearBtn) clearBtn.addEventListener('click', function () { document.getElementById('end_at').value = ''; });
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
