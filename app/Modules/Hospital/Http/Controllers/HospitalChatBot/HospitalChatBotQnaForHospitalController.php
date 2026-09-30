<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotQnaListForHospitalAction;

final class HospitalChatBotQnaForHospitalController extends Controller
{
    public function index(HospitalChatBotQnaListForHospitalAction $action)
    {
        return ApiResponse::success($action->execute());
    }
}
