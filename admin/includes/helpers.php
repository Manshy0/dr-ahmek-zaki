<?php
require_once __DIR__ . '/config.php';

/**
 * Generic helpers: file scanning, backups, JSON store
 */

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function safe_path($path) {
    // Resolve and ensure stays inside SITE_ROOT
    $real = realpath($path);
    if ($real === false) return false;
    if (strpos($real, realpath(SITE_ROOT)) !== 0) return false;
    return $real;
}

function backup_file($filepath) {
    if (!file_exists($filepath)) return false;
    $rel = str_replace(realpath(SITE_ROOT) . DIRECTORY_SEPARATOR, '', realpath($filepath));
    $rel = str_replace(['/', '\\'], '__', $rel);
    $stamp = date('Ymd_His');
    $backup = BACKUP_DIR . '/' . $stamp . '__' . $rel;
    @copy($filepath, $backup);
    // prune
    $pattern = BACKUP_DIR . '/*__' . $rel;
    $files = glob($pattern);
    if ($files && count($files) > MAX_BACKUP_FILES) {
        usort($files, fn($a,$b) => filemtime($a) - filemtime($b));
        $remove = array_slice($files, 0, count($files) - MAX_BACKUP_FILES);
        foreach ($remove as $f) @unlink($f);
    }
    return $backup;
}

function list_html_pages() {
    $pages = [];
    $root = realpath(SITE_ROOT);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $file) {
        if ($file->isDir()) continue;
        $name = $file->getFilename();
        $path = $file->getPathname();
        // Skip admin, wp-admin, wp-includes, .git, node_modules, backups
        $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
        $rel = str_replace('\\', '/', $rel);
        $skip = ['admin/', 'wp-admin/', 'wp-includes/', '.git/', 'node_modules/', 'wp-content/plugins/', 'wp-content/themes/', '_new_assets/'];
        $skipMatch = false;
        foreach ($skip as $s) {
            if (strpos($rel, $s) === 0 || strpos($rel, '/' . $s) !== false) { $skipMatch = true; break; }
        }
        if ($skipMatch) continue;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['htm', 'html'])) continue;

        // Build URL slug
        $url = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
        if (basename($url) === 'index.htm' || basename($url) === 'index.html') {
            $url = rtrim(dirname($url), '/') . '/';
            if ($url === '') $url = '/';
        }

        // Categorize
        $type = 'page';
        if (strpos($rel, 'blogs/') === 0) $type = 'blog';
        elseif (strpos($rel, 'news/') === 0) $type = 'news';
        elseif (strpos($rel, 'treatments/') === 0) $type = 'treatment';
        elseif (strpos($rel, 'patient_journey/') === 0) $type = 'patient_journey';
        elseif (strpos($rel, 'appointments/') === 0) $type = 'appointment';
        elseif (strpos($rel, 'shoulder-treatment-dubai/') === 0) $type = 'treatment';

        $pages[] = [
            'path'     => $rel,
            'url'      => $url,
            'title'    => extract_title($path),
            'type'     => $type,
            'modified' => filemtime($path),
            'size'     => filesize($path),
        ];
    }
    usort($pages, fn($a,$b) => $b['modified'] - $a['modified']);
    return $pages;
}

function extract_title($filepath) {
    $h = @fopen($filepath, 'r');
    if (!$h) return basename(dirname($filepath));
    $content = fread($h, 8192);
    fclose($h);
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $m)) {
        $t = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES, 'UTF-8');
        // Strip site suffix patterns
        $t = preg_replace('/\s*[\|\-–]\s*Dr\.?\s*Ahmed\s*Zaki.*$/i', '', $t);
        return $t ?: basename(dirname($filepath));
    }
    return basename(dirname($filepath));
}

function format_size($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / (1024 * 1024), 1) . ' MB';
}

function format_date($ts) {
    if (!$ts) return '—';
    return date('Y-m-d H:i', $ts);
}

function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function load_json($filename, $default = []) {
    $path = DATA_DIR . '/' . $filename;
    if (!file_exists($path)) return $default;
    return json_decode(file_get_contents($path), true) ?: $default;
}

function save_json($filename, $data) {
    file_put_contents(DATA_DIR . '/' . $filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// log_activity is now in auth.php and uses database

function role_label($role) {
    return ROLES[$role] ?? $role;
}

function relative_time($ts) {
    if (!$ts) return '—';
    $diff = time() - $ts;
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    if ($diff < 86400 * 30) return floor($diff/86400) . 'd ago';
    return date('Y-m-d', $ts);
}
