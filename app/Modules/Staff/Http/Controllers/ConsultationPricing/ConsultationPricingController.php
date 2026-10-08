<?php

namespace App\Modules\Staff\Http\Controllers\ConsultationPricing;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\ConsultationPricing\Actions\ConsultationPricingManageAction;
use App\Domains\ConsultationPricing\Support\ConsultationPricingResolver;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Modules\Staff\Http\Requests\ConsultationPricing\ConsultationPricingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ConsultationPricingController extends Controller
{
    public function show(ConsultationPricingRequest $request, ConsultationPricingManageAction $action)
    {
        return ApiResponse::success($action->show($request->validated()));
    }

    public function save(ConsultationPricingRequest $request, ConsultationPricingManageAction $action)
    {
        return ApiResponse::success($action->save($request->validated(), $request->isMethod('DELETE')));
    }

    public function preview(Request $request, ConsultationPricingResolver $resolver)
    {
        Gate::authorize('preview', HospitalEvent::class);
        $input = $request->validate(['category_id' => ['required', 'integer', 'min:1'], 'event_price' => ['required', 'integer', 'min:0', 'max:2000000000']]);

        return ApiResponse::success($resolver->event((int) $input['category_id'], (int) $input['event_price']));
    }
}
