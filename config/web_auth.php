<?php

$local = in_array(env('APP_ENV', 'production'), ['local', 'testing'], true);
$origins = static fn (string $key, int $port): array => array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env($key, $local ? "http://localhost:{$port},http://127.0.0.1:{$port}" : '')),
)));

return [
    'connection' => env('WEB_SESSION_CONNECTION', 'default'),
    'revocation_store' => env('WEB_SESSION_REVOCATION_STORE', 'redis'),
    'actors' => [
        'staff' => [
            'origins' => $origins('STAFF_WEB_ORIGINS', 3000),
            'idle_minutes' => (int) env('STAFF_WEB_IDLE_MINUTES', 120),
            'absolute_minutes' => (int) env('STAFF_WEB_ABSOLUTE_MINUTES', 1440),
            'persistent' => false,
        ],
        'hospital' => [
            'origins' => $origins('HOSPITAL_WEB_ORIGINS', 3002),
            'idle_minutes' => (int) env('HOSPITAL_WEB_IDLE_MINUTES', 120),
            'absolute_minutes' => (int) env('HOSPITAL_WEB_ABSOLUTE_MINUTES', 1440),
            'persistent' => false,
        ],
        'beauty' => [
            'origins' => $origins('BEAUTY_WEB_ORIGINS', 3003),
            'idle_minutes' => (int) env('BEAUTY_WEB_IDLE_MINUTES', 120),
            'absolute_minutes' => (int) env('BEAUTY_WEB_ABSOLUTE_MINUTES', 1440),
            'persistent' => false,
        ],
        'user' => [
            'origins' => $origins('USER_WEB_ORIGINS', 3001),
            'idle_minutes' => (int) env('USER_WEB_IDLE_MINUTES', 10080),
            'absolute_minutes' => (int) env('USER_WEB_ABSOLUTE_MINUTES', 43200),
            'persistent' => true,
        ],
    ],
];
