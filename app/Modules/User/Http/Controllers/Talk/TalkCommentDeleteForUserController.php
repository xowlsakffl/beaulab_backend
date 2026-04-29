<?php

namespace App\Modules\User\Http\Controllers\Talk;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Talk\Actions\User\TalkCommentDeleteForUserAction;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Http\Request;

final class TalkCommentDeleteForUserController extends Controller
{
    public function deleteTalkCommentForUser(
        Request $request,
        Talk $talk,
        TalkComment $comment,
        TalkCommentDeleteForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $talk, $comment);

        return ApiResponse::success($result['comment'] ?? $result);
    }
}
