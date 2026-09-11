<?php

return [
    'disabled_purposes' => ['hospital_account_phone_verification', 'hospital_account_password_reset'],
    'enabled' => env('SMS_ENABLED', false),
    'provider' => env('SMS_PROVIDER', 'log'),
    'queue' => env('SMS_QUEUE', 'sms'),
    'sms_max_bytes' => (int) env('SMS_MAX_BYTES', 90),
    'lms_max_bytes' => (int) env('LMS_MAX_BYTES', 2000),
];
