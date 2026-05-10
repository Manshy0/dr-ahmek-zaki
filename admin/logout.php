<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
if (current_user()) log_activity('logout');
logout();
header('Location: ' . ADMIN_URL . '/login.php');
exit;
