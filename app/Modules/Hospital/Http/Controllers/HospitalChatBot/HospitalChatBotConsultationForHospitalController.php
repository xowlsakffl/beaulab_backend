<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotConsultationActiveForHospitalAction;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotConsultationCancelForHospitalAction;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotConsultationCreateForHospitalAction;
use App\Modules\Hospital\Http\Requests\HospitalChatBot\HospitalChatBotConsultationCreateForHospitalRequest;

final class HospitalChatBotConsultationForHospitalController extends Controller
{
    public function active(HospitalChatBotConsultationActiveForHospitalAction $action)
    {
        return ApiResponse::success($action->execute());
    }

    public function store(
        HospitalChatBotConsultationCreateForHospitalRequest $request,
        HospitalChatBotConsultationCreateForHospitalAction $action,
    ) {
        return ApiResponse::success($action->execute($request->context()));
    }

    public function cancel(int $consultation, HospitalChatBotConsultationCancelForHospitalAction $action)
    {
        return ApiResponse::success($action->execute($consultation));
    }
}
