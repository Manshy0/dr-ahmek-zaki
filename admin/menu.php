<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role(['admin', 'editor']);

$page_title = 'Menu & Footer';
$page_subtitle = 'Edit navigation links and footer information.';

$msg = null;
$config = load_json('site-config.json', [
    'menu' => [
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'Treatments', 'url' => '/treatments/'],
        ['label' => 'About', 'url' => '/meet-dr-ahmed-zaki/'],
        ['label' => 'Blog', 'url' => '/blogs/'],
        ['label' => 'News', 'url' => '/news/'],
        ['label' => 'Contact', 'url' => '/contact/'],
    ],
    'footer' => [
        'phone' => '+971 50 000 0000',
        'email' => 'info@drazaki.com',
        'address' => 'Dubai, United Arab Emirates',
        'about' => 'Leading orthopedic surgeon in Dubai specializing in shoulder, knee, and joint care.',
        'copyright' => '© ' . date('Y') . ' Dr. Ahmed Zaki. All rights reserved.',
    ],
    'social' => [
        'facebook' => '',
        'instagram' => '',
        'linkedin' => '',
        'youtube' => '',
        'twitter' => '',
    ],
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_menu') {
        $labels = $_POST['label'] ?? [];
        $urls = $_POST['url'] ?? [];
        $menu = [];
        for ($i = 0; $i < count($labels); $i++) {
            $l = trim($labels[$i]);
            $u = trim($urls[$i]);
            if ($l && $u) $menu[] = ['label' => $l, 'url' => $u];
        }
        $config['menu'] = $menu;
        save_json('site-config.json', $config);
        log_activity('update_menu');
        $msg = 'Menu saved. Note: changes apply to new pages only — existing HTML pages need to be edited individually.';
    } elseif ($action === 'save_footer') {
        $config['footer'] = [
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'about' => trim($_POST['about'] ?? ''),
            'copyright' => trim($_POST['copyright'] ?? ''),
        ];
        $config['social'] = [
            'facebook' => trim($_POST['facebook'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'linkedin' => trim($_POST['linkedin'] ?? ''),
            'youtube' => trim($_POST['youtube'] ?? ''),
            'twitter' => trim($_POST['twitter'] ?? ''),
        ];
        save_json('site-config.json', $config);
        log_activity('update_footer');
        $msg = 'Footer info saved.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-info"><?= escape($msg) ?></div>
<?php endif; ?>

<div class="alert alert-warning">
  <strong>Important:</strong> Your site uses static HTML files. Menu and footer info shown below
  is stored in a config file. To apply these to your existing pages, you'll edit them individually
  through the visual editor (the menu and footer appear inside each HTML file).
  Changes here are useful for reference and for newly created posts.
</div>

<div class="card mb-4">
  <div class="card-header">
    <div class="card-title">Main Navigation Menu</div>
  </div>
  <form method="POST" id="menu-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save_menu">
    <div id="menu-items">
      <?php foreach ($config['menu'] as $i => $item): ?>
        <div class="menu-row" style="display:flex; gap:8px; margin-bottom:8px;">
          <input type="text" class="form-input" name="label[]" value="<?= escape($item['label']) ?>" placeholder="Label" style="flex:1;">
          <input type="text" class="form-input" name="url[]" value="<?= escape($item['url']) ?>" placeholder="URL" style="flex:2;">
          <button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()" style="color:var(--danger);">Remove</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="addMenuItem()">+ Add menu item</button>
    <button type="submit" class="btn btn-primary">Save menu</button>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Footer Information</div>
  </div>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save_footer">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="text" class="form-input" name="phone" value="<?= escape($config['footer']['phone']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" class="form-input" name="email" value="<?= escape($config['footer']['email']) ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Address</label>
      <input type="text" class="form-input" name="address" value="<?= escape($config['footer']['address']) ?>">
    </div>
    <div class="form-group">
      <label class="form-label">About / Description</label>
      <textarea class="form-textarea" name="about" rows="3"><?= escape($config['footer']['about']) ?></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Copyright text</label>
      <input type="text" class="form-input" name="copyright" value="<?= escape($config['footer']['copyright']) ?>">
    </div>
    <h3 style="font-size:14px; margin: 16px 0 8px;">Social media</h3>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Facebook URL</label><input type="url" class="form-input" name="facebook" value="<?= escape($config['social']['facebook']) ?>"></div>
      <div class="form-group"><label class="form-label">Instagram URL</label><input type="url" class="form-input" name="instagram" value="<?= escape($config['social']['instagram']) ?>"></div>
      <div class="form-group"><label class="form-label">LinkedIn URL</label><input type="url" class="form-input" name="linkedin" value="<?= escape($config['social']['linkedin']) ?>"></div>
      <div class="form-group"><label class="form-label">YouTube URL</label><input type="url" class="form-input" name="youtube" value="<?= escape($config['social']['youtube']) ?>"></div>
      <div class="form-group"><label class="form-label">Twitter / X URL</label><input type="url" class="form-input" name="twitter" value="<?= escape($config['social']['twitter']) ?>"></div>
    </div>
    <button type="submit" class="btn btn-primary">Save footer</button>
  </form>
</div>

<script>
function addMenuItem() {
  const div = document.createElement('div');
  div.className = 'menu-row';
  div.style.cssText = 'display:flex; gap:8px; margin-bottom:8px;';
  div.innerHTML = '<input type="text" class="form-input" name="label[]" placeholder="Label" style="flex:1;"><input type="text" class="form-input" name="url[]" placeholder="URL" style="flex:2;"><button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()" style="color:var(--danger);">Remove</button>';
  document.getElementById('menu-items').appendChild(div);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
