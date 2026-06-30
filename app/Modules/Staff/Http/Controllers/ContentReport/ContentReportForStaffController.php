<?php

namespace App\Modules\Staff\Http\Controllers\ContentReport;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\ContentReport\Actions\Staff\ContentReportStateStatusUpdateForStaffAction;
use App\Domains\Common\ContentReport\Actions\Staff\ContentReportWarningStatusUpdateForStaffAction;
use App\Domains\Common\ContentReport\Actions\Staff\ReportedContentDetailForStaffAction;
use App\Domains\Common\ContentReport\Actions\Staff\ReportedContentListForStaffAction;
use App\Domains\Common\ContentReport\Actions\Staff\ReportedContentReportsForStaffAction;
use App\Domains\Common\ContentReport\Actions\Staff\ReportedContentSummaryForStaffAction;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Modules\Staff\Http\Requests\ContentReport\ContentReportStateStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\ContentReport\ContentReportWarningStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\ContentReport\ReportedContentListForStaffRequest;
use Illuminate\Http\Request;

final class ContentReportForStaffController extends Controller
{
    public function getReportedTalksForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentListForStaffAction $action,
    ) {
        $result = $action->execute(ContentReportTargetRegistry::ALIAS_TALK, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getReportedTalksSummaryForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentSummaryForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(ContentReportTargetRegistry::ALIAS_TALK, $request->filters()));
    }

    public function getReportedTalkCommentsForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentListForStaffAction $action,
    ) {
        $result = $action->execute(ContentReportTargetRegistry::ALIAS_TALK_COMMENT, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getReportedTalkCommentsSummaryForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentSummaryForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(ContentReportTargetRegistry::ALIAS_TALK_COMMENT, $request->filters()));
    }

    public function getReportedSurgeryHospitalReviewsForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentListForStaffAction $action,
    ) {
        $result = $action->execute(
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW,
            $request->filters(HospitalReview::CATEGORY_DOMAIN_SURGERY),
        );

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getReportedSurgeryHospitalReviewsSummaryForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentSummaryForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW,
            $request->filters(HospitalReview::CATEGORY_DOMAIN_SURGERY),
        ));
    }

    public function getReportedTreatmentHospitalReviewsForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentListForStaffAction $action,
    ) {
        $result = $action->execute(
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW,
            $request->filters(HospitalReview::CATEGORY_DOMAIN_TREATMENT),
        );

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getReportedTreatmentHospitalReviewsSummaryForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentSummaryForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW,
            $request->filters(HospitalReview::CATEGORY_DOMAIN_TREATMENT),
        ));
    }

    public function getReportedSurgeryHospitalReviewCommentsForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentListForStaffAction $action,
    ) {
        $result = $action->execute(
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW_COMMENT,
            $request->filters(HospitalReview::CATEGORY_DOMAIN_SURGERY),
        );

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getReportedSurgeryHospitalReviewCommentsSummaryForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentSummaryForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW_COMMENT,
            $request->filters(HospitalReview::CATEGORY_DOMAIN_SURGERY),
        ));
    }

    public function getReportedTreatmentHospitalReviewCommentsForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentListForStaffAction $action,
    ) {
        $result = $action->execute(
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW_COMMENT,
            $request->filters(HospitalReview::CATEGORY_DOMAIN_TREATMENT),
        );

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getReportedTreatmentHospitalReviewCommentsSummaryForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentSummaryForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW_COMMENT,
            $request->filters(HospitalReview::CATEGORY_DOMAIN_TREATMENT),
        ));
    }

    public function getReportedHospitalEvaluationsForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentListForStaffAction $action,
    ) {
        $result = $action->execute(ContentReportTargetRegistry::ALIAS_HOSPITAL_EVALUATION, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getReportedHospitalEvaluationsSummaryForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentSummaryForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(ContentReportTargetRegistry::ALIAS_HOSPITAL_EVALUATION, $request->filters()));
    }

    public function getReportedChatMessagesForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentListForStaffAction $action,
    ) {
        $result = $action->execute(ContentReportTargetRegistry::ALIAS_CHAT_MESSAGE, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getReportedChatMessagesSummaryForStaff(
        ReportedContentListForStaffRequest $request,
        ReportedContentSummaryForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(ContentReportTargetRegistry::ALIAS_CHAT_MESSAGE, $request->filters()));
    }

    public function getReportedContentDetailForStaff(
        Request $request,
        string $targetType,
        int $targetId,
        ReportedContentDetailForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(
            $targetType,
            $targetId,
            $request->boolean('include_target', true),
        ));
    }

    public function getReportedContentReportsForStaff(
        Request $request,
        string $targetType,
        int $targetId,
        ReportedContentReportsForStaffAction $action,
    ) {
        $result = $action->execute($targetType, $targetId, max(1, $request->integer('reports_page', 1)));

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function updateReportedContentStatusForStaff(
        ContentReportStateStatusUpdateForStaffRequest $request,
        ContentReportStateStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function updateReportedContentWarningStatusForStaff(
        ContentReportWarningStatusUpdateForStaffRequest $request,
        ContentReportWarningStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }
}
