<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Push Notification Runtime
    |--------------------------------------------------------------------------
    |
    | 실제 외부 푸시 전송은 credentials가 있어야만 성공한다. 기본 provider는
    | FCM이며, iOS 원시 APNs 토큰을 쓰는 경우 PUSH_IOS_PROVIDER=apns로 바꾼다.
    */

    'enabled' => env('PUSH_ENABLED', false),
    'queue' => env('PUSH_QUEUE', 'notifications'),
    'timeout' => (int) env('PUSH_HTTP_TIMEOUT', 10),

    'provider_by_platform' => [
        'IOS' => env('PUSH_IOS_PROVIDER', env('PUSH_PROVIDER', 'fcm')),
        'ANDROID' => env('PUSH_ANDROID_PROVIDER', env('PUSH_PROVIDER', 'fcm')),
        'WEB' => env('PUSH_WEB_PROVIDER', env('PUSH_PROVIDER', 'fcm')),
    ],

    'fcm' => [
        'enabled' => env('FCM_ENABLED', env('PUSH_ENABLED', false)),
        'project_id' => env('FCM_PROJECT_ID'),
        'client_email' => env('FCM_CLIENT_EMAIL'),
        'private_key' => str_replace('\\n', "\n", (string) env('FCM_PRIVATE_KEY', '')),
        'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH'),
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON'),
        'token_uri' => env('FCM_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
        'scope' => env('FCM_SCOPE', 'https://www.googleapis.com/auth/firebase.messaging'),
    ],

    'apns' => [
        'enabled' => env('APNS_ENABLED', false),
        'environment' => env('APNS_ENVIRONMENT', 'production'),
        'team_id' => env('APNS_TEAM_ID'),
        'key_id' => env('APNS_KEY_ID'),
        'bundle_id' => env('APNS_BUNDLE_ID'),
        'private_key' => str_replace('\\n', "\n", (string) env('APNS_PRIVATE_KEY', '')),
        'private_key_path' => env('APNS_PRIVATE_KEY_PATH'),
    ],
];
