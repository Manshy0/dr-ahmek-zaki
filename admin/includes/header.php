<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_login();
$user = current_user();
$page_title = $page_title ?? 'Admin Panel';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?= escape($page_title) ?> — Admin Panel</title>
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/admin.css?v=2">
<meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="brand">
      <div class="brand-mark">DZ</div>
      <div>
        <div class="brand-name">Dr. Ahmed Zaki</div>
        <div class="brand-sub">Site Manager</div>
      </div>
    </div>
    <nav class="nav">
      <a href="<?= ADMIN_URL ?>/index.php" class="nav-item <?= $current_page === 'index' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12L12 3l9 9M5 10v10h14V10"/></svg>
        Dashboard
      </a>
      <a href="<?= ADMIN_URL ?>/pages.php" class="nav-item <?= $current_page === 'pages' || $current_page === 'edit' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
        Pages
      </a>
      <a href="<?= ADMIN_URL ?>/posts.php?type=blog" class="nav-item <?= $current_page === 'posts' && ($_GET['type'] ?? '') === 'blog' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 4H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
        Blog
      </a>
      <a href="<?= ADMIN_URL ?>/posts.php?type=news" class="nav-item <?= $current_page === 'posts' && ($_GET['type'] ?? '') === 'news' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11a8 8 0 0116 0v6a2 2 0 01-2 2h-2v-7H8v7H6a2 2 0 01-2-2z"/></svg>
        News
      </a>
      <a href="<?= ADMIN_URL ?>/media.php" class="nav-item <?= $current_page === 'media' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/></svg>
        Media
      </a>
      <a href="<?= ADMIN_URL ?>/site-images.php" class="nav-item <?= $current_page === 'site-images' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Site Images
      </a>
      <a href="<?= ADMIN_URL ?>/menu.php" class="nav-item <?= $current_page === 'menu' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        Menu &amp; Footer
      </a>
      <a href="<?= ADMIN_URL ?>/appearance.php" class="nav-item <?= $current_page === 'appearance' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
        Appearance
      </a>
      <a href="<?= ADMIN_URL ?>/seo.php" class="nav-item <?= $current_page === 'seo' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        SEO
      </a>
      <?php if (can('manage_users')): ?>
      <a href="<?= ADMIN_URL ?>/users.php" class="nav-item <?= $current_page === 'users' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        Users
      </a>
      <a href="<?= ADMIN_URL ?>/settings.php" class="nav-item <?= $current_page === 'settings' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
        Settings
      </a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= strtoupper(substr($user['name'] ?? $user['username'], 0, 1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= escape($user['name'] ?? $user['username']) ?></div>
          <div class="user-role"><?= escape($user['role']) ?></div>
        </div>
      </div>
      <a href="<?= ADMIN_URL ?>/logout.php" class="logout-btn">Logout</a>
    </div>
  </aside>
  <main class="admin-main">
    <header class="admin-header">
      <div>
        <h1 class="page-title"><?= escape($page_title) ?></h1>
        <?php if (!empty($page_subtitle)): ?>
          <p class="page-subtitle"><?= escape($page_subtitle) ?></p>
        <?php endif; ?>
      </div>
      <?php if (!empty($page_actions)): ?>
        <div class="page-actions"><?= $page_actions ?></div>
      <?php endif; ?>
    </header>
    <div class="admin-content">
