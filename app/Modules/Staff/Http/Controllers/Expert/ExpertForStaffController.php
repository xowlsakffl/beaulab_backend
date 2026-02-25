<?php

namespace App\Modules\Staff\Http\Controllers\Expert;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Expert\Actions\Staff\ExpertCreateForStaffAction;
use App\Domains\Expert\Actions\Staff\ExpertDeleteForStaffAction;
use App\Domains\Expert\Actions\Staff\ExpertGetForStaffAction;
use App\Domains\Expert\Actions\Staff\ExpertListForStaffAction;
use App\Domains\Expert\Actions\Staff\ExpertUpdateForStaffAction;
use App\Domains\Expert\Models\Expert;
use App\Modules\Staff\Http\Requests\Expert\ExpertCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Expert\ExpertListForStaffRequest;
use App\Modules\Staff\Http\Requests\Expert\ExpertUpdateForStaffRequest;

final class ExpertForStaffController extends Controller
{
    public function getExpertsForStaff(ExpertListForStaffRequest $request, ExpertListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getExpertForStaff(Expert $expert, ExpertGetForStaffAction $action)
    {
        $result = $action->execute($expert);

        return ApiResponse::success($result['expert'] ?? $result);
    }

    public function storeExpertForStaff(ExpertCreateForStaffRequest $request, ExpertCreateForStaffAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['expert'] ?? $result);
    }

    public function updateExpertForStaff(Expert $expert, ExpertUpdateForStaffRequest $request, ExpertUpdateForStaffAction $action)
    {
        $result = $action->execute($expert, $request->validated());

        return ApiResponse::success($result['expert'] ?? $result);
    }

    public function deleteExpertForStaff(Expert $expert, ExpertDeleteForStaffAction $action)
    {
        $result = $action->execute($expert);

        return ApiResponse::success($result);
    }
}
