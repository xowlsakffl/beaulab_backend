<?php

namespace App\Modules\User\Http\Controllers\HospitalEvent;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEvent\Actions\User\HospitalEventConsultationCreateForUserAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Modules\User\Http\Requests\HospitalEvent\HospitalEventConsultationCreateForUserRequest;

final class HospitalEventForUserController extends Controller
{
    public function createHospitalEventConsultationForUser(
        HospitalEvent $hospitalEvent,
        HospitalEventConsultationCreateForUserRequest $request,
        HospitalEventConsultationCreateForUserAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), $hospitalEvent, [
            ...$request->validated(),
            'author_ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]));
    }
}
