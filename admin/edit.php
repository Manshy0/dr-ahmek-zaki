<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role(['admin', 'editor', 'author']);

$file = $_GET['file'] ?? '';
if (!$file) { header('Location: ' . ADMIN_URL . '/pages.php'); exit; }
$abs = realpath(SITE_ROOT . '/' . $file);
if (!$abs || strpos($abs, realpath(SITE_ROOT)) !== 0) { http_response_code(403); die('Invalid file'); }
if (!file_exists($abs)) { http_response_code(404); die('File not found'); }

$user = current_user();
$page_title_text = extract_title($abs);
$serve_url = ADMIN_URL . '/api/serve.php?file=' . urlencode($file);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Editing: <?= escape($page_title_text) ?></title>
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/admin.css?v=2">
<meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body style="margin:0;">
<div class="editor-shell">
  <div class="editor-toolbar">
    <div class="group">
      <a href="<?= ADMIN_URL ?>/pages.php" class="btn btn-secondary btn-sm">← Back</a>
      <div class="file-info">
        <b><?= escape($page_title_text) ?></b>
        <span style="color:#94a3b8;"> · <?= escape($file) ?></span>
      </div>
    </div>
    <div class="group">
      <span id="dirty-status" class="file-info">No changes</span>
      <button id="discard-btn" class="btn btn-secondary btn-sm" disabled>Discard</button>
      <button id="save-btn" class="btn btn-primary btn-sm" disabled>Save changes</button>
    </div>
  </div>
  <div class="editor-iframe-wrap">
    <iframe id="editor-frame" class="editor-iframe" src="<?= escape($serve_url) ?>"></iframe>
    <div id="editor-status" class="editor-status">Saving...</div>
  </div>
</div>

<script>
const frame = document.getElementById('editor-frame');
const saveBtn = document.getElementById('save-btn');
const discardBtn = document.getElementById('discard-btn');
const dirtyStatus = document.getElementById('dirty-status');
const editorStatus = document.getElementById('editor-status');
let dirtyCount = 0;

function setDirty(count) {
  dirtyCount = count;
  if (count > 0) {
    dirtyStatus.textContent = count + ' unsaved change' + (count > 1 ? 's' : '');
    dirtyStatus.style.color = '#fbbf24';
    saveBtn.disabled = false;
    discardBtn.disabled = false;
  } else {
    dirtyStatus.textContent = 'No changes';
    dirtyStatus.style.color = '#cbd5e1';
    saveBtn.disabled = true;
    discardBtn.disabled = true;
  }
}

function showStatus(msg, type) {
  editorStatus.textContent = msg;
  editorStatus.style.background = type === 'error' ? '#dc2626' : (type === 'success' ? '#16a34a' : '#0f172a');
  editorStatus.classList.add('show');
  setTimeout(() => editorStatus.classList.remove('show'), 3000);
}

window.addEventListener('message', (e) => {
  const data = e.data || {};
  if (data.type === 'editor:dirty') setDirty(data.count);
  if (data.type === 'editor:saved') {
    if (data.result.ok) {
      showStatus('Saved ' + data.result.applied + ' change(s)', 'success');
      setDirty(0);
      // Reload iframe to get fresh saved version
      setTimeout(() => frame.src = frame.src, 600);
    } else {
      showStatus('Save failed: ' + (data.result.error || 'Unknown error'), 'error');
    }
  }
  if (data.type === 'editor:ready') {
    showStatus('Click any text or image to edit', 'info');
  }
});

saveBtn.onclick = () => {
  if (dirtyCount === 0) return;
  showStatus('Saving...', 'info');
  saveBtn.disabled = true;
  frame.contentWindow.postMessage({ type: 'editor:save' }, '*');
};

discardBtn.onclick = () => {
  if (!confirm('Discard all unsaved changes?')) return;
  frame.contentWindow.postMessage({ type: 'editor:discard' }, '*');
  setDirty(0);
};

window.addEventListener('beforeunload', (e) => {
  if (dirtyCount > 0) {
    e.preventDefault();
    e.returnValue = '';
  }
});

// Save on Ctrl+S
window.addEventListener('keydown', (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    saveBtn.click();
  }
});
</script>
</body>
</html>
