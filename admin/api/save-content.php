<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/dom_walker.php';
require_role(['admin', 'editor', 'author']);
verify_csrf();

/**
 * Saves a list of element edits to a target HTML file.
 * Body JSON: { file: 'blogs/x/index.htm', edits: [{ id, type, value, attr? }] }
 * "id" matches data-ie-id assigned by serve.php (deterministic walk).
 */

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) json_response(['ok' => false, 'error' => 'Invalid JSON'], 400);

$file = $input['file'] ?? '';
$edits = $input['edits'] ?? [];

if (!$file) json_response(['ok' => false, 'error' => 'Missing file'], 400);
if (!is_array($edits) || !count($edits)) json_response(['ok' => false, 'error' => 'No edits to save']);

$abs = realpath(SITE_ROOT . '/' . $file);
if (!$abs || strpos($abs, realpath(SITE_ROOT)) !== 0)
    json_response(['ok' => false, 'error' => 'Invalid path'], 403);
if (!file_exists($abs)) json_response(['ok' => false, 'error' => 'File not found'], 404);
if (!is_writable($abs)) json_response(['ok' => false, 'error' => 'File not writable. Check Hostinger file permissions (644).'], 403);

// Backup
$backup = backup_file($abs);

// Parse original file with the same walker so IDs match the iframe
$html = file_get_contents($abs);
$dom = load_html_dom($html);
$idMap = build_id_map($dom);

$applied = 0;
$failed = [];

foreach ($edits as $edit) {
    $id = $edit['id'] ?? null;
    $type = $edit['type'] ?? 'text';
    $value = $edit['value'] ?? '';
    $attr = $edit['attr'] ?? '';

    if ($id === null || !isset($idMap[(string)$id])) { $failed[] = $edit; continue; }
    $node = $idMap[(string)$id];

    try {
        if ($type === 'text') {
            // Replace innerHTML safely
            $fragment = $dom->createDocumentFragment();
            $payload = mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8');
            @$fragment->appendXML($payload);
            while ($node->firstChild) $node->removeChild($node->firstChild);
            if ($fragment->hasChildNodes()) {
                $node->appendChild($fragment);
            } else {
                $node->appendChild($dom->createTextNode($value));
            }
            $applied++;
        } elseif ($type === 'html') {
            $tmp = new DOMDocument();
            @$tmp->loadHTML('<?xml encoding="UTF-8"><div id="__wrap__">' . $value . '</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            while ($node->firstChild) $node->removeChild($node->firstChild);
            $wrap = $tmp->getElementById('__wrap__');
            if ($wrap) {
                foreach (iterator_to_array($wrap->childNodes) as $child) {
                    $node->appendChild($dom->importNode($child, true));
                }
            }
            $applied++;
        } elseif ($type === 'image-src') {
            if ($node instanceof DOMElement) {
                $node->setAttribute('src', $value);
                if ($node->hasAttribute('srcset')) $node->removeAttribute('srcset');
                if ($node->hasAttribute('data-src')) $node->setAttribute('data-src', $value);
                $applied++;
            }
        } elseif ($type === 'image-alt') {
            if ($node instanceof DOMElement) { $node->setAttribute('alt', $value); $applied++; }
        } elseif ($type === 'bg-image') {
            if ($node instanceof DOMElement) {
                $style = $node->getAttribute('style');
                $newBg = "background-image: url('" . addslashes($value) . "')";
                if (preg_match('/background-image\s*:[^;]+/i', $style)) {
                    $style = preg_replace('/background-image\s*:[^;]+/i', $newBg, $style);
                } else {
                    $style = trim($style . '; ' . $newBg, '; ');
                }
                $node->setAttribute('style', $style);
                $applied++;
            }
        } elseif ($type === 'attr' && $attr) {
            if ($node instanceof DOMElement) { $node->setAttribute($attr, $value); $applied++; }
        }
    } catch (Throwable $e) {
        $failed[] = $edit;
    }
}

// Strip any stray editor metadata before saving
$xpath = new DOMXPath($dom);
foreach ($xpath->query('//*[@data-ie-id]') as $el) {
    $el->removeAttribute('data-ie-id');
}

$out = serialize_dom($dom);

// Write atomically
$tmp = $abs . '.tmp';
if (file_put_contents($tmp, $out) === false) {
    json_response(['ok' => false, 'error' => 'Failed to write temp file. Check folder permissions.'], 500);
}
if (!rename($tmp, $abs)) {
    @unlink($tmp);
    json_response(['ok' => false, 'error' => 'Failed to replace file. Check folder permissions.'], 500);
}

log_activity('edit_page', ['file' => $file, 'edits' => $applied, 'failed' => count($failed)]);

json_response([
    'ok' => true,
    'applied' => $applied,
    'failed' => count($failed),
    'backup' => $backup ? basename($backup) : null,
]);
