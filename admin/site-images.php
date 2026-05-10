<?php
/**
 * Site Images Manager
 * Scans the entire site for images and allows replacement via database
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$current_page = 'site-images';
$page_title = 'Site Images';
$page_subtitle = 'Replace any image across your website';
$message = '';
$message_type = '';

// Handle replacement upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'replace_image' && isset($_FILES['new_image'])) {
        $original_path = $_POST['original_path'] ?? '';
        $alt_text = $_POST['alt_text'] ?? '';
        if (empty($original_path)) {
            $message = 'Original image path is required';
            $message_type = 'error';
        } else {
            $file = $_FILES['new_image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $message = 'Upload failed (error code: ' . $file['error'] . ')';
                $message_type = 'error';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_IMAGE_TYPES)) {
                    $message = 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_IMAGE_TYPES);
                    $message_type = 'error';
                } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
                    $message = 'File too large. Max size: ' . format_size(MAX_UPLOAD_SIZE);
                    $message_type = 'error';
                } else {
                    $upload_dir = SITE_ROOT . '/wp-content/uploads/custom';
                    if (!is_dir($upload_dir)) {
                        @mkdir($upload_dir, 0755, true);
                    }
                    $basename = pathinfo($file['name'], PATHINFO_FILENAME);
                    $basename = preg_replace('/[^a-zA-Z0-9_-]/', '-', $basename);
                    $new_filename = $basename . '-' . time() . '.' . $ext;
                    $new_filepath = $upload_dir . '/' . $new_filename;
                    $new_url = '/wp-content/uploads/custom/' . $new_filename;
                    if (move_uploaded_file($file['tmp_name'], $new_filepath)) {
                        db_save_image_replacement($original_path, $new_url, $alt_text);
                        @db_add_media($new_filename, $new_url, 'image', $file['size']);
                        log_activity('Replaced image', ['original' => $original_path, 'new' => $new_url]);
                        $message = 'Image replaced successfully! Refresh the website to see changes.';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to save uploaded file. Check folder permissions.';
                        $message_type = 'error';
                    }
                }
            }
        }
    }
    if ($_POST['action'] === 'remove_replacement') {
        $original_path = $_POST['original_path'] ?? '';
        db_delete_image_replacement($original_path);
        log_activity('Restored original image', ['original' => $original_path]);
        $message = 'Original image restored.';
        $message_type = 'success';
    }
}

// Scan all HTML files for images
function scan_site_images_all() {
    $seen = [];
    $images = [];
    $root = realpath(SITE_ROOT);
    if (!$root) return [];

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iter as $file) {
        if ($file->isDir()) continue;
        $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $rel = str_replace('\\', '/', $rel);

        if (strpos($rel, 'admin/') === 0) continue;
        if (strpos($rel, 'wp-content/uploads/custom/') === 0) continue;
        if (strpos($rel, '_new_assets/') === 0) continue;
        if (strpos($rel, 'wp-content/litespeed/') === 0) continue;
        if (strpos($rel, 'wp-includes/') === 0) continue;

        $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        if (!in_array($ext, ['htm', 'html'])) continue;

        $content = @file_get_contents($file->getPathname());
        if (!$content) continue;

        preg_match_all('#["\']((?:/|\.\./|\./)?(?:wp-content/uploads/[^"\'\s]+\.(?:jpg|jpeg|png|webp|gif|svg)))["\']#i', $content, $matches);

        foreach ($matches[1] as $img_url) {
            $img_url = trim($img_url);
            if (strpos($img_url, '/') !== 0) {
                $img_url = '/' . ltrim($img_url, './');
            }
            if (isset($seen[$img_url])) continue;
            $seen[$img_url] = true;
            $img_path = $root . $img_url;
            $exists = file_exists($img_path);
            $images[] = [
                'url' => $img_url,
                'exists' => $exists,
                'size' => $exists ? filesize($img_path) : 0,
                'filename' => basename($img_url),
            ];
        }
    }

    usort($images, function($a, $b) {
        return strcmp($a['filename'], $b['filename']);
    });

    return $images;
}

try {
    $all_images = scan_site_images_all();
    $replacements = db_get_all_image_replacements();
} catch (Exception $e) {
    $all_images = [];
    $replacements = [];
    if (!$message) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$filtered = array_filter($all_images, function($img) use ($filter, $search, $replacements) {
    if ($search !== '' && stripos($img['filename'], $search) === false && stripos($img['url'], $search) === false) {
        return false;
    }
    if ($filter === 'replaced' && !isset($replacements[$img['url']])) return false;
    if ($filter === 'original' && isset($replacements[$img['url']])) return false;
    return true;
});

include __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
    <div>
        <h1 style="margin:0 0 4px 0;font-size:26px;">Site Images</h1>
        <p style="color:#64748b;margin:0;">Replace any image across your website without touching code</p>
    </div>
    <div style="display:flex;gap:12px;">
        <div style="padding:8px 16px;background:#f1f5f9;border-radius:8px;font-size:14px;">
            <strong style="color:#0a4d68;font-size:18px;"><?= count($all_images) ?></strong> Total
        </div>
        <div style="padding:8px 16px;background:#ecfdf5;border-radius:8px;font-size:14px;">
            <strong style="color:#059669;font-size:18px;"><?= count($replacements) ?></strong> Replaced
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div style="padding:14px 18px;border-radius:8px;margin-bottom:20px;font-size:14px;<?= $message_type === 'success' ? 'background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;' : 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;' ?>">
        <?= escape($message) ?>
    </div>
<?php endif; ?>

<div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:24px;">
    <form method="get" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
        <input type="search" name="q" value="<?= escape($search) ?>" placeholder="Search by filename..." style="flex:1;min-width:200px;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
        <div style="display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:8px;">
            <a href="?filter=all<?= $search ? '&q='.urlencode($search) : '' ?>" style="padding:8px 16px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:500;<?= $filter==='all' ? 'background:white;color:#0a4d68;box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:#64748b;' ?>">All</a>
            <a href="?filter=original<?= $search ? '&q='.urlencode($search) : '' ?>" style="padding:8px 16px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:500;<?= $filter==='original' ? 'background:white;color:#0a4d68;box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:#64748b;' ?>">Original</a>
            <a href="?filter=replaced<?= $search ? '&q='.urlencode($search) : '' ?>" style="padding:8px 16px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:500;<?= $filter==='replaced' ? 'background:white;color:#0a4d68;box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:#64748b;' ?>">Replaced</a>
        </div>
        <button type="submit" style="padding:10px 20px;background:#0a4d68;color:white;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;">Search</button>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
    <?php foreach ($filtered as $img):
        $is_replaced = isset($replacements[$img['url']]);
        $display_url = $is_replaced ? $replacements[$img['url']] : $img['url'];
    ?>
        <div style="background:white;border:1px solid <?= $is_replaced ? '#10b981' : '#e2e8f0' ?>;border-radius:12px;overflow:hidden;transition:all 0.2s;">
            <div style="position:relative;aspect-ratio:1;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                <?php if ($img['exists'] || $is_replaced): ?>
                    <img src="<?= escape($display_url) ?>" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.parentElement.innerHTML='<div style=color:#94a3b8;font-size:13px;text-align:center;padding:16px;>Image not found</div>'">
                <?php else: ?>
                    <div style="color:#94a3b8;font-size:13px;text-align:center;padding:16px;">Image not found</div>
                <?php endif; ?>
                <?php if ($is_replaced): ?>
                    <div style="position:absolute;top:8px;right:8px;background:#10b981;color:white;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:600;">REPLACED</div>
                <?php endif; ?>
            </div>
            <div style="padding:12px;">
                <div title="<?= escape($img['url']) ?>" style="font-size:13px;font-weight:500;color:#0f172a;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= escape($img['filename']) ?></div>
                <div style="font-size:12px;color:#94a3b8;margin-bottom:12px;">
                    <?= $img['exists'] ? format_size($img['size']) : 'Missing' ?>
                </div>
                <div style="display:flex;gap:6px;">
                    <button type="button" onclick="openReplaceModal('<?= escape($img['url']) ?>', '<?= escape($img['filename']) ?>')" style="flex:1;padding:6px 12px;background:#0a4d68;color:white;border:none;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;">Replace</button>
                    <?php if ($is_replaced): ?>
                        <form method="post" style="display:inline;flex:1;" onsubmit="return confirm('Restore original image?')">
                            <input type="hidden" name="action" value="remove_replacement">
                            <input type="hidden" name="original_path" value="<?= escape($img['url']) ?>">
                            <button type="submit" style="width:100%;padding:6px 12px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;">Restore</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($filtered)): ?>
        <div style="grid-column:1 / -1;text-align:center;padding:60px 20px;color:#94a3b8;background:white;border:1px solid #e2e8f0;border-radius:12px;">
            <p>No images found. Make sure your website pages contain images.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Replace Modal -->
<div id="replaceModal" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
    <div onclick="closeReplaceModal()" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);"></div>
    <div style="position:relative;background:white;border-radius:16px;width:90%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid #e2e8f0;">
            <h2 style="margin:0;font-size:20px;">Replace Image</h2>
            <button type="button" onclick="closeReplaceModal()" style="background:none;border:none;font-size:28px;cursor:pointer;color:#94a3b8;line-height:1;">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="replace_image">
            <input type="hidden" name="original_path" id="modalOriginalPath">
            <div style="padding:24px;">
                <div style="margin-bottom:20px;">
                    <label style="display:block;margin-bottom:8px;font-weight:500;font-size:14px;color:#334155;">Original Image:</label>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;text-align:center;">
                        <img id="modalPreview" src="" alt="" style="max-width:100%;max-height:200px;border-radius:4px;margin-bottom:8px;">
                        <code id="modalPath" style="display:block;font-size:11px;color:#64748b;word-break:break-all;"></code>
                    </div>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;margin-bottom:8px;font-weight:500;font-size:14px;color:#334155;">Upload New Image:</label>
                    <input type="file" name="new_image" accept="image/*" required style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
                    <small style="display:block;margin-top:6px;font-size:12px;color:#94a3b8;">Allowed: JPG, PNG, WEBP, GIF, SVG. Max 10MB. Use same dimensions for best results.</small>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;margin-bottom:8px;font-weight:500;font-size:14px;color:#334155;">Alt Text (for SEO):</label>
                    <input type="text" name="alt_text" placeholder="Describe the image" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:12px;padding:16px 24px;border-top:1px solid #e2e8f0;">
                <button type="button" onclick="closeReplaceModal()" style="padding:10px 20px;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:10px 20px;background:#0a4d68;color:white;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;">Replace Image</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplaceModal(originalPath, filename) {
    document.getElementById('modalOriginalPath').value = originalPath;
    document.getElementById('modalPreview').src = originalPath;
    document.getElementById('modalPath').textContent = originalPath;
    document.getElementById('replaceModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeReplaceModal() {
    document.getElementById('replaceModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeReplaceModal();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
