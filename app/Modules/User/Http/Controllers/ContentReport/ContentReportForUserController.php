<?php

namespace App\Modules\User\Http\Controllers\ContentReport;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\ContentReport\Actions\User\ContentReportCreateForUserAction;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Modules\User\Http\Requests\ContentReport\ContentReportCreateForUserRequest;
use App\Modules\User\Http\Requests\ContentReport\HospitalVideoReportForUserRequest;

final class ContentReportForUserController extends Controller
{
    public function reportTalkForUser(
        Talk $talk,
        ContentReportCreateForUserRequest $request,
        ContentReportCreateForUserAction $action,
    ) {
        $action->execute($request->user(), $talk, [
            ...$request->validated(),
            'reporter_ip' => $request->ip(),
        ]);

        return ApiResponse::success([
            'message' => '신고가 완료되었습니다.',
        ]);
    }

    public function reportTalkCommentForUser(
        Talk $talk,
        TalkComment $comment,
        ContentReportCreateForUserRequest $request,
        ContentReportCreateForUserAction $action,
    ) {
        $action->executeForTalkComment($request->user(), $talk, $comment, [
            ...$request->validated(),
            'reporter_ip' => $request->ip(),
        ]);

        return ApiResponse::success([
            'message' => '신고가 완료되었습니다.',
        ]);
    }

    public function reportHospitalReviewForUser(
        HospitalReview $hospitalReview,
        ContentReportCreateForUserRequest $request,
        ContentReportCreateForUserAction $action,
    ) {
        $action->execute($request->user(), $hospitalReview, [
            ...$request->validated(),
            'reporter_ip' => $request->ip(),
        ]);

        return ApiResponse::success([
            'message' => '신고가 완료되었습니다.',
        ]);
    }

    public function reportHospitalReviewCommentForUser(
        HospitalReview $hospitalReview,
        HospitalReviewComment $comment,
        ContentReportCreateForUserRequest $request,
        ContentReportCreateForUserAction $action,
    ) {
        $action->executeForHospitalReviewComment($request->user(), $hospitalReview, $comment, [
            ...$request->validated(),
            'reporter_ip' => $request->ip(),
        ]);

        return ApiResponse::success([
            'message' => '신고가 완료되었습니다.',
        ]);
    }

    public function reportHospitalEvaluationForUser(
        HospitalEvaluation $hospitalEvaluation,
        ContentReportCreateForUserRequest $request,
        ContentReportCreateForUserAction $action,
    ) {
        $action->execute($request->user(), $hospitalEvaluation, [
            ...$request->validated(),
            'reporter_ip' => $request->ip(),
        ]);

        return ApiResponse::success([
            'message' => '신고가 완료되었습니다.',
        ]);
    }

    public function reportHospitalVideoForUser(
        HospitalVideo $video,
        HospitalVideoReportForUserRequest $request,
        ContentReportCreateForUserAction $action,
    ) {
        $action->execute($request->user(), $video, [
            ...$request->validated(),
            'reporter_ip' => $request->ip(),
        ]);

        return ApiResponse::success([
            'message' => '신고가 완료되었습니다.',
        ]);
    }
}
