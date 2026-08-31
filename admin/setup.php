<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();

// Safety #1: this page disables itself the moment any admin account exists.
if (pjp_admin_exists()) {
    pjp_redirect('login.php');
}

// Safety #2: refuse to run at all until the site owner has set their own
// private setup key in includes/config.php (see the comment there). This
// closes the window where a stranger who finds this URL first could create
// the admin account instead of the real site owner.
$key = $_GET['key'] ?? $_POST['key'] ?? '';
$keyOk = ADMIN_SETUP_KEY !== 'change-me-now' && hash_equals(ADMIN_SETUP_KEY, (string) $key);

if (!$keyOk) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="utf-8"><title>Setup Locked | PJP</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta name="robots" content="noindex">
      <link href="../css/design.css" rel="stylesheet">
      <link href="assets/admin.css" rel="stylesheet">
    </head>
    <body class="admin-auth-page">
      <div class="admin-auth-card">
        <h1>Setup Locked</h1>
        <?php if (ADMIN_SETUP_KEY === 'change-me-now'): ?>
          <p class="admin-auth-sub">This page won't work until you set your own private setup key. Open <code>includes/config.php</code> in cPanel's File Editor, change <code>ADMIN_SETUP_KEY</code> from <code>'change-me-now'</code> to any random string only you know, save it, then visit this page again as:</p>
          <p class="admin-auth-sub"><code>/admin/setup.php?key=THE-STRING-YOU-PICKED</code></p>
        <?php else: ?>
          <p class="admin-auth-sub">Missing or incorrect setup key. Visit this page as:</p>
          <p class="admin-auth-sub"><code>/admin/setup.php?key=THE-STRING-YOU-SET-IN-CONFIG</code></p>
        <?php endif; ?>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($username === '' || strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = pjp_db()->prepare('INSERT INTO admin_users (username, password_hash, created_at) VALUES (?, ?, ?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), pjp_now()]);
        pjp_flash_set('success', 'Admin account created. You can log in now.');
        pjp_redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Set Up Admin Account | PJP</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<link href="../css/design.css" rel="stylesheet">
<link href="assets/admin.css" rel="stylesheet">
</head>
<body class="admin-auth-page">
  <div class="admin-auth-card">
    <h1>Create Your Admin Account</h1>
    <p class="admin-auth-sub">This page only works once — the first account you create here becomes the site admin, and this form disables itself immediately after.</p>
    <?php if ($error): ?><div class="admin-alert admin-alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="key" value="<?= h($key) ?>">
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required minlength="3" value="<?= h($_POST['username'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8">
      </div>
      <div class="field">
        <label for="confirm">Confirm Password</label>
        <input type="password" id="confirm" name="confirm" required minlength="8">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Admin Account</button>
    </form>
  </div>
</body>
</html>
