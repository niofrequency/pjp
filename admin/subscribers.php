<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();
pjp_require_login();

$db = pjp_db();

/** Validate + normalize a raw pasted list into a clean set of lowercase emails. */
function parse_subscriber_list(string $raw): array {
    $lines = preg_split('/[\r\n,;]+/', $raw) ?: [];
    $seen = [];
    $valid = [];
    $invalid = 0;
    foreach ($lines as $line) {
        $email = strtolower(trim($line));
        if ($email === '') {
            continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_contains(substr($email, strpos($email, '@') + 1), '.')) {
            $invalid++;
            continue;
        }
        $seen[$email] = true;
    }
    return [array_keys($seen), $invalid];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pjp_check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare('DELETE FROM subscribers WHERE id = ?')->execute([$id]);
            pjp_flash_set('success', 'Subscriber removed.');
        }
        pjp_redirect('subscribers.php');
    }

    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT status FROM subscribers WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $newStatus = $row['status'] === 'active' ? 'unsubscribed' : 'active';
            $db->prepare('UPDATE subscribers SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
        }
        pjp_redirect('subscribers.php');
    }

    if ($action === 'import') {
        [$emails, $invalidCount] = parse_subscriber_list((string) ($_POST['bulk_emails'] ?? ''));
        $imported = 0;
        $skipped = 0;
        $stmt = $db->prepare('INSERT INTO subscribers (email, status, source, subscribed_at) VALUES (?, ?, ?, ?)');
        foreach ($emails as $email) {
            try {
                $stmt->execute([$email, 'active', 'Imported', pjp_now()]);
                $imported++;
            } catch (PDOException $e) {
                $skipped++; // already subscribed
            }
        }
        $msg = "Imported $imported new subscriber" . ($imported === 1 ? '' : 's') . ".";
        if ($skipped) {
            $msg .= " $skipped already on the list.";
        }
        if ($invalidCount) {
            $msg .= " $invalidCount line" . ($invalidCount === 1 ? '' : 's') . " skipped (not a valid email).";
        }
        pjp_flash_set('success', $msg);
        pjp_redirect('subscribers.php');
    }
}

$statusFilter = $_GET['status'] ?? '';
if ($statusFilter === 'active' || $statusFilter === 'unsubscribed') {
    $stmt = $db->prepare('SELECT * FROM subscribers WHERE status = ? ORDER BY subscribed_at DESC');
    $stmt->execute([$statusFilter]);
    $subscribers = $stmt->fetchAll();
} else {
    $subscribers = $db->query('SELECT * FROM subscribers ORDER BY subscribed_at DESC')->fetchAll();
}

$totalActive = (int) $db->query("SELECT COUNT(*) c FROM subscribers WHERE status = 'active'")->fetch()['c'];
$totalUnsub = (int) $db->query("SELECT COUNT(*) c FROM subscribers WHERE status = 'unsubscribed'")->fetch()['c'];
$totalWebsite = (int) $db->query("SELECT COUNT(*) c FROM subscribers WHERE source = 'website'")->fetch()['c'];

$page_title = 'Subscribers';
$active_nav = 'subscribers';
require __DIR__ . '/partials/header.php';
?>
<div class="admin-header-row">
  <div>
    <h1>Subscribers</h1>
    <p>Everyone who signed up through the newsletter form in the site footer, plus anyone imported manually.</p>
  </div>
  <div class="admin-table-actions">
    <a href="subscribers-export.php" class="btn btn-outline btn-sm">Export CSV</a>
  </div>
</div>

<div class="admin-stat-grid">
  <div class="admin-stat-card">
    <div class="num"><?= $totalActive ?></div>
    <div class="label">Active Subscribers</div>
  </div>
  <div class="admin-stat-card">
    <div class="num"><?= $totalWebsite ?></div>
    <div class="label">Signed Up On Site</div>
  </div>
  <div class="admin-stat-card">
    <div class="num"><?= $totalUnsub ?></div>
    <div class="label">Unsubscribed</div>
  </div>
</div>

<div class="admin-card" style="margin-bottom:2rem;">
  <h3 style="margin-bottom:0.5rem;">Import a list</h3>
  <p class="muted" style="margin-bottom:1rem;">Paste emails below — one per line (or separated by commas). Duplicates and invalid addresses are skipped automatically, and anyone already on the list is left untouched.</p>
  <form method="POST">
    <?= pjp_csrf_field() ?>
    <input type="hidden" name="action" value="import">
    <div class="field">
      <textarea name="bulk_emails" rows="6" placeholder="name@example.com&#10;name2@example.com" style="width:100%; padding:0.9rem 1.1rem; border-radius:12px; border:1.5px solid var(--border-soft); font-family:inherit; font-size:0.95rem;" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Import &amp; Deduplicate</button>
  </form>
</div>

<div class="admin-header-row" style="margin-bottom:1rem;">
  <div class="admin-table-actions">
    <a href="subscribers.php" class="btn btn-sm <?= $statusFilter === '' ? 'btn-primary' : 'btn-outline' ?>">All</a>
    <a href="subscribers.php?status=active" class="btn btn-sm <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-outline' ?>">Active</a>
    <a href="subscribers.php?status=unsubscribed" class="btn btn-sm <?= $statusFilter === 'unsubscribed' ? 'btn-primary' : 'btn-outline' ?>">Unsubscribed</a>
  </div>
</div>

<?php if (!$subscribers): ?>
  <div class="admin-card"><p class="admin-empty">No subscribers yet.</p></div>
<?php else: ?>
  <table class="admin-table">
    <thead><tr><th>Email</th><th>Source</th><th>Status</th><th>Signed Up</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($subscribers as $s): ?>
      <tr>
        <td><?= h($s['email']) ?></td>
        <td class="muted"><?= h($s['source'] ?: '—') ?></td>
        <td>
          <?php if ($s['status'] === 'active'): ?>
            <span class="admin-badge admin-badge-active">Active</span>
          <?php else: ?>
            <span class="admin-badge admin-badge-inactive">Unsubscribed</span>
          <?php endif; ?>
        </td>
        <td class="muted"><?= pjp_fmt_dt($s['subscribed_at']) ?></td>
        <td>
          <div class="admin-table-actions">
            <form method="POST">
              <?= pjp_csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button type="submit" name="action" value="toggle_status" class="btn btn-outline btn-sm">
                <?= $s['status'] === 'active' ? 'Unsubscribe' : 'Reactivate' ?>
              </button>
            </form>
            <form method="POST" onsubmit="return confirm('Remove this subscriber permanently?');">
              <?= pjp_csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
