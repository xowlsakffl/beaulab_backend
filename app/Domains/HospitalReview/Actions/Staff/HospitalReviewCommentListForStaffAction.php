<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Domains\HospitalReview\Dto\Staff\HospitalReviewCommentForStaffDto;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalReview\Queries\Staff\HospitalReviewCommentListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalReviewCommentListForStaffAction
{
    public function __construct(
        private readonly HospitalReviewCommentListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalReviewComment::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($comment) => HospitalReviewCommentForStaffDto::fromModel($comment)->toArray())
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
