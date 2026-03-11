<?php

namespace App\Modules\Staff\Http\Controllers\HospitalTalkComment;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkCommentCreateForStaffAction;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkCommentDeleteForStaffAction;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkCommentGetForStaffAction;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkCommentListForStaffAction;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkCommentUpdateForStaffAction;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use App\Modules\Staff\Http\Requests\HospitalTalkComment\HospitalTalkCommentCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalTalkComment\HospitalTalkCommentGetForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalTalkComment\HospitalTalkCommentListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalTalkComment\HospitalTalkCommentUpdateForStaffRequest;

final class HospitalTalkCommentForStaffController extends Controller
{
    public function getCommentsForStaff(HospitalTalkCommentListForStaffRequest $request, HospitalTalkCommentListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getCommentForStaff(HospitalTalkComment $comment, HospitalTalkCommentGetForStaffRequest $request, HospitalTalkCommentGetForStaffAction $action)
    {
        $result = $action->execute($comment, $request->filters());

        return ApiResponse::success($result['comment'] ?? $result);
    }

    public function storeCommentForStaff(HospitalTalkCommentCreateForStaffRequest $request, HospitalTalkCommentCreateForStaffAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['comment'] ?? $result);
    }

    public function updateCommentForStaff(HospitalTalkComment $comment, HospitalTalkCommentUpdateForStaffRequest $request, HospitalTalkCommentUpdateForStaffAction $action)
    {
        $result = $action->execute($comment, $request->validated());

        return ApiResponse::success($result['comment'] ?? $result);
    }

    public function deleteCommentForStaff(HospitalTalkComment $comment, HospitalTalkCommentDeleteForStaffAction $action)
    {
        $result = $action->execute($comment);

        return ApiResponse::success($result);
    }
}
