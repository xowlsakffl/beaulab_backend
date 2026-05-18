<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalReview\Dto\Staff\HospitalReviewForStaffDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Queries\Staff\HospitalReviewImageSummaryQuery;
use App\Domains\HospitalReview\Queries\Staff\HospitalReviewListForStaffQuery;
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
        $imageSummaries = HospitalReviewImageSummaryQuery::forReviewIds(
            collect($paginator->items())
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all(),
        );

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($review): array => HospitalReviewForStaffDto::fromModel(
                $review,
                $imageSummaries[(int) $review->id] ?? null,
            )->toArray(),
        );
    }
}
