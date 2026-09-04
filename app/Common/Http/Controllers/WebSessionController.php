<?php

namespace App\Common\Http\Controllers;

use App\Common\Auth\ActorAuthentication;
use App\Common\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

final class WebSessionController
{
    public function csrf(Request $request)
    {
        return ApiResponse::success(['csrf_token' => $request->session()->token()]);
    }

    public function status(ActorAuthentication $authentication)
    {
        return ApiResponse::success(['session' => $authentication->metadata()]);
    }
}
