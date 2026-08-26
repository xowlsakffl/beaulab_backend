<?php

return [
    'url' => env(
        'HOSPITAL_ACCOUNT_INVITATION_URL',
        rtrim((string) env('APP_URL', 'http://localhost'), '/').'/account/create'
    ),
    'expire_hours' => (int) env('HOSPITAL_ACCOUNT_INVITATION_EXPIRE_HOURS', 72),

    'phone_verification' => [
        'code_ttl_minutes' => (int) env('HOSPITAL_ACCOUNT_PHONE_CODE_TTL_MINUTES', 5),
        'verification_ttl_minutes' => (int) env('HOSPITAL_ACCOUNT_PHONE_VERIFICATION_TTL_MINUTES', 15),
        'resend_seconds' => (int) env('HOSPITAL_ACCOUNT_PHONE_RESEND_SECONDS', 60),
        'max_attempts' => (int) env('HOSPITAL_ACCOUNT_PHONE_MAX_ATTEMPTS', 5),
    ],
    'mail' => [
        'connection' => env('HOSPITAL_ACCOUNT_INVITATION_MAIL_QUEUE_CONNECTION', 'redis'),
        'queue' => env('HOSPITAL_ACCOUNT_INVITATION_MAIL_QUEUE', 'mail'),
    ],
];
