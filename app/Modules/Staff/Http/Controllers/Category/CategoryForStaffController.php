<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\Category;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Actions\Category\Staff\CategoryCreateForStaffAction;
use App\Domains\Common\Actions\Category\Staff\CategoryDeleteForStaffAction;
use App\Domains\Common\Actions\Category\Staff\CategoryGetForStaffAction;
use App\Domains\Common\Actions\Category\Staff\CategoryListForStaffAction;
use App\Domains\Common\Actions\Category\Staff\CategoryUpdateForStaffAction;
use App\Domains\Common\Models\Category\Category;
use App\Modules\Staff\Http\Requests\Category\CategoryCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Category\CategoryGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Category\CategoryListForStaffRequest;
use App\Modules\Staff\Http\Requests\Category\CategoryUpdateForStaffRequest;

final class CategoryForStaffController extends Controller
{
    public function getCategoriesForStaff(
        CategoryListForStaffRequest $request,
        CategoryListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getCategoryForStaff(
        Category $category,
        CategoryGetForStaffRequest $request,
        CategoryGetForStaffAction $action,
    ) {
        $result = $action->execute($category, $request->filters()['include']);

        return ApiResponse::success($result['category'] ?? $result);
    }

    public function storeCategoryForStaff(
        CategoryCreateForStaffRequest $request,
        CategoryCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['category'] ?? $result);
    }

    public function updateCategoryForStaff(
        Category $category,
        CategoryUpdateForStaffRequest $request,
        CategoryUpdateForStaffAction $action,
    ) {
        $result = $action->execute($category, $request->validated());

        return ApiResponse::success($result['category'] ?? $result);
    }

    public function deleteCategoryForStaff(
        Category $category,
        CategoryDeleteForStaffAction $action,
    ) {
        $result = $action->execute($category);

        return ApiResponse::success($result);
    }
}
