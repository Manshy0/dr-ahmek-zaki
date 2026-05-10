<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (current_user()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $r = login($username, $password);
        if ($r['ok']) {
            log_activity('login');
            header('Location: ' . ADMIN_URL . '/index.php');
            exit;
        }
        $error = $r['error'];
    } else {
        $error = 'Please enter username and password';
    }
}
// trigger creation of users.json on first visit
load_users();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Admin Login — Dr. Ahmed Zaki</title>
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/admin.css?v=2">
</head>
<body>
<div class="login-shell">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-mark">DZ</div>
      <div>
        <div class="login-title">Site Manager</div>
        <div class="login-sub">Dr. Ahmed Zaki</div>
      </div>
    </div>
    <h1>Welcome back</h1>
    <p class="login-desc">Sign in to manage your website</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= escape($error) ?></div>
    <?php endif; ?>

    <?php if (file_exists(DATA_DIR . '/INITIAL-PASSWORD.txt')): ?>
      <div class="alert alert-warning">
        <strong>First-time setup detected.</strong> Default credentials saved in
        <code>/admin/data/INITIAL-PASSWORD.txt</code>. Open it via Hostinger File Manager,
        log in, then delete that file.
      </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input class="form-input" type="text" id="username" name="username" required autofocus
               value="<?= escape($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-input" type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
    </form>
  </div>
</div>
</body>
</html>
