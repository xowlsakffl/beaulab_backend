<?php

namespace App\Modules\Admin\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function traceId(?Request $request = null): ?string
    {
        $request ??= request();

        // App\Shared\Middleware\RequestId 가 attributes('traceId')에 세팅함
        return $request->attributes->get('traceId')
            ?? $request->header('X-Request-Id');
    }
}
