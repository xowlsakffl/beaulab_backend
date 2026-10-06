<?php

namespace App\Modules\User\Http\Controllers\VideoExposure;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\VideoExposure\Actions\VideoExposureClickAction;
use App\Domains\VideoExposure\Actions\VideoExposureReadAction;
use App\Domains\VideoExposure\Models\VideoExposure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class VideoExposureForUserController extends Controller
{
    public function click(Request $request, string $placement, string $sourceKey, VideoExposureClickAction $action)
    {
        abort_unless(in_array($placement, VideoExposure::PLACEMENTS, true), 404);
        $filters = validator($request->query(), ['category_id' => ['nullable', 'integer', 'min:1']])->validate();
        $action->execute($placement, isset($filters['category_id']) ? (int) $filters['category_id'] : null, $sourceKey);

        return ApiResponse::success();
    }

    public function index(Request $request, VideoExposureReadAction $action)
    {
        $filters = $request->validate(['placement' => ['required', Rule::in(VideoExposure::PLACEMENTS)], 'category_id' => ['nullable', 'integer', 'min:1']]);

        return ApiResponse::success($action->listing($filters));
    }
}
