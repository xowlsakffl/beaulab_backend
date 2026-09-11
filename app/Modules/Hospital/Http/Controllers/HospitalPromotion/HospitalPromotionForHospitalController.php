<?php

namespace App\Modules\Hospital\Http\Controllers\HospitalPromotion;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalPromotion\Actions\Hospital\HospitalPromotionForHospitalAction;

final class HospitalPromotionForHospitalController extends Controller
{
    public function index(HospitalPromotionForHospitalAction $action)
    {
        return ApiResponse::success($action->listing());
    }

    public function show(int $promotion, HospitalPromotionForHospitalAction $action)
    {
        return ApiResponse::success($action->detail($promotion));
    }

    public function click(int $promotion, HospitalPromotionForHospitalAction $action)
    {
        return ApiResponse::success($action->click($promotion));
    }
}
