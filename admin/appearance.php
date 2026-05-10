<?php
/**
 * Appearance Settings - Logo, Favicon, Top Bar, Branding (MySQL)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$current_page = 'appearance';
$page_title = 'Appearance';
$page_subtitle = 'Logo, top bar, colors, and branding';
$message = '';
$message_type = '';

// Sanitize helper
if (!function_exists('sanitize')) {
    function sanitize($input) {
        return trim(strip_tags($input ?? ''));
    }
}

// File upload handler
function handle_upload($field_name, $prefix = 'file') {
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$field_name];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_TYPES)) return null;
    if ($file['size'] > MAX_UPLOAD_SIZE) return null;

    $upload_dir = SITE_ROOT . '/wp-content/uploads/custom';
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

    $basename = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
    $new_filename = $prefix . '-' . $basename . '-' . time() . '.' . $ext;
    $new_filepath = $upload_dir . '/' . $new_filename;
    $new_url = '/wp-content/uploads/custom/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $new_filepath)) {
        return $new_url;
    }
    return null;
}

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Logo upload
        $logo_url = handle_upload('logo_file', 'logo');
        if ($logo_url) db_set_setting('logo_url', $logo_url);

        // Favicon upload
        $favicon_url = handle_upload('favicon_file', 'favicon');
        if ($favicon_url) db_set_setting('favicon_url', $favicon_url);

        // Text fields
        $text_fields = [
            'site_name', 'site_tagline', 'logo_alt',
            'topbar_text', 'topbar_phone', 'topbar_phone_link',
            'topbar_email', 'topbar_location', 'topbar_hours',
            'topbar_bg_color', 'topbar_text_color', 'primary_color',
            'whatsapp_number',
            'social_facebook', 'social_instagram', 'social_twitter',
            'social_linkedin', 'social_youtube',
            'contact_email', 'contact_phone',
        ];
        foreach ($text_fields as $key) {
            if (isset($_POST[$key])) {
                db_set_setting($key, sanitize($_POST[$key]));
            }
        }
        db_set_setting('topbar_enabled', isset($_POST['topbar_enabled']) ? '1' : '0');

        log_activity('Updated appearance settings');
        $message = 'Settings saved successfully! Refresh the website to see changes.';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Load settings
try {
    $settings = db_get_all_settings();
} catch (Exception $e) {
    $settings = [];
    $message = 'Database error: ' . $e->getMessage();
    $message_type = 'error';
}

function s($key, $default = '') {
    global $settings;
    return $settings[$key] ?? $default;
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div style="padding:14px 18px;border-radius:8px;margin-bottom:20px;font-size:14px;<?= $message_type === 'success' ? 'background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;' : 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;' ?>">
        <?= escape($message) ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" style="max-width:1000px;">

    <!-- LOGO & FAVICON -->
    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px;">
        <h2 style="margin:0 0 4px 0;font-size:18px;color:#0f172a;">Logo &amp; Favicon</h2>
        <p style="color:#64748b;margin:0 0 20px 0;font-size:14px;">Upload your site logo and favicon</p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;">
            <div>
                <label style="display:block;margin-bottom:10px;font-weight:500;font-size:14px;color:#334155;">Site Logo</label>
                <div style="background:#f8fafc;border:2px dashed #e2e8f0;border-radius:8px;padding:20px;text-align:center;margin-bottom:10px;min-height:100px;display:flex;align-items:center;justify-content:center;">
                    <?php if (s('logo_url')): ?>
                        <img src="<?= escape(s('logo_url')) ?>" alt="Current logo" id="logo-preview" style="max-width:100%;max-height:80px;">
                    <?php else: ?>
                        <img src="/wp-content/uploads/2025/05/P-1-1024x201.png" alt="Default logo" id="logo-preview" style="max-width:100%;max-height:80px;">
                    <?php endif; ?>
                </div>
                <input type="file" id="logo_file" name="logo_file" accept="image/*" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
                <small style="display:block;margin-top:6px;color:#94a3b8;font-size:12px;">PNG with transparent background recommended</small>
                <div style="margin-top:12px;">
                    <label style="display:block;margin-bottom:6px;font-weight:500;font-size:13px;color:#334155;">Logo Alt Text</label>
                    <input type="text" name="logo_alt" value="<?= escape(s('logo_alt', 'Dr. Ahmed Zaki Logo')) ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
                </div>
            </div>

            <div>
                <label style="display:block;margin-bottom:10px;font-weight:500;font-size:14px;color:#334155;">Favicon</label>
                <div style="background:#f8fafc;border:2px dashed #e2e8f0;border-radius:8px;padding:20px;text-align:center;margin-bottom:10px;min-height:100px;display:flex;align-items:center;justify-content:center;">
                    <?php if (s('favicon_url')): ?>
                        <img src="<?= escape(s('favicon_url')) ?>" alt="Current favicon" id="favicon-preview" style="width:64px;height:64px;">
                    <?php else: ?>
                        <img src="/wp-content/uploads/2025/06/cropped-favicon-180x180.png" alt="Default favicon" id="favicon-preview" style="width:64px;height:64px;">
                    <?php endif; ?>
                </div>
                <input type="file" id="favicon_file" name="favicon_file" accept="image/*" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
                <small style="display:block;margin-top:6px;color:#94a3b8;font-size:12px;">Square PNG, 192x192px or larger</small>
            </div>
        </div>
    </div>

    <!-- SITE IDENTITY -->
    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px;">
        <h2 style="margin:0 0 4px 0;font-size:18px;color:#0f172a;">Site Identity</h2>
        <p style="color:#64748b;margin:0 0 20px 0;font-size:14px;">Site name and tagline</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Site Name</label>
                <input type="text" name="site_name" value="<?= escape(s('site_name', 'Dr. Ahmed Zaki')) ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Tagline</label>
                <input type="text" name="site_tagline" value="<?= escape(s('site_tagline', 'Orthopedic Surgeon')) ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
        </div>
    </div>

    <!-- TOP BAR -->
    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
            <div>
                <h2 style="margin:0 0 4px 0;font-size:18px;color:#0f172a;">Top Bar</h2>
                <p style="color:#64748b;margin:0;font-size:14px;">The bar above the main header</p>
            </div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 14px;background:#f1f5f9;border-radius:8px;">
                <input type="checkbox" name="topbar_enabled" value="1" <?= s('topbar_enabled', '1') === '1' ? 'checked' : '' ?> style="width:18px;height:18px;cursor:pointer;">
                <span style="font-size:14px;font-weight:500;">Show Top Bar</span>
            </label>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Welcome Text</label>
                <input type="text" name="topbar_text" value="<?= escape(s('topbar_text', 'Book Your Appointment Today')) ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Phone (Display)</label>
                <input type="tel" name="topbar_phone" value="<?= escape(s('topbar_phone', '+971 58 567 0984')) ?>" placeholder="+971 58 567 0984" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Phone Link</label>
                <input type="text" name="topbar_phone_link" value="<?= escape(s('topbar_phone_link', 'tel:+971585670984')) ?>" placeholder="tel:+971585670984" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Email</label>
                <input type="email" name="topbar_email" value="<?= escape(s('topbar_email', 'info@drahmedzaki.ae')) ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Location</label>
                <input type="text" name="topbar_location" value="<?= escape(s('topbar_location', 'Dubai, UAE')) ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Working Hours</label>
                <input type="text" name="topbar_hours" value="<?= escape(s('topbar_hours', 'Sun - Thu: 9:00 AM - 6:00 PM')) ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Top Bar Background</label>
                <input type="color" name="topbar_bg_color" value="<?= escape(s('topbar_bg_color', '#0a4d68')) ?>" style="width:100%;height:42px;padding:4px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Top Bar Text Color</label>
                <input type="color" name="topbar_text_color" value="<?= escape(s('topbar_text_color', '#ffffff')) ?>" style="width:100%;height:42px;padding:4px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;">
            </div>
        </div>
    </div>

    <!-- BRAND COLORS -->
    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px;">
        <h2 style="margin:0 0 4px 0;font-size:18px;color:#0f172a;">Brand Colors</h2>
        <p style="color:#64748b;margin:0 0 20px 0;font-size:14px;">Primary color across the site</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Primary Color</label>
                <input type="color" name="primary_color" value="<?= escape(s('primary_color', '#0a4d68')) ?>" style="width:100%;height:42px;padding:4px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;">
            </div>
        </div>
    </div>

    <!-- SOCIAL MEDIA -->
    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px;">
        <h2 style="margin:0 0 4px 0;font-size:18px;color:#0f172a;">Social Media &amp; WhatsApp</h2>
        <p style="color:#64748b;margin:0 0 20px 0;font-size:14px;">Social profiles and WhatsApp link</p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">WhatsApp Number</label>
                <input type="text" name="whatsapp_number" value="<?= escape(s('whatsapp_number', '971585670984')) ?>" placeholder="971585670984 (no + or spaces)" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
                <small style="display:block;margin-top:4px;font-size:12px;color:#94a3b8;">Country code + number, no + or spaces</small>
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Facebook URL</label>
                <input type="url" name="social_facebook" value="<?= escape(s('social_facebook')) ?>" placeholder="https://facebook.com/..." style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Instagram URL</label>
                <input type="url" name="social_instagram" value="<?= escape(s('social_instagram')) ?>" placeholder="https://instagram.com/..." style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Twitter / X URL</label>
                <input type="url" name="social_twitter" value="<?= escape(s('social_twitter')) ?>" placeholder="https://twitter.com/..." style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">LinkedIn URL</label>
                <input type="url" name="social_linkedin" value="<?= escape(s('social_linkedin')) ?>" placeholder="https://linkedin.com/in/..." style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">YouTube URL</label>
                <input type="url" name="social_youtube" value="<?= escape(s('social_youtube')) ?>" placeholder="https://youtube.com/..." style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
        </div>
    </div>

    <!-- CONTACT INFO -->
    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px;">
        <h2 style="margin:0 0 4px 0;font-size:18px;color:#0f172a;">Contact Info</h2>
        <p style="color:#64748b;margin:0 0 20px 0;font-size:14px;">Used in footer and contact pages</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Contact Email</label>
                <input type="email" name="contact_email" value="<?= escape(s('contact_email')) ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#334155;">Contact Phone</label>
                <input type="tel" name="contact_phone" value="<?= escape(s('contact_phone')) ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
        </div>
    </div>

    <div style="position:sticky;bottom:0;background:white;padding:16px 20px;border:1px solid #e2e8f0;border-radius:12px;display:flex;justify-content:flex-end;gap:12px;box-shadow:0 -4px 12px rgba(0,0,0,0.06);">
        <button type="reset" style="padding:10px 24px;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;">Reset</button>
        <button type="submit" style="padding:10px 24px;background:#0a4d68;color:white;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;">Save Changes</button>
    </div>
</form>

<script>
// Live preview for file uploads
document.getElementById('logo_file')?.addEventListener('change', function(e) {
    const f = e.target.files[0];
    if (f) {
        const r = new FileReader();
        r.onload = e => document.getElementById('logo-preview').src = e.target.result;
        r.readAsDataURL(f);
    }
});
document.getElementById('favicon_file')?.addEventListener('change', function(e) {
    const f = e.target.files[0];
    if (f) {
        const r = new FileReader();
        r.onload = e => document.getElementById('favicon-preview').src = e.target.result;
        r.readAsDataURL(f);
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
