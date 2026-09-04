<?php

$webAuth = require __DIR__.'/web_auth.php';

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique(array_merge(...array_column($webAuth['actors'], 'origins')))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Content-Disposition', 'X-Session-Expires-At', 'X-Session-Idle-Expires-At'],
    'max_age' => max(0, (int) env('CORS_MAX_AGE', 3600)),
    'supports_credentials' => true,
];
