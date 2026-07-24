<?php

namespace App\Modules\User\Http\Controllers\Talk;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Talk\Actions\User\TalkCommentCreateForUserAction;
use App\Domains\Talk\Actions\User\TalkCommentDeleteForUserAction;
use App\Domains\Talk\Actions\User\TalkCreateForUserAction;
use App\Domains\Talk\Actions\User\TalkDeleteForUserAction;
use App\Domains\Talk\Actions\User\TalkPollVoteForUserAction;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Modules\User\Http\Requests\Talk\TalkCommentCreateForUserRequest;
use App\Modules\User\Http\Requests\Talk\TalkCreateForUserRequest;
use App\Modules\User\Http\Requests\Talk\TalkPollVoteForUserRequest;
use Illuminate\Http\Request;

final class TalkForUserController extends Controller
{
    public function createTalkForUser(TalkCreateForUserRequest $request, TalkCreateForUserAction $action)
    {
        $result = $action->execute($request->user(), [
            ...$request->validated(),
            'author_ip' => $request->ip(),
        ]);

        return ApiResponse::success($result['talk']);
    }

    public function deleteTalkForUser(Request $request, Talk $talk, TalkDeleteForUserAction $action)
    {
        $result = $action->execute($request->user(), $talk);

        return ApiResponse::success($result['talk']);
    }

    public function createTalkCommentForUser(
        Talk $talk,
        TalkCommentCreateForUserRequest $request,
        TalkCommentCreateForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $talk, [
            ...$request->validated(),
            'author_ip' => $request->ip(),
        ]);

        return ApiResponse::success($result['comment']);
    }

    public function deleteTalkCommentForUser(
        Request $request,
        Talk $talk,
        TalkComment $comment,
        TalkCommentDeleteForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $talk, $comment);

        return ApiResponse::success($result['comment']);
    }

    public function voteTalkPollForUser(
        Talk $talk,
        TalkPollVoteForUserRequest $request,
        TalkPollVoteForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $talk, $request->validated());

        return ApiResponse::success($result['poll']);
    }
}
