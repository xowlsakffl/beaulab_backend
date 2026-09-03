<?php

return [
    'hospital' => [
        'url' => env('PASSWORD_RESET_HOSPITAL_URL', 'http://localhost:3002/password/reset'),
        'expire_minutes' => (int) env('PASSWORD_RESET_HOSPITAL_EXPIRE_MINUTES', 60),
        'resend_seconds' => (int) env('PASSWORD_RESET_HOSPITAL_RESEND_SECONDS', 60),
    ],

    'mail' => [
        'connection' => env('PASSWORD_RESET_MAIL_QUEUE_CONNECTION', 'redis'),
        'queue' => env('PASSWORD_RESET_MAIL_QUEUE', 'mail'),
    ],

    'urls' => [
        'user' => env('PASSWORD_RESET_USER_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/password/reset'),
        'beauty' => env('PASSWORD_RESET_BEAUTY_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/password/reset'),
        'staff' => env('PASSWORD_RESET_STAFF_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/password/reset'),
    ],
];
