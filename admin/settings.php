<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role(['admin']);

$page_title = 'Site Settings';
$page_subtitle = 'Manage general website settings and configuration.';

$msg = null;
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $settings_to_save = [
        'site_name',
        'site_tagline',
        'contact_email',
        'contact_phone',
        'contact_address',
        'social_facebook',
        'social_instagram',
        'social_linkedin',
        'social_youtube',
        'social_twitter',
        'whatsapp_number',
        'google_analytics_id',
        'google_maps_embed',
    ];
    
    foreach ($settings_to_save as $key) {
        if (isset($_POST[$key])) {
            db_set_setting($key, trim($_POST[$key]));
        }
    }
    
    log_activity('update_settings', []);
    $msg = 'Settings saved successfully';
}

$settings = db_get_all_settings();

include __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>"><?= escape($msg) ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="card-title">General Settings</div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Site Name</label>
                <input type="text" class="form-input" name="site_name" value="<?= escape($settings['site_name'] ?? 'Dr. Ahmed Zaki') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Site Tagline</label>
                <input type="text" class="form-input" name="site_tagline" value="<?= escape($settings['site_tagline'] ?? 'Orthopedic Surgeon') ?>">
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="card-title">Contact Information</div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" name="contact_email" value="<?= escape($settings['contact_email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" class="form-input" name="contact_phone" value="<?= escape($settings['contact_phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">WhatsApp Number</label>
                <input type="text" class="form-input" name="whatsapp_number" value="<?= escape($settings['whatsapp_number'] ?? '') ?>" placeholder="+971501234567">
                <div class="form-help">Include country code without spaces</div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Address</label>
            <textarea class="form-textarea" name="contact_address" rows="2"><?= escape($settings['contact_address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Google Maps Embed Code</label>
            <textarea class="form-textarea" name="google_maps_embed" rows="3" placeholder="<iframe src='...'></iframe>"><?= escape($settings['google_maps_embed'] ?? '') ?></textarea>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="card-title">Social Media Links</div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Facebook URL</label>
                <input type="url" class="form-input" name="social_facebook" value="<?= escape($settings['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/...">
            </div>
            <div class="form-group">
                <label class="form-label">Instagram URL</label>
                <input type="url" class="form-input" name="social_instagram" value="<?= escape($settings['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/...">
            </div>
            <div class="form-group">
                <label class="form-label">LinkedIn URL</label>
                <input type="url" class="form-input" name="social_linkedin" value="<?= escape($settings['social_linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/...">
            </div>
            <div class="form-group">
                <label class="form-label">YouTube URL</label>
                <input type="url" class="form-input" name="social_youtube" value="<?= escape($settings['social_youtube'] ?? '') ?>" placeholder="https://youtube.com/...">
            </div>
            <div class="form-group">
                <label class="form-label">Twitter/X URL</label>
                <input type="url" class="form-input" name="social_twitter" value="<?= escape($settings['social_twitter'] ?? '') ?>" placeholder="https://twitter.com/...">
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="card-title">Analytics & Tracking</div>
        </div>
        <div class="form-group">
            <label class="form-label">Google Analytics ID</label>
            <input type="text" class="form-input" name="google_analytics_id" value="<?= escape($settings['google_analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
            <div class="form-help">Enter your Google Analytics 4 measurement ID</div>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary btn-lg">Save Settings</button>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
