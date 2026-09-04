<?php

return [
    // Only the validated actor's web guard is enabled by StartActorWebSession.
    'guard' => [],
    'expiration' => null,
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
];
