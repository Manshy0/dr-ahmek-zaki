<?php
/**
 * Public API: Returns site configuration as JSON
 * Used by site-loader.js on the frontend
 * No authentication required (public read-only)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');
header('Access-Control-Allow-Origin: *');

try {
    $settings = db_get_all_settings();
    $replacements = db_get_all_image_replacements();

    echo json_encode([
        'success' => true,
        'settings' => $settings,
        'image_replacements' => $replacements,
        'generated_at' => time(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'settings' => [],
        'image_replacements' => [],
    ]);
}
