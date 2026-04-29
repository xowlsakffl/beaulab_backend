<?php

namespace App\Modules\User\Http\Controllers\Talk;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Talk\Actions\User\TalkDeleteForUserAction;
use App\Domains\Talk\Models\Talk;
use Illuminate\Http\Request;

final class TalkDeleteForUserController extends Controller
{
    public function deleteTalkForUser(Request $request, Talk $talk, TalkDeleteForUserAction $action)
    {
        $result = $action->execute($request->user(), $talk);

        return ApiResponse::success($result['talk'] ?? $result);
    }
}
