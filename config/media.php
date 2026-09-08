<?php

declare(strict_types=1);

return [
    'disk' => env('MEDIA_DISK', 'public'),
    'private_disk' => env('MEDIA_PRIVATE_DISK', 'private_media'),
    'private_collections' => [
        \App\Domains\HospitalWallet\Models\HospitalWalletRefund::class => ['hospital_wallet_refund_business_registration_file', 'hospital_wallet_refund_bankbook_file'],
        \App\Domains\Hospital\Models\HospitalBusinessRegistration::class => ['business_registration_file'],
        \App\Domains\Beauty\Models\BeautyBusinessRegistration::class => ['business_registration_file'],
        \App\Domains\HospitalDoctor\Models\HospitalDoctor::class => ['license_image', 'specialist_certificate_image'],
        \App\Domains\BeautyExpert\Models\BeautyExpert::class => ['education_certificate_image', 'etc_certificate_image'],
        \App\Domains\HospitalEntry\Models\HospitalEntry::class => ['hospital_entry_business_registration_file', 'hospital_entry_license_file'],
        \App\Domains\HospitalEvent\Models\HospitalEventRealModelDB::class => ['real_model_db_images'],
        \App\Domains\HospitalEvaluation\Models\HospitalEvaluation::class => ['receipt_images'],
        \App\Domains\Chat\Models\ChatMessage::class => ['attachments'],
    ],
    'cache_control' => env('MEDIA_CACHE_CONTROL', 'public, max-age=31536000, immutable'),
];
