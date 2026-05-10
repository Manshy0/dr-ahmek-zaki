<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$page_title = 'Dashboard';
$page_subtitle = 'Manage your website content from one place';

$pages = list_html_pages();
$blogs = array_filter($pages, fn($p) => $p['type'] === 'blog');
$news  = array_filter($pages, fn($p) => $p['type'] === 'news');
$treatments = array_filter($pages, fn($p) => $p['type'] === 'treatment');
$other = array_filter($pages, fn($p) => $p['type'] === 'page');

$activity = load_json('activity.json', []);
$recent_activity = array_slice($activity, 0, 8);
$recent_pages = array_slice($pages, 0, 6);

include __DIR__ . '/includes/header.php';
?>

<?php if (file_exists(DATA_DIR . '/INITIAL-PASSWORD.txt')): ?>
<div class="alert alert-warning">
  <strong>Security:</strong> Initial password file still exists. Delete <code>/admin/data/INITIAL-PASSWORD.txt</code> via Hostinger File Manager.
</div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Pages</div>
    <div class="stat-value"><?= count($other) + count($treatments) ?></div>
    <div class="stat-trend"><?= count($treatments) ?> treatments</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Blog Posts</div>
    <div class="stat-value"><?= count($blogs) ?></div>
    <div class="stat-trend"><a href="<?= ADMIN_URL ?>/posts.php?type=blog">Manage blog</a></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">News</div>
    <div class="stat-value"><?= count($news) ?></div>
    <div class="stat-trend"><a href="<?= ADMIN_URL ?>/posts.php?type=news">Manage news</a></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Files</div>
    <div class="stat-value"><?= count($pages) ?></div>
    <div class="stat-trend">HTML files tracked</div>
  </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap: 16px;" class="dash-grid">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Recently modified</div>
      <a href="<?= ADMIN_URL ?>/pages.php" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <?php if (empty($recent_pages)): ?>
      <div class="empty"><div class="empty-icon">+</div><div class="empty-title">No pages yet</div></div>
    <?php else: ?>
      <div class="table-wrap" style="border:0;">
        <table class="data-table">
          <thead><tr><th>Title</th><th>Type</th><th>Modified</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($recent_pages as $p): ?>
            <tr>
              <td>
                <div class="col-title"><?= escape($p['title']) ?></div>
                <div class="col-meta"><?= escape($p['url']) ?></div>
              </td>
              <td><span class="badge"><?= escape($p['type']) ?></span></td>
              <td class="col-meta"><?= relative_time($p['modified']) ?></td>
              <td class="row-actions">
                <a href="<?= ADMIN_URL ?>/edit.php?file=<?= urlencode($p['path']) ?>" class="btn btn-sm btn-primary">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">Recent activity</div>
    </div>
    <?php if (empty($recent_activity)): ?>
      <div class="empty"><div class="empty-icon">·</div><div class="empty-title">No activity yet</div></div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($recent_activity as $a): ?>
          <div style="font-size: 13px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
            <div><strong><?= escape($a['user']) ?></strong> <?= escape(str_replace('_', ' ', $a['action'])) ?></div>
            <?php if (!empty($a['details']['file'])): ?>
              <div class="text-muted text-sm"><?= escape($a['details']['file']) ?></div>
            <?php endif; ?>
            <div class="text-muted text-sm"><?= relative_time($a['time']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card mt-4">
  <div class="card-header">
    <div class="card-title">Quick actions</div>
  </div>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
    <a href="<?= ADMIN_URL ?>/pages.php" class="btn btn-secondary" style="justify-content: center; padding: 18px;">Edit Pages</a>
    <a href="<?= ADMIN_URL ?>/posts.php?type=blog&action=new" class="btn btn-secondary" style="justify-content: center; padding: 18px;">New Blog Post</a>
    <a href="<?= ADMIN_URL ?>/posts.php?type=news&action=new" class="btn btn-secondary" style="justify-content: center; padding: 18px;">New News Item</a>
    <a href="<?= ADMIN_URL ?>/media.php" class="btn btn-secondary" style="justify-content: center; padding: 18px;">Upload Media</a>
    <a href="<?= ADMIN_URL ?>/seo.php" class="btn btn-secondary" style="justify-content: center; padding: 18px;">SEO Settings</a>
  </div>
</div>

<style>
@media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr !important; } }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
