<?php

namespace App\Modules\User\Http\Controllers\ReviewExposure;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\ReviewExposure\Actions\ReviewExposureClickAction;
use App\Domains\ReviewExposure\Actions\ReviewExposureReadAction;
use App\Domains\ReviewExposure\Models\ReviewExposure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ReviewExposureForUserController extends Controller
{
    public function click(Request $request, string $placement, string $sourceKey, ReviewExposureClickAction $action)
    {
        abort_unless(in_array($placement, ReviewExposure::PLACEMENTS, true), 404);
        $filters = validator($request->query(), ['category_id' => ['nullable', 'integer', 'min:1']])->validate();
        $action->execute($placement, isset($filters['category_id']) ? (int) $filters['category_id'] : null, $sourceKey);

        return ApiResponse::success();
    }

    public function index(Request $request, ReviewExposureReadAction $action)
    {
        $filters = $request->validate(['placement' => ['required', Rule::in(ReviewExposure::PLACEMENTS)], 'category_id' => ['nullable', 'integer', 'min:1']]);

        return ApiResponse::success($action->listing($filters));
    }
}
