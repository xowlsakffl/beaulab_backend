<?php

namespace App\Domains\Common\Category\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Queries\Staff\CategoryDeleteForStaffQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use Illuminate\Support\Facades\Gate;

/**
 * CategoryDeleteForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class CategoryDeleteForStaffAction
{
    public function __construct(
        private readonly CategoryDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(Category $category): array
    {
        Gate::authorize('delete', $category);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($category) {
            \App\Domains\Common\Category\Models\CategoryTopic::orderBy('id')->lockForUpdate()->get();
            $category = Category::whereKey($category->id)->lockForUpdate()->firstOrFail();
            if ($this->query->hasChildren($category)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '하위 카테고리가 있어 삭제할 수 없습니다.');
            }
            if ($this->query->hasAssignments($category)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '연결된 데이터가 있어 삭제할 수 없습니다.');
            }
            $category->topics()->detach();
            $category->usages()->delete();
            $this->mediaAttachAction->deleteCollectionMedia($category, 'icon');
            $this->query->delete($category);

            return ['deleted_id' => (int) $category->id];
        }, 3);
    }
}
