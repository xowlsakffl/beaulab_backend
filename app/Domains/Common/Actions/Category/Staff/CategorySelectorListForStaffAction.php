<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Domains\Common\Dto\Category\Staff\CategorySelectorForStaffDto;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Queries\Category\Staff\CategorySelectorListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * CategorySelectorListForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class CategorySelectorListForStaffAction
{
    public function __construct(
        private readonly CategorySelectorListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Category::class);

        $items = $this->query->get($filters)
            ->map(fn (Category $category): array => CategorySelectorForStaffDto::fromModel($category)->toArray())
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => null,
        ];
    }
}
