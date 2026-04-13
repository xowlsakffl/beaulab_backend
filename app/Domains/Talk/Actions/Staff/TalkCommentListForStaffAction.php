<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Talk\Dto\Staff\TalkCommentForStaffDto;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Queries\Staff\TalkCommentListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * TalkCommentListForStaffAction 역할 정의.
 * 토크 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class TalkCommentListForStaffAction
{
    public function __construct(
        private readonly TalkCommentListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', TalkComment::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($comment) => TalkCommentForStaffDto::fromModel($comment)->toArray())
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
