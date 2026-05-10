<?php
/**
 * MySQL Database Connection
 * Dr. Ahmed Zaki Admin Panel
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u535202675_drahmed');
define('DB_USER', 'u535202675_abdelrazekzaki');
define('DB_PASS', '&BGpyYOfYk2');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            // Show detailed error in development
            die("Database connection failed: " . $e->getMessage() . "<br><br>Please make sure:<br>1. Database name is correct (currently: " . DB_NAME . ")<br>2. Username is correct (currently: " . DB_USER . ")<br>3. Run setup.sql in phpMyAdmin first");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

function db() {
    return Database::getInstance()->getConnection();
}

// ==========================================
// USER FUNCTIONS
// ==========================================

function db_get_user($username) {
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function db_get_user_by_id($id) {
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function db_get_all_users() {
    $stmt = db()->query("SELECT id, username, name, email, role, created_at, last_login FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function db_create_user($username, $password, $name, $email, $role) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = db()->prepare("INSERT INTO users (username, password, name, email, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    return $stmt->execute([$username, $hash, $name, $email, $role]);
}

function db_update_user($id, $data) {
    $fields = [];
    $values = [];
    foreach ($data as $key => $value) {
        if (in_array($key, ['name', 'email', 'role', 'password'])) {
            if ($key === 'password') {
                $value = password_hash($value, PASSWORD_BCRYPT);
            }
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    if (empty($fields)) return false;
    $values[] = $id;
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = db()->prepare($sql);
    return $stmt->execute($values);
}

function db_delete_user($id) {
    $stmt = db()->prepare("DELETE FROM users WHERE id = ? AND username != 'admin'");
    return $stmt->execute([$id]);
}

function db_update_last_login($username) {
    $stmt = db()->prepare("UPDATE users SET last_login = NOW() WHERE username = ?");
    return $stmt->execute([$username]);
}

// ==========================================
// LOGIN ATTEMPTS
// ==========================================

function db_get_login_attempts($username) {
    $stmt = db()->prepare("SELECT attempt_count, last_attempt FROM login_attempts WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    return $stmt->fetch() ?: ['attempt_count' => 0, 'last_attempt' => null];
}

function db_record_login_attempt($username, $success) {
    if ($success) {
        $stmt = db()->prepare("DELETE FROM login_attempts WHERE username = ?");
        $stmt->execute([$username]);
    } else {
        $stmt = db()->prepare("
            INSERT INTO login_attempts (username, attempt_count, last_attempt) 
            VALUES (?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1, last_attempt = NOW()
        ");
        $stmt->execute([$username]);
    }
}

// ==========================================
// ACTIVITY LOG
// ==========================================

function db_log_activity($user, $action, $details = []) {
    $stmt = db()->prepare("INSERT INTO activity_log (user, action, details, ip, created_at) VALUES (?, ?, ?, ?, NOW())");
    return $stmt->execute([$user, $action, json_encode($details), $_SERVER['REMOTE_ADDR'] ?? '']);
}

function db_get_recent_activity($limit = 50) {
    $stmt = db()->prepare("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// ==========================================
// POSTS MANAGEMENT
// ==========================================

function db_get_posts($type = null, $limit = 100) {
    if ($type) {
        $stmt = db()->prepare("SELECT * FROM posts WHERE type = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$type, $limit]);
    } else {
        $stmt = db()->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
    }
    return $stmt->fetchAll();
}

function db_get_post($id) {
    $stmt = db()->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function db_get_post_by_slug($slug, $type) {
    $stmt = db()->prepare("SELECT * FROM posts WHERE slug = ? AND type = ? LIMIT 1");
    $stmt->execute([$slug, $type]);
    return $stmt->fetch();
}

function db_create_post($data) {
    $stmt = db()->prepare("
        INSERT INTO posts (type, title, slug, excerpt, content, featured_image, author, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $data['type'],
        $data['title'],
        $data['slug'],
        $data['excerpt'] ?? '',
        $data['content'] ?? '',
        $data['featured_image'] ?? '',
        $data['author'] ?? 'admin',
        $data['status'] ?? 'published'
    ]);
    return db()->lastInsertId();
}

function db_update_post($id, $data) {
    $fields = [];
    $values = [];
    $allowed = ['title', 'slug', 'excerpt', 'content', 'featured_image', 'status'];
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    if (empty($fields)) return false;
    $fields[] = "updated_at = NOW()";
    $values[] = $id;
    $sql = "UPDATE posts SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = db()->prepare($sql);
    return $stmt->execute($values);
}

function db_delete_post($id) {
    $stmt = db()->prepare("DELETE FROM posts WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==========================================
// MEDIA MANAGEMENT
// ==========================================

function db_add_media($filename, $path, $type, $size) {
    $stmt = db()->prepare("INSERT INTO media (filename, path, type, size, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$filename, $path, $type, $size]);
    return db()->lastInsertId();
}

function db_get_media($limit = 100) {
    $stmt = db()->prepare("SELECT * FROM media ORDER BY uploaded_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function db_delete_media($id) {
    $stmt = db()->prepare("SELECT path FROM media WHERE id = ?");
    $stmt->execute([$id]);
    $media = $stmt->fetch();
    if ($media && file_exists(SITE_ROOT . $media['path'])) {
        @unlink(SITE_ROOT . $media['path']);
    }
    $stmt = db()->prepare("DELETE FROM media WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==========================================
// SEO SETTINGS
// ==========================================

function db_get_seo($page_path) {
    $stmt = db()->prepare("SELECT * FROM seo_settings WHERE page_path = ? LIMIT 1");
    $stmt->execute([$page_path]);
    return $stmt->fetch();
}

function db_save_seo($page_path, $data) {
    $stmt = db()->prepare("
        INSERT INTO seo_settings (page_path, meta_title, meta_description, og_image, updated_at) 
        VALUES (?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
            meta_title = VALUES(meta_title), 
            meta_description = VALUES(meta_description), 
            og_image = VALUES(og_image),
            updated_at = NOW()
    ");
    return $stmt->execute([
        $page_path,
        $data['meta_title'] ?? '',
        $data['meta_description'] ?? '',
        $data['og_image'] ?? ''
    ]);
}

// ==========================================
// MENU MANAGEMENT
// ==========================================

function db_get_menus() {
    $stmt = db()->query("SELECT * FROM menus ORDER BY position ASC");
    return $stmt->fetchAll();
}

function db_save_menu($id, $title, $url, $position, $parent_id = null) {
    if ($id) {
        $stmt = db()->prepare("UPDATE menus SET title = ?, url = ?, position = ?, parent_id = ? WHERE id = ?");
        return $stmt->execute([$title, $url, $position, $parent_id, $id]);
    } else {
        $stmt = db()->prepare("INSERT INTO menus (title, url, position, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $url, $position, $parent_id]);
        return db()->lastInsertId();
    }
}

function db_delete_menu($id) {
    $stmt = db()->prepare("DELETE FROM menus WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==========================================
// SITE SETTINGS
// ==========================================

function db_get_setting($key, $default = null) {
    $stmt = db()->prepare("SELECT value FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : $default;
}

function db_set_setting($key, $value) {
    $stmt = db()->prepare("
        INSERT INTO settings (setting_key, value) VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE value = VALUES(value)
    ");
    return $stmt->execute([$key, $value]);
}

function db_get_all_settings() {
    $stmt = db()->query("SELECT setting_key, value FROM settings");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['value'];
    }
    return $settings;
}

// ==========================================
// IMAGE REPLACEMENTS
// ==========================================

function db_get_image_replacement($original_path) {
    $stmt = db()->prepare("SELECT new_path FROM image_replacements WHERE original_path = ? LIMIT 1");
    $stmt->execute([$original_path]);
    $result = $stmt->fetch();
    return $result ? $result['new_path'] : null;
}

function db_get_all_image_replacements() {
    $stmt = db()->query("SELECT * FROM image_replacements ORDER BY updated_at DESC");
    $replacements = [];
    foreach ($stmt->fetchAll() as $row) {
        $replacements[$row['original_path']] = $row['new_path'];
    }
    return $replacements;
}

function db_save_image_replacement($original_path, $new_path, $alt_text = '') {
    $user = $_SESSION['user']['username'] ?? 'admin';
    $stmt = db()->prepare("
        INSERT INTO image_replacements (original_path, new_path, alt_text, updated_by) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            new_path = VALUES(new_path), 
            alt_text = VALUES(alt_text),
            updated_by = VALUES(updated_by)
    ");
    return $stmt->execute([$original_path, $new_path, $alt_text, $user]);
}

function db_delete_image_replacement($original_path) {
    $stmt = db()->prepare("DELETE FROM image_replacements WHERE original_path = ?");
    return $stmt->execute([$original_path]);
}
