<?php

namespace App\Modules\User\Http\Controllers\Talk;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Actions\User\TalkCreateForUserAction;
use App\Modules\User\Http\Requests\Talk\TalkCreateForUserRequest;

final class TalkCreateForUserController extends Controller
{
    public function createTalkForUser(TalkCreateForUserRequest $request, TalkCreateForUserAction $action)
    {
        $result = $action->execute($this->user(), $request->validated());

        return ApiResponse::success($result['talk'] ?? $result);
    }

    private function user(): AccountUser
    {
        $user = auth()->user();

        if (! $user instanceof AccountUser) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        if (! $user->isActive()) {
            throw new CustomException(ErrorCode::FORBIDDEN, 'Only active users can create talks.');
        }

        return $user;
    }
}
