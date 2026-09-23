<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationClaimForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationEndForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationListForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationShowForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotConsultationListForStaffRequest;

final class HospitalChatBotConsultationForStaffController extends Controller
{
    public function index(
        HospitalChatBotConsultationListForStaffRequest $request,
        HospitalChatBotConsultationListForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function show(int $consultation, HospitalChatBotConsultationShowForStaffAction $action)
    {
        return ApiResponse::success($action->execute($consultation));
    }

    public function claim(int $consultation, HospitalChatBotConsultationClaimForStaffAction $action)
    {
        return ApiResponse::success($action->execute($consultation));
    }

    public function end(int $consultation, HospitalChatBotConsultationEndForStaffAction $action)
    {
        return ApiResponse::success($action->execute($consultation));
    }
}
