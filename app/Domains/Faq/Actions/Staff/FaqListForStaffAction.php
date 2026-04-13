<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\Faq\Dto\Staff\FaqForStaffDto;
use App\Domains\Faq\Models\Faq;
use App\Domains\Faq\Queries\Staff\FaqListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * FaqListForStaffAction 역할 정의.
 * FAQ 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class FaqListForStaffAction
{
    public function __construct(
        private readonly FaqListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Faq::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(static fn (Faq $faq): array => FaqForStaffDto::fromModel($faq)->toArray())
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
