<?php

return [
    'url' => env(
        'HOSPITAL_ACCOUNT_INVITATION_URL',
        rtrim((string) env('APP_URL', 'http://localhost'), '/').'/account/create'
    ),
    'expire_hours' => (int) env('HOSPITAL_ACCOUNT_INVITATION_EXPIRE_HOURS', 72),

    'email_verification' => [
        'code_ttl_minutes' => (int) env('HOSPITAL_ACCOUNT_EMAIL_CODE_TTL_MINUTES', 5),
        'verification_ttl_minutes' => (int) env('HOSPITAL_ACCOUNT_EMAIL_VERIFICATION_TTL_MINUTES', 15),
        'resend_seconds' => (int) env('HOSPITAL_ACCOUNT_EMAIL_RESEND_SECONDS', 60),
        'max_attempts' => (int) env('HOSPITAL_ACCOUNT_EMAIL_MAX_ATTEMPTS', 5),
    ],
    'mail' => [
        'connection' => env('HOSPITAL_ACCOUNT_INVITATION_MAIL_QUEUE_CONNECTION', 'redis'),
        'queue' => env('HOSPITAL_ACCOUNT_INVITATION_MAIL_QUEUE', 'mail'),
    ],
];
