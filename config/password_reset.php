<?php

return [
    'mail' => [
        'connection' => env('PASSWORD_RESET_MAIL_QUEUE_CONNECTION', 'redis'),
        'queue' => env('PASSWORD_RESET_MAIL_QUEUE', 'mail'),
    ],

    'urls' => [
        'user' => env('PASSWORD_RESET_USER_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/password/reset'),
        'hospital' => env('PASSWORD_RESET_HOSPITAL_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/password/reset'),
        'beauty' => env('PASSWORD_RESET_BEAUTY_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/password/reset'),
        'staff' => env('PASSWORD_RESET_STAFF_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/password/reset'),
    ],
];
