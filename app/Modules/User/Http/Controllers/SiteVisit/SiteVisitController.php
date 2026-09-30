<?php

namespace App\Modules\User\Http\Controllers\SiteVisit;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\SiteVisit\Actions\RecordSiteVisitAction;
use App\Modules\User\Http\Requests\SiteVisit\SiteVisitRequest;

final class SiteVisitController extends Controller
{
    public function store(SiteVisitRequest $request, RecordSiteVisitAction $action)
    {
        $action->execute($request->validated('session_id'));

        return ApiResponse::success(null);
    }
}
