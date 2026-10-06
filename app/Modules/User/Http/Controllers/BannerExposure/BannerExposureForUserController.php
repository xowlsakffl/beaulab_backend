<?php

namespace App\Modules\User\Http\Controllers\BannerExposure;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\BannerExposure\Actions\BannerExposurePublicAction;
use App\Domains\BannerExposure\Models\BannerExposure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class BannerExposureForUserController extends Controller
{
    public function index(Request $request, BannerExposurePublicAction $action)
    {
        $payload = $request->validate(['placement' => ['required', Rule::in(BannerExposure::PLACEMENTS)]]);

        return ApiResponse::success($action->listing($payload['placement']));
    }

    public function click(string $placement, string $sourceKey, BannerExposurePublicAction $action)
    {
        abort_unless(in_array($placement, BannerExposure::PLACEMENTS, true), 404);
        $action->click($placement, $sourceKey);

        return ApiResponse::success();
    }
}
