<?php

namespace App\Common\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

final class ValidateWebCsrfToken extends ValidateCsrfToken
{
    // CSRF tokens are returned as JSON; no cross-actor XSRF cookie is needed.
    protected $addHttpCookie = false;
}
