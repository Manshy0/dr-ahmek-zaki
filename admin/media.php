<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$page_title = 'Media Library';
$page_subtitle = 'Upload, browse, and manage images for your site.';

// Scan uploads dir
$files = [];
if (is_dir(UPLOADS_DIR)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(UPLOADS_DIR, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if ($file->isDir()) continue;
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ALLOWED_IMAGE_TYPES)) continue;
        $rel = str_replace(UPLOADS_DIR, '', $file->getPathname());
        $rel = str_replace('\\', '/', $rel);
        $files[] = [
            'name' => $file->getFilename(),
            'url'  => UPLOADS_URL . $rel,
            'size' => $file->getSize(),
            'mtime'=> $file->getMTime(),
        ];
    }
    usort($files, fn($a,$b) => $b['mtime'] - $a['mtime']);
}

include __DIR__ . '/includes/header.php';
?>

<div class="card mb-4">
  <div class="dropzone" id="dropzone">
    <div class="dropzone-icon">↑</div>
    <div style="font-weight:500; margin-bottom:4px;">Drop images here or click to upload</div>
    <div class="text-muted text-sm">JPG, PNG, GIF, WebP, SVG · Max <?= MAX_UPLOAD_SIZE / 1024 / 1024 ?>MB</div>
    <input type="file" id="file-input" accept="image/*" multiple style="display:none;">
  </div>
  <div id="upload-progress" style="display:none; margin-top:16px;"></div>
</div>

<?php if (empty($files)): ?>
  <div class="card empty">
    <div class="empty-icon">·</div>
    <div class="empty-title">No images yet</div>
    <p>Upload your first image using the dropzone above.</p>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= count($files) ?> images</div>
    </div>
    <div class="media-grid" id="media-grid">
      <?php foreach ($files as $f): ?>
        <div class="media-item" data-url="<?= escape($f['url']) ?>" data-name="<?= escape($f['name']) ?>">
          <img src="<?= escape($f['url']) ?>" alt="<?= escape($f['name']) ?>" loading="lazy">
          <div class="media-name"><?= escape($f['name']) ?></div>
          <div class="media-actions">
            <button class="icon-btn" title="Copy URL" onclick="copyUrl(this)">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
            <?php if (can('delete_pages')): ?>
            <button class="icon-btn" title="Delete" onclick="deleteImage(this)">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#dc2626" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
            </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<script>
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('file-input');
const progress = document.getElementById('upload-progress');

dropzone.addEventListener('click', () => fileInput.click());
['dragenter','dragover'].forEach(e => dropzone.addEventListener(e, ev => { ev.preventDefault(); dropzone.classList.add('drag-over'); }));
['dragleave','drop'].forEach(e => dropzone.addEventListener(e, ev => { ev.preventDefault(); dropzone.classList.remove('drag-over'); }));
dropzone.addEventListener('drop', ev => uploadFiles(ev.dataTransfer.files));
fileInput.addEventListener('change', () => uploadFiles(fileInput.files));

async function uploadFiles(files) {
  if (!files.length) return;
  progress.style.display = 'block';
  progress.innerHTML = '';
  let success = 0;
  for (const file of files) {
    const row = document.createElement('div');
    row.style.cssText = 'padding:8px 0; font-size:13px;';
    row.textContent = 'Uploading ' + file.name + '...';
    progress.appendChild(row);
    const fd = new FormData();
    fd.append('file', file);
    const r = await Admin.api('<?= ADMIN_URL ?>/api/upload-image.php', fd);
    if (r.ok) {
      row.innerHTML = '<span style="color:var(--success);">✓</span> ' + file.name;
      success++;
    } else {
      row.innerHTML = '<span style="color:var(--danger);">✗</span> ' + file.name + ' — ' + (r.error || 'failed');
    }
  }
  if (success > 0) {
    Admin.toast('Uploaded ' + success + ' image(s)', 'success');
    setTimeout(() => location.reload(), 1000);
  }
}

function copyUrl(btn) {
  const item = btn.closest('.media-item');
  navigator.clipboard.writeText(item.dataset.url);
  Admin.toast('URL copied to clipboard', 'success');
}

async function deleteImage(btn) {
  const item = btn.closest('.media-item');
  if (!confirm('Delete this image?\n\n' + item.dataset.name)) return;
  const r = await Admin.api('<?= ADMIN_URL ?>/api/delete-image.php', { url: item.dataset.url });
  if (r.ok) {
    item.remove();
    Admin.toast('Image deleted', 'success');
  } else {
    Admin.toast('Delete failed: ' + (r.error || 'Unknown'), 'error');
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
