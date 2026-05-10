<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role(['admin']);
verify_csrf();

$input = json_decode(file_get_contents('php://input'), true);
$path = $input['path'] ?? '';
if (!$path) json_response(['ok' => false, 'error' => 'Missing path'], 400);

$abs = realpath(SITE_ROOT . '/' . $path);
if (!$abs || strpos($abs, realpath(SITE_ROOT)) !== 0)
    json_response(['ok' => false, 'error' => 'Invalid path'], 403);
if (!file_exists($abs)) json_response(['ok' => false, 'error' => 'Not found'], 404);

// Only allow deleting blogs/* and news/* directories
if (!preg_match('#^(blogs|news)/[^/]+/index\.htm$#', $path)) {
    json_response(['ok' => false, 'error' => 'Only blog/news posts can be deleted'], 403);
}

backup_file($abs);

$dir = dirname($abs);
// Move to backups instead of permanent delete
$dest = BACKUP_DIR . '/deleted_' . date('Ymd_His') . '_' . str_replace('/', '__', $path);
@rename($abs, $dest);

// Try removing empty parent dir
@rmdir($dir);

log_activity('delete_post', ['file' => $path]);

json_response(['ok' => true]);
