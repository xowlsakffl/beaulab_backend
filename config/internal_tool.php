<?php

return [
    'allowed_emails' => env('INTERNAL_TOOL_ALLOWED_EMAILS', ''),
    'allowed_ips' => env('INTERNAL_TOOL_ALLOWED_IPS', '127.0.0.1,::1'),
];
