<?php
require_once __DIR__ . '/config.php';

/**
 * Authentication & User Management
 * Simple file-based version (no database required)
 */

// Users stored in JSON file
$users_file = __DIR__ . '/../data/users.json';

session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_users_file() {
    global $users_file;
    return $users_file;
}

function load_users() {
    $file = get_users_file();
    if (!file_exists($file)) {
        // Create default admin user
        $default_users = [
            [
                'id' => 1,
                'username' => 'admin',
                'password' => password_hash('Admin@123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, json_encode($default_users, JSON_PRETTY_PRINT));
        return $default_users;
    }
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_users($users) {
    $file = get_users_file();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
}

function get_user($username) {
    $users = load_users();
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }
    return null;
}

function login($username, $password) {
    $user = get_user($username);
    if (!$user || !password_verify($password, $user['password'])) {
        return ['ok' => false, 'error' => 'Invalid username or password'];
    }
    
    $_SESSION['user'] = [
        'id'       => $user['id'],
        'username' => $user['username'],
        'role'     => $user['role'],
        'name'     => $user['name'],
    ];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    
    return ['ok' => true];
}

function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

function require_role($roles) {
    require_login();
    $user = current_user();
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $allowed)) {
        http_response_code(403);
        die('Access denied. You do not have permission for this action.');
    }
}

function can($capability) {
    $user = current_user();
    if (!$user) return false;
    $role = $user['role'];

    $caps = [
        'manage_users'   => ['admin'],
        'edit_pages'     => ['admin', 'editor'],
        'create_pages'   => ['admin', 'editor'],
        'delete_pages'   => ['admin'],
        'edit_all_posts' => ['admin', 'editor'],
        'edit_own_posts' => ['admin', 'editor', 'author', 'contributor'],
        'publish_posts'  => ['admin', 'editor', 'author'],
        'upload_media'   => ['admin', 'editor', 'author'],
        'manage_menu'    => ['admin', 'editor'],
        'manage_seo'     => ['admin', 'editor'],
        'view_analytics' => ['admin', 'editor'],
        'manage_settings'=> ['admin'],
    ];
    return isset($caps[$capability]) && in_array($role, $caps[$capability]);
}

function csrf_token() {
    return $_SESSION['csrf'] ?? '';
}

function verify_csrf() {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['ok' => false, 'error' => 'Invalid CSRF token']));
    }
}

// Activity logging (file-based)
function log_activity($action, $details = []) {
    $log_file = __DIR__ . '/../data/activity.json';
    $log = [];
    if (file_exists($log_file)) {
        $log = json_decode(file_get_contents($log_file), true) ?: [];
    }
    
    $user = $_SESSION['user']['username'] ?? 'system';
    array_unshift($log, [
        'time'    => time(),
        'user'    => $user,
        'action'  => $action,
        'details' => $details,
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    
    // Keep only last 500 entries
    if (count($log) > 500) {
        $log = array_slice($log, 0, 500);
    }
    
    $dir = dirname($log_file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($log_file, json_encode($log, JSON_PRETTY_PRINT));
}
