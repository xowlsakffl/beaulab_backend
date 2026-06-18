<?php

namespace App\Modules\User\Http\Controllers\HospitalEvent;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEvent\Actions\User\HospitalEventDBCreateForUserAction;
use App\Domains\HospitalEvent\Actions\User\HospitalEventRealModelDBCreateForUserAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Modules\User\Http\Requests\HospitalEvent\HospitalEventDBCreateForUserRequest;
use App\Modules\User\Http\Requests\HospitalEvent\HospitalEventRealModelDBCreateForUserRequest;

final class HospitalEventForUserController extends Controller
{
    public function createHospitalEventDBForUser(
        HospitalEvent $hospitalEvent,
        HospitalEventDBCreateForUserRequest $request,
        HospitalEventDBCreateForUserAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), $hospitalEvent, [
            ...$request->validated(),
            'author_ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]));
    }

    public function createHospitalEventRealModelDBForUser(
        HospitalEvent $hospitalEvent,
        HospitalEventRealModelDBCreateForUserRequest $request,
        HospitalEventRealModelDBCreateForUserAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), $hospitalEvent, [
            ...$request->validated(),
            'author_ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]));
    }
}
