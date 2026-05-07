<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalReview\Dto\Staff\HospitalReviewCommentForStaffDetailDto;
use App\Domains\HospitalReview\Dto\Staff\HospitalReviewForStaffDetailDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalReviewGetForStaffAction 역할 정의.
 * 병원후기 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
class HospitalReviewGetForStaffAction
{
    public function execute(HospitalReview $review, array $filters = []): array
    {
        Gate::authorize('view', $review);

        $review->load([
            'author',
            'hospital.businessRegistration',
            'doctor',
            'categories',
            'beforeImages',
            'afterImages',
        ]);

        $operationHistories = $review->operationHistories()
            ->with('actor')
            ->paginate(
                perPage: (int) ($filters['operation_histories_per_page'] ?? 15),
                pageName: 'operation_histories_page',
                page: (int) ($filters['operation_histories_page'] ?? 1),
            );

        $comments = $review->comments()
            ->with(['author', 'operationHistories', 'mentions.mentionedUser'])
            ->paginate(
                perPage: (int) ($filters['comments_per_page'] ?? 10),
                pageName: 'comments_page',
                page: (int) ($filters['comments_page'] ?? 1),
            );

        return [
            'review' => HospitalReviewForStaffDetailDto::fromModel(
                $review,
                operationHistories: $this->paginated(
                    $operationHistories,
                    fn ($history): array => OperationHistoryDto::fromModel($history)->toArray(),
                ),
                comments: $this->paginated(
                    $comments,
                    fn ($comment): array => HospitalReviewCommentForStaffDetailDto::fromModel(
                        $comment->setRelation('review', $review),
                    )->toArray(),
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
