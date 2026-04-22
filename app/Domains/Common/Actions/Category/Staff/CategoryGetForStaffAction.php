<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Domains\Common\Dto\Category\Staff\CategoryForStaffDto;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Queries\Category\Staff\CategoryGetForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * CategoryGetForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class CategoryGetForStaffAction
{
    public function __construct(
        private readonly CategoryGetForStaffQuery $query,
    ) {}

    /**
     * @param array<int, string> $include
     */
    public function execute(Category $category, array $include = []): array
    {
        Gate::authorize('view', $category);

        Log::info('카테고리 단건 조회', [
            'category_id' => $category->id,
            'include' => $include,
        ]);

        $detail = $this->query->get($category, $include);

        return [
            'category' => CategoryForStaffDto::fromModel($detail)->toArray(),
        ];
    }
}
