<?php

namespace App\Domains\Common\Category\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\Category\Dto\Staff\CategoryForStaffDto;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Queries\Staff\CategoryListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * CategoryListForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class CategoryListForStaffAction
{
    public function __construct(
        private readonly CategoryListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Category::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn (Category $category): array => CategoryForStaffDto::fromModel($category)->toArray(),
        );
    }
}
