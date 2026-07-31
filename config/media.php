<?php

declare(strict_types=1);

return [
    'disk' => env('MEDIA_DISK', 'public'),
    'cache_control' => env('MEDIA_CACHE_CONTROL', 'public, max-age=31536000, immutable'),
];
