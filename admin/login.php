<?php
require_once __DIR__ . '/../includes/auth.php';
pjp_start_session();

if (!pjp_admin_exists()) {
    pjp_redirect('setup.php');
}
if (pjp_logged_in()) {
    pjp_redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (pjp_attempt_login($username, $password)) {
        pjp_redirect('index.php');
    }
    $error = 'Incorrect username or password.';
}

$flash = pjp_flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Login | PJP</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<link href="../css/design.css" rel="stylesheet">
<link href="assets/admin.css" rel="stylesheet">
</head>
<body class="admin-auth-page">
  <div class="admin-auth-card">
    <div class="admin-auth-brand"><img src="../img/icon/PJP logo.png" alt="PJP logo"> PT. PJP</div>
    <h1>Admin Login</h1>
    <?php if ($flash): ?><div class="admin-alert admin-alert-<?= h($flash['type']) ?>"><?= h($flash['text']) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-alert admin-alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
    <p class="admin-auth-back"><a href="../index.html">&larr; Back to website</a></p>
  </div>
</body>
</html>
