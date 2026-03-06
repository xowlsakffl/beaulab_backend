<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Queries\Category\Staff\CategoryDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class CategoryDeleteForStaffAction
{
    public function __construct(
        private readonly CategoryDeleteForStaffQuery $query,
    ) {}

    public function execute(Category $category): array
    {
        Gate::authorize('delete', $category);

        $hasChildren = Category::query()
            ->where('domain', $category->domain)
            ->where('parent_id', $category->id)
            ->exists();

        if ($hasChildren) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '하위 카테고리가 있어 삭제할 수 없습니다.');
        }

        $hasAssignments = DB::table('category_assignments')
            ->where('category_id', $category->id)
            ->exists();

        if ($hasAssignments) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '연결된 데이터가 있어 삭제할 수 없습니다.');
        }

        $this->query->delete($category);

        Log::info('카테고리 삭제', [
            'category_id' => $category->id,
            'domain' => $category->domain,
        ]);

        return [
            'deleted_id' => (int) $category->id,
        ];
    }
}
