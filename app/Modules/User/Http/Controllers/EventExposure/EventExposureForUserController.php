<?php

namespace App\Modules\User\Http\Controllers\EventExposure;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\EventExposure\Actions\EventExposureClickAction;
use App\Domains\EventExposure\Actions\EventExposureReadAction;
use App\Domains\EventExposure\Models\EventExposure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class EventExposureForUserController extends Controller
{
    public function click(Request $request, string $placement, string $sourceKey, EventExposureClickAction $action)
    {
        abort_unless(in_array($placement, EventExposure::PLACEMENTS, true), 404);
        $filters = validator($request->query(), ['category_id' => ['nullable', 'integer', 'min:1']])->validate();
        $action->execute($placement, isset($filters['category_id']) ? (int) $filters['category_id'] : null, $sourceKey);

        return ApiResponse::success();
    }

    public function index(Request $request, EventExposureReadAction $action)
    {
        $filters = $request->validate(['placement' => ['required', Rule::in(EventExposure::PLACEMENTS)], 'category_id' => ['nullable', 'integer', 'min:1']]);

        return ApiResponse::success($action->listing($filters));
    }
}
