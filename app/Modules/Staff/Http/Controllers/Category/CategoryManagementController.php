<?php

namespace App\Modules\Staff\Http\Controllers\Category;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Category\Actions\Staff\CategoryManagementAction;
use App\Modules\Staff\Http\Requests\Category\CategoryManagementRequest;

final class CategoryManagementController extends Controller
{
    public function show(CategoryManagementRequest $request, CategoryManagementAction $action)
    {
        return ApiResponse::success($action->show($request->validated('domain')));
    }

    public function save(CategoryManagementRequest $request, CategoryManagementAction $action)
    {
        return ApiResponse::success($action->save($request->validated()));
    }
}
