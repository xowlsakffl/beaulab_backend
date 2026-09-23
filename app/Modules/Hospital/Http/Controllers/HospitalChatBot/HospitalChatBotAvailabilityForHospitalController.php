<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotAvailabilityForHospitalAction;

final class HospitalChatBotAvailabilityForHospitalController extends Controller
{
    public function show(HospitalChatBotAvailabilityForHospitalAction $action)
    {
        return ApiResponse::success($action->execute());
    }
}
