<?php

namespace App\Modules\Staff\Http\Controllers\HospitalEvaluation;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEvaluation\Actions\Staff\HospitalEvaluationGetForStaffAction;
use App\Domains\HospitalEvaluation\Actions\Staff\HospitalEvaluationListForStaffAction;
use App\Domains\HospitalEvaluation\Actions\Staff\HospitalEvaluationOperationHistoriesForStaffAction;
use App\Domains\HospitalEvaluation\Actions\Staff\HospitalEvaluationReceiptRejectForStaffAction;
use App\Domains\HospitalEvaluation\Actions\Staff\HospitalEvaluationReceiptVerifyForStaffAction;
use App\Domains\HospitalEvaluation\Actions\Staff\HospitalEvaluationStatusUpdateForStaffAction;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Modules\Staff\Http\Requests\HospitalEvaluation\HospitalEvaluationGetForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvaluation\HospitalEvaluationListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvaluation\HospitalEvaluationReceiptRejectForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvaluation\HospitalEvaluationStatusUpdateForStaffRequest;

final class HospitalEvaluationForStaffController extends Controller
{
    public function getHospitalEvaluationsForStaff(
        HospitalEvaluationListForStaffRequest $request,
        HospitalEvaluationListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getHospitalEvaluationForStaff(
        HospitalEvaluation $hospitalEvaluation,
        HospitalEvaluationGetForStaffRequest $request,
        HospitalEvaluationGetForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEvaluation, $request->filters());

        return ApiResponse::success($result['evaluation']);
    }

    public function getHospitalEvaluationOperationHistoriesForStaff(
        HospitalEvaluation $hospitalEvaluation,
        HospitalEvaluationGetForStaffRequest $request,
        HospitalEvaluationOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEvaluation, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function updateHospitalEvaluationStatusForStaff(
        HospitalEvaluationStatusUpdateForStaffRequest $request,
        HospitalEvaluationStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function verifyHospitalEvaluationReceiptForStaff(
        HospitalEvaluation $hospitalEvaluation,
        HospitalEvaluationReceiptVerifyForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($hospitalEvaluation));
    }

    public function rejectHospitalEvaluationReceiptForStaff(
        HospitalEvaluation $hospitalEvaluation,
        HospitalEvaluationReceiptRejectForStaffRequest $request,
        HospitalEvaluationReceiptRejectForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($hospitalEvaluation, $request->validated()));
    }
}
