<?php

namespace App\Modules\Staff\Http\Controllers\RemoteConsultation;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\RemoteConsultation\Actions\RemoteConsultationApproveForStaffAction;
use App\Domains\RemoteConsultation\Actions\RemoteConsultationAssignmentForStaffAction;
use App\Domains\RemoteConsultation\Actions\RemoteConsultationReadForStaffAction;
use App\Domains\RemoteConsultation\Actions\RemoteConsultationStatusForStaffAction;
use App\Modules\Staff\Http\Requests\RemoteConsultation\RemoteConsultationAssignForStaffRequest;
use App\Modules\Staff\Http\Requests\RemoteConsultation\RemoteConsultationHospitalSearchRequest;
use App\Modules\Staff\Http\Requests\RemoteConsultation\RemoteConsultationListForStaffRequest;
use App\Modules\Staff\Http\Requests\RemoteConsultation\RemoteConsultationStatusForStaffRequest;

final class RemoteConsultationForStaffController extends Controller
{
    public function index(RemoteConsultationListForStaffRequest $request, RemoteConsultationReadForStaffAction $action)
    {
        $result = $action->index($request->validated());

        return ApiResponse::success($result['items'], $result['meta']);
    }

    public function hospitals(int $consultation, RemoteConsultationHospitalSearchRequest $request, RemoteConsultationAssignmentForStaffAction $action)
    {
        $result = $action->candidates($consultation, $request->validated());

        return ApiResponse::success($result['items'], $result['meta']);
    }

    public function assign(int $consultation, RemoteConsultationAssignForStaffRequest $request, RemoteConsultationAssignmentForStaffAction $action)
    {
        return ApiResponse::success($action->add($consultation, $request->validated('hospital_ids') ?? [(int) $request->validated('hospital_id')]));
    }

    public function removeQuote(int $consultation, int $quote, RemoteConsultationAssignmentForStaffAction $action)
    {
        return ApiResponse::success($action->remove($consultation, $quote));
    }

    public function show(int $consultation, RemoteConsultationReadForStaffAction $action)
    {
        return ApiResponse::success($action->show($consultation));
    }

    public function quote(int $consultation, int $quote, RemoteConsultationReadForStaffAction $action)
    {
        return ApiResponse::success($action->quote($consultation, $quote));
    }

    public function approve(int $consultation, RemoteConsultationApproveForStaffAction $action)
    {
        return ApiResponse::success($action->execute($consultation));
    }

    public function status(int $consultation, RemoteConsultationStatusForStaffRequest $request, RemoteConsultationStatusForStaffAction $action)
    {
        return ApiResponse::success($action->execute($consultation, $request->validated('status')));
    }
}
