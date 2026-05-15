<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Domains\HospitalReview\Dto\Staff\HospitalReviewForStaffDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Queries\Staff\HospitalReviewListForStaffQuery;
use App\Domains\HospitalReview\Support\HospitalReviewImageSummary;
use Illuminate\Support\Facades\Gate;

final class HospitalReviewListForStaffAction
{
    public function __construct(
        private readonly HospitalReviewListForStaffQuery $query,
    ) {}

    public function execute(array $filters, string $categoryDomain): array
    {
        Gate::authorize('viewAny', HospitalReview::class);

        $paginator = $this->query->paginate([
            ...$filters,
            'category_domain' => $categoryDomain,
        ]);
        $imageSummaries = HospitalReviewImageSummary::forReviewIds(
            collect($paginator->items())
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all(),
        );

        return [
            'items' => collect($paginator->items())
                ->map(fn ($review) => HospitalReviewForStaffDto::fromModel(
                    $review,
                    $imageSummaries[(int) $review->id] ?? null,
                )->toArray())
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
