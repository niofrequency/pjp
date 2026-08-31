<?php
/**
 * Shared admin chrome. Include after calling pjp_require_login().
 * Expects $active_nav to be set to one of: dashboard, messages, posts, notifications
 */
$active_nav = $active_nav ?? '';
$flash = pjp_flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= h($page_title ?? 'Admin') ?> | PJP Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<link href="../css/design.css" rel="stylesheet">
<link href="assets/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <div class="admin-sidebar-brand"><img src="../img/icon/PJP logo.png" alt="PJP logo"> PT. PJP</div>
      <nav class="admin-nav-list">
        <a href="index.php" class="<?= $active_nav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="messages.php" class="<?= $active_nav === 'messages' ? 'active' : '' ?>">Messages</a>
        <a href="posts.php" class="<?= $active_nav === 'posts' ? 'active' : '' ?>">Blog Posts</a>
        <a href="notifications.php" class="<?= $active_nav === 'notifications' ? 'active' : '' ?>">Notifications</a>
      </nav>
      <div class="admin-sidebar-footer">
        <div class="admin-user">Logged in as <strong><?= h($_SESSION['admin_username'] ?? '') ?></strong></div>
        <a href="logout.php" class="btn btn-outline btn-sm btn-block">Log Out</a>
        <a href="../index.html" class="admin-view-site">&larr; View live site</a>
      </div>
    </aside>
    <main class="admin-main">
      <?php if ($flash): ?>
        <div class="admin-alert admin-alert-<?= h($flash['type']) ?>"><?= h($flash['text']) ?></div>
      <?php endif; ?>
