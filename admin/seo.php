<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role(['admin', 'editor']);

$page_title = 'SEO Settings';
$page_subtitle = 'Edit page titles, meta descriptions, and Open Graph tags per page.';

$file = $_GET['file'] ?? '';
$msg = null; $msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $file = $_POST['file'] ?? '';
    $abs = realpath(SITE_ROOT . '/' . $file);
    if (!$abs || strpos($abs, realpath(SITE_ROOT)) !== 0) {
        $msg = 'Invalid file'; $msg_type = 'error';
    } elseif (!is_writable($abs)) {
        $msg = 'File not writable'; $msg_type = 'error';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $og_title = trim($_POST['og_title'] ?? '');
        $og_description = trim($_POST['og_description'] ?? '');
        $og_image = trim($_POST['og_image'] ?? '');
        $canonical = trim($_POST['canonical'] ?? '');
        $robots = $_POST['robots'] ?? 'index,follow';

        backup_file($abs);
        $html = file_get_contents($abs);
        $html = update_meta($html, $title, $description, $og_title, $og_description, $og_image, $canonical, $robots);

        if (file_put_contents($abs, $html) === false) {
            $msg = 'Failed to save'; $msg_type = 'error';
        } else {
            log_activity('update_seo', ['file' => $file]);
            $msg = 'SEO settings saved successfully';
        }
    }
}

$pages = list_html_pages();
$current_meta = $file ? extract_meta(realpath(SITE_ROOT . '/' . $file)) : null;

include __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>"><?= escape($msg) ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header">
    <div class="card-title">Select a page to edit</div>
  </div>
  <form method="GET">
    <select name="file" class="form-select" onchange="this.form.submit()" required>
      <option value="">— Choose a page —</option>
      <?php foreach ($pages as $p): ?>
        <option value="<?= escape($p['path']) ?>" <?= $file === $p['path'] ? 'selected' : '' ?>>
          [<?= escape($p['type']) ?>] <?= escape($p['title']) ?> · <?= escape($p['url']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($current_meta): ?>
<div class="card">
  <div class="card-header">
    <div class="card-title">Editing: <?= escape($file) ?></div>
    <a href="<?= ADMIN_URL ?>/edit.php?file=<?= urlencode($file) ?>" class="btn btn-secondary btn-sm">Visual editor</a>
  </div>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="file" value="<?= escape($file) ?>">

    <div class="form-group">
      <label class="form-label">Page title <span class="text-muted">(50-60 chars optimal)</span></label>
      <input type="text" class="form-input" name="title" value="<?= escape($current_meta['title']) ?>" maxlength="120" id="seo-title">
      <div class="form-help"><span id="title-count">0</span> chars</div>
    </div>

    <div class="form-group">
      <label class="form-label">Meta description <span class="text-muted">(150-160 chars optimal)</span></label>
      <textarea class="form-textarea" name="description" rows="3" maxlength="300" id="seo-desc"><?= escape($current_meta['description']) ?></textarea>
      <div class="form-help"><span id="desc-count">0</span> chars</div>
    </div>

    <div class="form-group">
      <label class="form-label">Canonical URL <span class="text-muted">(leave empty to use page URL)</span></label>
      <input type="url" class="form-input" name="canonical" value="<?= escape($current_meta['canonical']) ?>" placeholder="https://drazaki.com/treatments/">
    </div>

    <div class="form-group">
      <label class="form-label">Robots</label>
      <select class="form-select" name="robots">
        <option value="index,follow" <?= $current_meta['robots'] === 'index,follow' ? 'selected' : '' ?>>Index &amp; follow (recommended)</option>
        <option value="index,nofollow" <?= $current_meta['robots'] === 'index,nofollow' ? 'selected' : '' ?>>Index, no follow</option>
        <option value="noindex,follow" <?= $current_meta['robots'] === 'noindex,follow' ? 'selected' : '' ?>>No index, follow</option>
        <option value="noindex,nofollow" <?= $current_meta['robots'] === 'noindex,nofollow' ? 'selected' : '' ?>>No index, no follow (hide from search)</option>
      </select>
    </div>

    <hr style="margin:24px 0; border:0; border-top:1px solid var(--border);">
    <h3 style="font-size:14px; margin-bottom:12px;">Social sharing (Open Graph)</h3>

    <div class="form-group">
      <label class="form-label">OG title <span class="text-muted">(falls back to page title)</span></label>
      <input type="text" class="form-input" name="og_title" value="<?= escape($current_meta['og_title']) ?>" maxlength="120">
    </div>

    <div class="form-group">
      <label class="form-label">OG description</label>
      <textarea class="form-textarea" name="og_description" rows="2" maxlength="300"><?= escape($current_meta['og_description']) ?></textarea>
    </div>

    <div class="form-group">
      <label class="form-label">OG image URL <span class="text-muted">(1200x630 ideal)</span></label>
      <input type="text" class="form-input" name="og_image" value="<?= escape($current_meta['og_image']) ?>">
      <?php if ($current_meta['og_image']): ?>
        <img src="<?= escape($current_meta['og_image']) ?>" style="margin-top:8px; max-width:300px; border-radius:6px; border:1px solid var(--border);">
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">Save SEO settings</button>
  </form>
</div>
<?php endif; ?>

<script>
function updateCounts() {
  const t = document.getElementById('seo-title');
  const d = document.getElementById('seo-desc');
  if (t) document.getElementById('title-count').textContent = t.value.length;
  if (d) document.getElementById('desc-count').textContent = d.value.length;
}
document.querySelectorAll('#seo-title, #seo-desc').forEach(el => el.addEventListener('input', updateCounts));
updateCounts();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
function extract_meta($filepath) {
    $html = file_get_contents($filepath);
    $get = function ($pattern) use ($html) {
        return preg_match($pattern, $html, $m) ? html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8') : '';
    };
    return [
        'title' => $get('/<title[^>]*>(.*?)<\/title>/is'),
        'description' => $get('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)["\']/is'),
        'og_title' => $get('/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']*)["\']/is'),
        'og_description' => $get('/<meta\s+property=["\']og:description["\']\s+content=["\']([^"\']*)["\']/is'),
        'og_image' => $get('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']*)["\']/is'),
        'canonical' => $get('/<link\s+rel=["\']canonical["\']\s+href=["\']([^"\']*)["\']/is'),
        'robots' => $get('/<meta\s+name=["\']robots["\']\s+content=["\']([^"\']*)["\']/is') ?: 'index,follow',
    ];
}

function update_meta($html, $title, $description, $og_title, $og_description, $og_image, $canonical, $robots) {
    $title_safe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $description_safe = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    $og_title_safe = htmlspecialchars($og_title ?: $title, ENT_QUOTES, 'UTF-8');
    $og_description_safe = htmlspecialchars($og_description ?: $description, ENT_QUOTES, 'UTF-8');
    $og_image_safe = htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8');
    $canonical_safe = htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8');
    $robots_safe = htmlspecialchars($robots, ENT_QUOTES, 'UTF-8');

    $html = preg_replace('/<title[^>]*>.*?<\/title>/is', '<title>' . $title_safe . '</title>', $html, 1);
    $html = upsert_meta($html, '/<meta\s+name=["\']description["\'][^>]*>/is', '<meta name="description" content="' . $description_safe . '">');
    $html = upsert_meta($html, '/<meta\s+property=["\']og:title["\'][^>]*>/is', '<meta property="og:title" content="' . $og_title_safe . '">');
    $html = upsert_meta($html, '/<meta\s+property=["\']og:description["\'][^>]*>/is', '<meta property="og:description" content="' . $og_description_safe . '">');
    if ($og_image) {
        $html = upsert_meta($html, '/<meta\s+property=["\']og:image["\'][^>]*>/is', '<meta property="og:image" content="' . $og_image_safe . '">');
    }
    if ($canonical) {
        $html = upsert_meta($html, '/<link\s+rel=["\']canonical["\'][^>]*>/is', '<link rel="canonical" href="' . $canonical_safe . '">');
    }
    $html = upsert_meta($html, '/<meta\s+name=["\']robots["\'][^>]*>/is', '<meta name="robots" content="' . $robots_safe . '">');
    return $html;
}

function upsert_meta($html, $pattern, $replacement) {
    if (preg_match($pattern, $html)) {
        return preg_replace($pattern, $replacement, $html, 1);
    }
    // Insert before </head>
    return preg_replace('/<\/head>/i', $replacement . "\n</head>", $html, 1);
}
?>
