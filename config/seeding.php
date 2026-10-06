<?php

return [
    'development' => [
        'enabled' => env('DEVELOPMENT_SEED_ENABLED', false),
        'database' => env('DEVELOPMENT_SEED_DATABASE', ''),
    ],
    'frontend' => [
        'enabled' => env('FRONTEND_SEED_ENABLED', false),
        'password' => env('FRONTEND_SEED_PASSWORD', ''),
    ],
    'staff' => [
        'name' => env('SEED_STAFF_NAME', '관리자'),
        'nickname' => env('SEED_STAFF_NICKNAME', 'admin'),
        'email' => env('SEED_STAFF_EMAIL', ''),
        'password' => env('SEED_STAFF_PASSWORD', ''),
    ],
];
