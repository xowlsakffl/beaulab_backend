<?php

namespace App\Modules\Staff\Http\Controllers\HospitalEventAd;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdPricingManageAction;
use App\Modules\Staff\Http\Requests\HospitalEventAd\HospitalEventAdPricingRequest;

final class HospitalEventAdPricingController extends Controller
{
    public function show(HospitalEventAdPricingRequest $request, HospitalEventAdPricingManageAction $action)
    {
        return ApiResponse::success($action->show($request->validated('group')));
    }

    public function save(HospitalEventAdPricingRequest $request, HospitalEventAdPricingManageAction $action)
    {
        return ApiResponse::success($action->save($request->validated(), $request->isMethod('DELETE')));
    }
}
