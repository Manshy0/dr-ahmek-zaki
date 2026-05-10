<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$page_title = 'Pages';
$page_subtitle = 'All HTML pages on your site. Click Edit to modify content visually.';

$pages = list_html_pages();
$type_filter = $_GET['type'] ?? '';
$search = trim($_GET['q'] ?? '');

$filtered = array_filter($pages, function ($p) use ($type_filter, $search) {
    if ($type_filter && $p['type'] !== $type_filter) return false;
    if ($search) {
        $hay = strtolower($p['title'] . ' ' . $p['url'] . ' ' . $p['path']);
        if (strpos($hay, strtolower($search)) === false) return false;
    }
    return true;
});

$types = [];
foreach ($pages as $p) $types[$p['type']] = ($types[$p['type']] ?? 0) + 1;

include __DIR__ . '/includes/header.php';
?>

<form class="filter-bar" method="GET">
  <div class="search-box">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    <input type="text" class="form-input" name="q" value="<?= escape($search) ?>" placeholder="Search pages by title, URL, or path...">
  </div>
  <select name="type" class="form-select" onchange="this.form.submit()">
    <option value="">All types (<?= count($pages) ?>)</option>
    <?php foreach ($types as $t => $c): ?>
      <option value="<?= escape($t) ?>" <?= $type_filter === $t ? 'selected' : '' ?>><?= escape(ucfirst($t)) ?> (<?= $c ?>)</option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-secondary">Filter</button>
  <?php if ($search || $type_filter): ?>
    <a href="<?= ADMIN_URL ?>/pages.php" class="btn btn-ghost">Clear</a>
  <?php endif; ?>
</form>

<?php if (empty($filtered)): ?>
  <div class="card empty">
    <div class="empty-icon">·</div>
    <div class="empty-title">No pages found</div>
    <p>Try clearing filters or check back later.</p>
  </div>
<?php else: ?>
<div class="table-wrap">
<table class="data-table">
  <thead>
    <tr>
      <th>Title</th>
      <th>Type</th>
      <th>URL</th>
      <th>Modified</th>
      <th>Size</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($filtered as $p): ?>
    <tr>
      <td><div class="col-title"><?= escape($p['title']) ?></div></td>
      <td><span class="badge badge-primary"><?= escape($p['type']) ?></span></td>
      <td><a href="<?= escape($p['url']) ?>" target="_blank" class="col-meta"><?= escape($p['url']) ?></a></td>
      <td class="col-meta"><?= relative_time($p['modified']) ?></td>
      <td class="col-meta"><?= format_size($p['size']) ?></td>
      <td class="row-actions">
        <a href="<?= escape($p['url']) ?>" target="_blank" class="btn btn-ghost btn-sm">View</a>
        <?php if (can('edit_pages')): ?>
          <a href="<?= ADMIN_URL ?>/edit.php?file=<?= urlencode($p['path']) ?>" class="btn btn-primary btn-sm">Edit</a>
        <?php endif; ?>
        <?php if (can('delete_pages') && in_array($p['type'], ['blog', 'news'])): ?>
          <button class="btn btn-ghost btn-sm" onclick="deletePage('<?= escape($p['path']) ?>', this)">Delete</button>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<script>
async function deletePage(path, btn) {
  if (!confirm('Delete this post?\n\n' + path + '\n\nA backup will be kept.')) return;
  btn.disabled = true;
  const r = await Admin.api('<?= ADMIN_URL ?>/api/delete-page.php', { path });
  if (r.ok) {
    Admin.toast('Page deleted', 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    Admin.toast('Delete failed: ' + (r.error || 'Unknown'), 'error');
    btn.disabled = false;
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
