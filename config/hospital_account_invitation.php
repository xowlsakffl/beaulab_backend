<?php

return [
    'url' => env(
        'HOSPITAL_ACCOUNT_INVITATION_URL',
        rtrim((string) env('APP_URL', 'http://localhost'), '/').'/account/create'
    ),
    'expire_hours' => (int) env('HOSPITAL_ACCOUNT_INVITATION_EXPIRE_HOURS', 72),

    'identity_verification_ttl_minutes' => (int) env('HOSPITAL_ACCOUNT_IDENTITY_VERIFICATION_TTL_MINUTES', 15),
    'mail' => [
        'connection' => env('HOSPITAL_ACCOUNT_INVITATION_MAIL_QUEUE_CONNECTION', 'redis'),
        'queue' => env('HOSPITAL_ACCOUNT_INVITATION_MAIL_QUEUE', 'mail'),
    ],
];
