<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotConsultationActiveForHospitalAction;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotConsultationCancelForHospitalAction;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotConsultationCreateForHospitalAction;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotConsultationEndForHospitalAction;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotConsultationMessageForHospitalAction;
use App\Modules\Hospital\Http\Requests\HospitalChatBot\HospitalChatBotConsultationCreateForHospitalRequest;
use App\Modules\Hospital\Http\Requests\HospitalChatBot\HospitalChatBotConsultationMessageListForHospitalRequest;
use App\Modules\Hospital\Http\Requests\HospitalChatBot\HospitalChatBotConsultationMessageSendForHospitalRequest;

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

    public function end(int $consultation, HospitalChatBotConsultationEndForHospitalAction $action)
    {
        return ApiResponse::success($action->execute($consultation));
    }

    public function messages(
        int $consultation,
        HospitalChatBotConsultationMessageListForHospitalRequest $request,
        HospitalChatBotConsultationMessageForHospitalAction $action,
    ) {
        $result = $action->index($consultation, $request->afterSequence(), $request->limit());

        return ApiResponse::success($result['items'], [
            'last_sequence' => $result['last_sequence'],
            'has_older' => $result['has_older'],
        ]);
    }

    public function sendMessage(
        int $consultation,
        HospitalChatBotConsultationMessageSendForHospitalRequest $request,
        HospitalChatBotConsultationMessageForHospitalAction $action,
    ) {
        return ApiResponse::success(
            $action->store($consultation, $request->content(), $request->clientMessageId()),
        );
    }
}
