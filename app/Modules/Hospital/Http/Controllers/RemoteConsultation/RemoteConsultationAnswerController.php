<?php

namespace App\Modules\Hospital\Http\Controllers\RemoteConsultation;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\RemoteConsultation\Actions\RemoteConsultationAnswerForHospitalAction;
use App\Modules\Hospital\Http\Requests\RemoteConsultation\RemoteConsultationAnswerRequest;

final class RemoteConsultationAnswerController extends Controller
{
    public function update(RemoteConsultationAnswerRequest $request, int $quote, RemoteConsultationAnswerForHospitalAction $action)
    {
        return ApiResponse::success($action->execute($request->user(), $quote, $request->validated()));
    }
}
