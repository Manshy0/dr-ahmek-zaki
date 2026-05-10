<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role(['admin', 'editor']);
verify_csrf();

$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? '';
if (!$url) json_response(['ok' => false, 'error' => 'Missing url'], 400);

// Only allow deletion within UPLOADS_URL
if (strpos($url, UPLOADS_URL) !== 0) {
    json_response(['ok' => false, 'error' => 'Cannot delete files outside the upload folder'], 403);
}

$rel = substr($url, strlen(UPLOADS_URL));
$abs = realpath(UPLOADS_DIR . $rel);
if (!$abs || strpos($abs, realpath(UPLOADS_DIR)) !== 0)
    json_response(['ok' => false, 'error' => 'Invalid path'], 403);
if (!file_exists($abs)) json_response(['ok' => false, 'error' => 'Not found'], 404);

if (!@unlink($abs)) json_response(['ok' => false, 'error' => 'Failed to delete'], 500);

log_activity('delete_image', ['url' => $url]);
json_response(['ok' => true]);
