<?php

namespace App\Modules\User\Http\Controllers\Talk;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Actions\User\TalkPollVoteForUserAction;
use App\Domains\Talk\Models\Talk;
use App\Modules\User\Http\Requests\Talk\TalkPollVoteForUserRequest;

final class TalkPollVoteForUserController extends Controller
{
    public function voteTalkPollForUser(
        Talk $talk,
        TalkPollVoteForUserRequest $request,
        TalkPollVoteForUserAction $action,
    ) {
        $result = $action->execute($this->user(), $talk, $request->validated());

        return ApiResponse::success($result['poll'] ?? $result);
    }

    private function user(): AccountUser
    {
        $user = auth()->user();

        if (! $user instanceof AccountUser) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        if (! $user->isActive()) {
            throw new CustomException(ErrorCode::FORBIDDEN, 'Only active users can vote talks.');
        }

        return $user;
    }
}
