<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$type = $_GET['type'] ?? 'blog';
$type = in_array($type, ['blog', 'news']) ? $type : 'blog';
$action = $_GET['action'] ?? '';

$page_title = ucfirst($type) . ' Posts';
$page_subtitle = $type === 'blog'
    ? 'Manage blog posts. Create new posts or edit existing ones.'
    : 'Manage news items and announcements.';

$page_actions = '';
if (can('publish_posts')) {
    $page_actions = '<a href="' . ADMIN_URL . '/posts.php?type=' . $type . '&action=new" class="btn btn-primary">+ New ' . ucfirst($type) . ' post</a>';
}

// Handle new post creation
if ($action === 'new' && $_SERVER['REQUEST_METHOD'] === 'POST' && can('publish_posts')) {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $featured_image = trim($_POST['featured_image'] ?? '');

    if (!$title) {
        $err = 'Title is required';
    } else {
        if (!$slug) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
            $slug = trim($slug, '-');
        }
        $slug = preg_replace('/[^a-z0-9-]/i', '', $slug);
        $base_dir = SITE_ROOT . '/' . $type . '/' . $slug;
        if (file_exists($base_dir)) {
            $err = 'A post with this slug already exists';
        } else {
            if (!mkdir($base_dir, 0755, true)) {
                $err = 'Failed to create directory. Check permissions.';
            } else {
                $template = generate_post_template($type, $title, $excerpt, $content, $featured_image, $slug);
                $file_path = $base_dir . '/index.htm';
                if (file_put_contents($file_path, $template) === false) {
                    @rmdir($base_dir);
                    $err = 'Failed to write file';
                } else {
                    log_activity('create_post', ['type' => $type, 'slug' => $slug]);
                    $rel = $type . '/' . $slug . '/index.htm';
                    header('Location: ' . ADMIN_URL . '/edit.php?file=' . urlencode($rel));
                    exit;
                }
            }
        }
    }
}

$pages = list_html_pages();
$posts = array_values(array_filter($pages, fn($p) => $p['type'] === $type));

include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'new' && can('publish_posts')): ?>
  <?php if (!empty($err)): ?><div class="alert alert-error"><?= escape($err) ?></div><?php endif; ?>
  <div class="card">
    <div class="card-header">
      <div class="card-title">Create new <?= escape($type) ?> post</div>
      <a href="<?= ADMIN_URL ?>/posts.php?type=<?= escape($type) ?>" class="btn btn-ghost btn-sm">Cancel</a>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label class="form-label">Title <span style="color:var(--danger)">*</span></label>
        <input type="text" class="form-input" name="title" required value="<?= escape($_POST['title'] ?? '') ?>" autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">URL slug <span class="text-muted">(auto-generated if empty)</span></label>
        <input type="text" class="form-input" name="slug" value="<?= escape($_POST['slug'] ?? '') ?>" placeholder="my-new-post">
        <div class="form-help">Final URL will be: /<?= escape($type) ?>/<em>your-slug</em>/</div>
      </div>
      <div class="form-group">
        <label class="form-label">Featured image URL <span class="text-muted">(optional)</span></label>
        <input type="text" class="form-input" name="featured_image" value="<?= escape($_POST['featured_image'] ?? '') ?>" placeholder="<?= UPLOADS_URL ?>/2025/01/cover.jpg">
        <button type="button" class="btn btn-secondary btn-sm mt-3" onclick="pickImage()">Pick from media library</button>
      </div>
      <div class="form-group">
        <label class="form-label">Short excerpt</label>
        <textarea class="form-textarea" name="excerpt" rows="2"><?= escape($_POST['excerpt'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Content (HTML)</label>
        <textarea class="form-textarea" name="content" rows="14" placeholder="<p>Write your post content here. You can include &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;img&gt; tags etc.</p>"><?= escape($_POST['content'] ?? '') ?></textarea>
        <div class="form-help">After creation you can edit visually in the inline editor.</div>
      </div>
      <button type="submit" class="btn btn-primary btn-lg">Create &amp; edit visually</button>
    </form>
  </div>
  <script>
  async function pickImage() {
    // Quick prompt - full media library is on /admin/media.php
    const url = prompt('Enter image URL or upload first via the Media library:');
    if (url) document.querySelector('[name=featured_image]').value = url;
  }
  </script>
<?php else: ?>

<?php if (empty($posts)): ?>
  <div class="card empty">
    <div class="empty-icon">·</div>
    <div class="empty-title">No <?= escape($type) ?> posts yet</div>
    <p>Click "+ New <?= escape($type) ?> post" to create your first one.</p>
  </div>
<?php else: ?>
<div class="table-wrap">
<table class="data-table">
  <thead>
    <tr>
      <th>Title</th>
      <th>URL</th>
      <th>Modified</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($posts as $p): ?>
    <tr>
      <td>
        <div class="col-title"><?= escape($p['title']) ?></div>
      </td>
      <td><a href="<?= escape($p['url']) ?>" target="_blank" class="col-meta"><?= escape($p['url']) ?></a></td>
      <td class="col-meta"><?= relative_time($p['modified']) ?></td>
      <td class="row-actions">
        <a href="<?= escape($p['url']) ?>" target="_blank" class="btn btn-ghost btn-sm">View</a>
        <?php if (can('edit_pages')): ?>
          <a href="<?= ADMIN_URL ?>/edit.php?file=<?= urlencode($p['path']) ?>" class="btn btn-primary btn-sm">Edit</a>
        <?php endif; ?>
        <?php if (can('delete_pages')): ?>
          <button class="btn btn-ghost btn-sm" onclick="deletePost('<?= escape($p['path']) ?>', this)">Delete</button>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<script>
async function deletePost(path, btn) {
  if (!confirm('Delete this post?\n\n' + path + '\n\nA backup will be kept.')) return;
  btn.disabled = true;
  const r = await Admin.api('<?= ADMIN_URL ?>/api/delete-page.php', { path });
  if (r.ok) {
    Admin.toast('Post deleted', 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    Admin.toast('Delete failed: ' + (r.error || 'Unknown'), 'error');
    btn.disabled = false;
  }
}
</script>

<?php endif; // not new ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
function generate_post_template($type, $title, $excerpt, $content, $featured_image, $slug) {
    $title_safe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $excerpt_safe = htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8');
    $date = date('F j, Y');
    $datetime = date('c');
    $img_html = $featured_image ? '<img src="' . htmlspecialchars($featured_image, ENT_QUOTES) . '" alt="' . $title_safe . '" style="width:100%; max-height:420px; object-fit:cover; border-radius:8px;">' : '';
    $content_safe = $content ?: '<p>Start writing your post content here. After publishing, click "Edit" to modify visually.</p>';

    return <<<HTML
<!DOCTYPE html>
<html lang="en-US">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title_safe} | Dr. Ahmed Zaki</title>
<meta name="description" content="{$excerpt_safe}">
<meta property="og:title" content="{$title_safe}">
<meta property="og:description" content="{$excerpt_safe}">
<meta property="og:type" content="article">
<meta property="og:image" content="{$featured_image}">
<link rel="canonical" href="/{$type}/{$slug}/">
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 760px; margin: 0 auto; padding: 32px 20px; line-height: 1.7; color: #0f172a; }
header.post-header { margin-bottom: 32px; }
h1 { font-size: 32px; line-height: 1.2; margin-bottom: 8px; }
.post-meta { color: #64748b; font-size: 14px; margin-bottom: 24px; }
.post-content h2 { font-size: 22px; margin-top: 32px; margin-bottom: 12px; }
.post-content h3 { font-size: 18px; margin-top: 24px; margin-bottom: 8px; }
.post-content p { margin-bottom: 16px; }
.post-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 16px 0; }
.post-content ul, .post-content ol { margin-bottom: 16px; padding-left: 24px; }
.back-link { display: inline-block; margin-bottom: 24px; color: #0f766e; text-decoration: none; }
</style>
</head>
<body>
<a href="/{$type}/" class="back-link">← Back to {$type}</a>
<header class="post-header">
  <h1>{$title_safe}</h1>
  <div class="post-meta">
    <time datetime="{$datetime}">{$date}</time> · By Dr. Ahmed Zaki
  </div>
  {$img_html}
</header>
<article class="post-content">
{$content_safe}
</article>
<footer style="margin-top:48px; padding-top:24px; border-top:1px solid #e2e8f0; color:#64748b; font-size:13px;">
  <a href="/{$type}/" style="color:#0f766e;">← Back to all {$type}</a>
</footer>
</body>
</html>
HTML;
}
?>
