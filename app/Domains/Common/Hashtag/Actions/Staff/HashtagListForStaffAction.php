<?php

namespace App\Domains\Common\Hashtag\Actions\Staff;

use App\Domains\Common\Hashtag\Dto\Staff\HashtagForStaffDto;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Hashtag\Queries\Staff\HashtagListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HashtagListForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HashtagListForStaffAction
{
    public function __construct(
        private readonly HashtagListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Hashtag::class);

        $paginator = $this->query->paginate($filters);

        $items = collect($paginator->items())
            ->map(fn (Hashtag $hashtag): array => HashtagForStaffDto::fromModel($hashtag)->toArray())
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
