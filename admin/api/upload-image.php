<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role(['admin', 'editor', 'author']);
verify_csrf();

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    json_response(['ok' => false, 'error' => 'No file uploaded'], 400);
}

$file = $_FILES['file'];
$size = (int)$file['size'];
if ($size <= 0 || $size > MAX_UPLOAD_SIZE) {
    json_response(['ok' => false, 'error' => 'File too large (max ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB)'], 400);
}

$origName = $file['name'];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_IMAGE_TYPES)) {
    json_response(['ok' => false, 'error' => 'Unsupported file type'], 400);
}

// Sanitize filename
$base = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($origName, PATHINFO_FILENAME));
$base = trim(substr($base, 0, 60), '-') ?: 'image';
$year = date('Y');
$month = date('m');
$dir = UPLOADS_DIR . '/' . $year . '/' . $month;
if (!is_dir($dir)) @mkdir($dir, 0755, true);
if (!is_dir($dir) || !is_writable($dir)) {
    json_response(['ok' => false, 'error' => 'Upload directory not writable. Check permissions of /wp-content/uploads/custom'], 500);
}

$name = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
$dest = $dir . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_response(['ok' => false, 'error' => 'Failed to move uploaded file'], 500);
}
@chmod($dest, 0644);

$url = UPLOADS_URL . '/' . $year . '/' . $month . '/' . $name;

log_activity('upload_image', ['file' => $url, 'size' => $size]);

json_response([
    'ok' => true,
    'url' => $url,
    'filename' => $name,
    'size' => $size,
]);
