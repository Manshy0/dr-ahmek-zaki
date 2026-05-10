<?php
/**
 * Admin Panel Configuration
 * Edit these values to match your setup
 */

// Site root (one level up from /admin)
define('SITE_ROOT', dirname(dirname(__DIR__)));
define('ADMIN_ROOT', dirname(__DIR__));
define('DATA_DIR', ADMIN_ROOT . '/data');
define('BACKUP_DIR', ADMIN_ROOT . '/backups');
define('UPLOADS_DIR', SITE_ROOT . '/wp-content/uploads/custom');
define('UPLOADS_URL', '/wp-content/uploads/custom');

// Admin URL prefix
define('ADMIN_URL', '/admin');

// Session settings
define('SESSION_LIFETIME', 60 * 60 * 8); // 8 hours
define('SESSION_NAME', 'drazaki_admin');

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 60 * 15); // 15 minutes

// File editing
define('MAX_BACKUP_FILES', 20); // Keep last 20 backups per file
define('ALLOWED_EDIT_EXTENSIONS', ['htm', 'html']);

// Image uploads
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);

// Roles
define('ROLES', [
    'admin'       => 'Administrator (full control)',
    'editor'      => 'Editor (edit all content)',
    'author'      => 'Author (own posts only)',
    'contributor' => 'Contributor (draft only)',
]);

// Timezone
date_default_timezone_set('Asia/Dubai');

// Error display (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ADMIN_ROOT . '/data/error.log');

// Ensure data directories exist
foreach ([DATA_DIR, BACKUP_DIR, UPLOADS_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Block direct access to data dir
$htaccess = DATA_DIR . '/.htaccess';
if (!file_exists($htaccess)) {
    @file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
}
