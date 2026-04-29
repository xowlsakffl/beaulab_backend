<?php

namespace App\Modules\User\Http\Controllers\Talk;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Talk\Actions\User\TalkCommentCreateForUserAction;
use App\Domains\Talk\Models\Talk;
use App\Modules\User\Http\Requests\Talk\TalkCommentCreateForUserRequest;

final class TalkCommentCreateForUserController extends Controller
{
    public function createTalkCommentForUser(
        Talk $talk,
        TalkCommentCreateForUserRequest $request,
        TalkCommentCreateForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $talk, [
            ...$request->validated(),
            'author_ip' => $request->ip(),
        ]);

        return ApiResponse::success($result['comment'] ?? $result);
    }
}
