<?php

namespace App\Modules\Staff\Http\Controllers\BeautyExpert;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Expert\Actions\Staff\BeautyExpertCreateForStaffAction;
use App\Domains\Expert\Actions\Staff\BeautyExpertDeleteForStaffAction;
use App\Domains\Expert\Actions\Staff\BeautyExpertGetForStaffAction;
use App\Domains\Expert\Actions\Staff\BeautyExpertListForStaffAction;
use App\Domains\Expert\Actions\Staff\BeautyExpertUpdateForStaffAction;
use App\Domains\Expert\Models\BeautyExpert;
use App\Modules\Staff\Http\Requests\Expert\ExpertCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Expert\ExpertListForStaffRequest;
use App\Modules\Staff\Http\Requests\Expert\ExpertUpdateForStaffRequest;

final class ExpertForStaffController extends Controller
{
    public function getExpertsForStaff(ExpertListForStaffRequest $request, BeautyExpertListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getExpertForStaff(BeautyExpert $expert, BeautyExpertGetForStaffAction $action)
    {
        $result = $action->execute($expert);

        return ApiResponse::success($result['expert'] ?? $result);
    }

    public function storeExpertForStaff(ExpertCreateForStaffRequest $request, BeautyExpertCreateForStaffAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['expert'] ?? $result);
    }

    public function updateExpertForStaff(BeautyExpert $expert, ExpertUpdateForStaffRequest $request, BeautyExpertUpdateForStaffAction $action)
    {
        $result = $action->execute($expert, $request->validated());

        return ApiResponse::success($result['expert'] ?? $result);
    }

    public function deleteExpertForStaff(BeautyExpert $expert, BeautyExpertDeleteForStaffAction $action)
    {
        $result = $action->execute($expert);

        return ApiResponse::success($result);
    }
}
