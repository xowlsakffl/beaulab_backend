<?php

namespace App\Modules\User\Http\Controllers\Talk;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
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
        $result = $action->execute($request->user(), $talk, $request->validated());

        return ApiResponse::success($result['poll'] ?? $result);
    }
}
