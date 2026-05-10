<?php
/**
 * API Endpoint to get site configuration
 * Returns JSON for the frontend site-loader.js
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes

$config_file = dirname(__DIR__) . '/data/site-config.json';

if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    echo json_encode($config);
} else {
    // Return defaults
    echo json_encode([
        'logo' => [
            'src' => 'wp-content/uploads/2025/05/P-1-1024x201.png',
            'alt' => 'Dr. Ahmed Zaki Logo'
        ],
        'topBar' => [
            'enabled' => true,
            'phone' => '+971 58 567 0984',
            'email' => 'info@drahmedzaki.ae'
        ],
        'social' => [
            'whatsapp' => '971585670984'
        ]
    ]);
}
