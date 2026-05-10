<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

/**
 * Serves an HTML file from the site with the inline editor injected.
 * Tags every element with data-ie-id (deterministic walk) so edits can be
 * mapped back to the source file reliably.
 *
 * URL: /admin/api/serve.php?file=blogs/post/index.htm
 */

$file = $_GET['file'] ?? '';
if (!$file) { http_response_code(400); die('Missing file parameter'); }

$abs = realpath(SITE_ROOT . '/' . $file);
if (!$abs || strpos($abs, realpath(SITE_ROOT)) !== 0) {
    http_response_code(403); die('Invalid file path');
}
if (!file_exists($abs)) { http_response_code(404); die('File not found'); }

$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
if (!in_array($ext, ['htm', 'html'])) { http_response_code(400); die('Not an HTML file'); }

$html = file_get_contents($abs);

// Inject deterministic data-ie-id attributes via DOM walk.
require_once __DIR__ . '/../includes/dom_walker.php';
$html = inject_editor_ids($html);

// Compute base href so relative URLs (assets, links) still work
$dir = dirname('/' . $file);
if ($dir === '/.' || $dir === '\\') $dir = '';
$baseHref = rtrim($dir, '/') . '/';

// Inject <base> into <head>
if (preg_match('/<head[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
    $insertPos = $m[0][1] + strlen($m[0][0]);
    $baseTag = "\n<base href=\"" . htmlspecialchars($baseHref, ENT_QUOTES) . "\">\n";
    $html = substr($html, 0, $insertPos) . $baseTag . substr($html, $insertPos);
}

// Inject inline editor before </body>
$editorScript = '
<link rel="stylesheet" href="' . ADMIN_URL . '/assets/inline-editor.css?v=3">
<script>
window.__EDITOR_FILE__ = ' . json_encode($file) . ';
window.__EDITOR_API__ = ' . json_encode(ADMIN_URL . '/api') . ';
window.__EDITOR_CSRF__ = ' . json_encode(csrf_token()) . ';
window.__EDITOR_UPLOADS_URL__ = ' . json_encode(UPLOADS_URL) . ';
</script>
<script src="' . ADMIN_URL . '/assets/inline-editor.js?v=4" defer></script>
';

if (stripos($html, '</body>') !== false) {
    $html = preg_replace('/<\/body>/i', $editorScript . '</body>', $html, 1);
} else {
    $html .= $editorScript;
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store');
echo $html;
