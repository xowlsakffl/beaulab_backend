<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationClaimForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationEndForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationExportForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationListForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationMessageForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotConsultationShowForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotConsultationExportForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotConsultationListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotConsultationMessageListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotConsultationMessageSendForStaffRequest;

final class HospitalChatBotConsultationForStaffController extends Controller
{
    public function index(
        HospitalChatBotConsultationListForStaffRequest $request,
        HospitalChatBotConsultationListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta']);
    }

    /** 목록과 같은 필터로 CSV를 내려 준다. ApiResponse가 아니라 파일 스트림이다. */
    public function export(
        HospitalChatBotConsultationExportForStaffRequest $request,
        HospitalChatBotConsultationExportForStaffAction $action,
    ) {
        return $action->execute($request->filters());
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

    public function messages(
        int $consultation,
        HospitalChatBotConsultationMessageListForStaffRequest $request,
        HospitalChatBotConsultationMessageForStaffAction $action,
    ) {
        $result = $action->index($consultation, $request->afterSequence(), $request->limit());

        return ApiResponse::success($result['items'], [
            'last_sequence' => $result['last_sequence'],
            'has_older' => $result['has_older'],
        ]);
    }

    public function sendMessage(
        int $consultation,
        HospitalChatBotConsultationMessageSendForStaffRequest $request,
        HospitalChatBotConsultationMessageForStaffAction $action,
    ) {
        return ApiResponse::success(
            $action->store($consultation, $request->content(), $request->clientMessageId()),
        );
    }
}
