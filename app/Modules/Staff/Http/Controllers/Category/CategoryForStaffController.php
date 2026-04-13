<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\Category;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Actions\Category\Staff\CategoryCreateForStaffAction;
use App\Domains\Common\Actions\Category\Staff\CategoryDeleteForStaffAction;
use App\Domains\Common\Actions\Category\Staff\CategoryGetForStaffAction;
use App\Domains\Common\Actions\Category\Staff\CategoryListForStaffAction;
use App\Domains\Common\Actions\Category\Staff\CategorySelectorListForStaffAction;
use App\Domains\Common\Actions\Category\Staff\CategoryUpdateForStaffAction;
use App\Domains\Common\Models\Category\Category;
use App\Modules\Staff\Http\Requests\Category\CategoryCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Category\CategoryGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Category\CategoryListForStaffRequest;
use App\Modules\Staff\Http\Requests\Category\CategorySelectorListForStaffRequest;
use App\Modules\Staff\Http\Requests\Category\CategoryUpdateForStaffRequest;

/**
 * CategoryForStaffController 역할 정의.
 * 스태프 모듈의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class CategoryForStaffController extends Controller
{
    public function getCategoriesForStaff(
        CategoryListForStaffRequest $request,
        CategoryListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getCategorySelectorForStaff(
        CategorySelectorListForStaffRequest $request,
        CategorySelectorListForStaffAction $action,
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
