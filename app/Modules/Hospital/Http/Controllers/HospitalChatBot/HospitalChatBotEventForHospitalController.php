<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotEventStoreForHospitalAction;
use App\Modules\Hospital\Http\Requests\HospitalChatBot\HospitalChatBotEventStoreForHospitalRequest;

final class HospitalChatBotEventForHospitalController extends Controller
{
    public function store(
        HospitalChatBotEventStoreForHospitalRequest $request,
        HospitalChatBotEventStoreForHospitalAction $action,
    ) {
        // 받았다는 뜻의 202. 클라이언트는 응답을 기다리지 않으므로 본문은 건수만 돌려준다.
        return ApiResponse::success($action->execute($request->events()), null, null, 202);
    }
}
