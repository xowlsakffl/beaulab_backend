<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Talk\Dto\Staff\TalkForStaffDetailDto;
use App\Domains\Talk\Models\Talk;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

/**
 * TalkGetForStaffAction 역할 정의.
 * 토크 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class TalkGetForStaffAction
{
    public function execute(Talk $talk, array $filters = []): array
    {
        Gate::authorize('view', $talk);

        $talk->load([
            'author',
            'categories',
            'images',
            'poll.options',
        ]);

        $operationHistories = $talk->operationHistories()
            ->with('actor')
            ->paginate(
                perPage: (int) ($filters['operation_histories_per_page'] ?? 15),
                pageName: 'operation_histories_page',
                page: (int) ($filters['operation_histories_page'] ?? 1),
            );

        $comments = $talk->comments()
            ->with(['author', 'operationHistories.actor', 'mentions.mentionedUser'])
            ->paginate(
                perPage: (int) ($filters['comments_per_page'] ?? 10),
                pageName: 'comments_page',
                page: (int) ($filters['comments_page'] ?? 1),
            );

        return [
            'talk' => TalkForStaffDetailDto::fromModel(
                $talk,
                operationHistories: $this->paginated(
                    $operationHistories,
                    fn ($history): array => TalkForStaffDetailDto::operationHistory($history),
                ),
                comments: $this->paginated(
                    $comments,
                    fn ($comment): array => TalkForStaffDetailDto::comment($comment),
                ),
            )->toArray(),
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    private function paginated(LengthAwarePaginator $paginator, callable $mapper): array
    {
        return [
            'items' => collect($paginator->items())
                ->map($mapper)
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
