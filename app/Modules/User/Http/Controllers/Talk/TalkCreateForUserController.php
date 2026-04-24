<?php

namespace App\Modules\User\Http\Controllers\Talk;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Actions\User\TalkCreateForUserAction;
use App\Modules\User\Http\Requests\Talk\TalkCreateForUserRequest;

final class TalkCreateForUserController extends Controller
{
    public function createTalkForUser(TalkCreateForUserRequest $request, TalkCreateForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($user, $request->validated());

        return ApiResponse::success($result['talk'] ?? $result);
    }
}
