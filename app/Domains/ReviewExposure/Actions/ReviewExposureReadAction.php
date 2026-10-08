<?php

namespace App\Domains\ReviewExposure\Actions;

use App\Domains\HospitalReview\Queries\Staff\HospitalReviewImageSummaryQuery;
use App\Domains\ReviewExposure\Dto\ReviewExposureDto;
use App\Domains\ReviewExposure\Models\ReviewExposure;
use App\Domains\ReviewExposure\Queries\ReviewExposureQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

final class ReviewExposureReadAction
{
    public function __construct(private readonly ReviewExposureQuery $query) {}

    public function categories(): array
    {
        Gate::authorize('viewAny', ReviewExposure::class);

        return $this->query->categories()->get(['id', 'name', 'group_code'])
            ->filter(fn ($row) => app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->presentation($row, 'HOSPITAL_REVIEW')['enabled'])
            ->sortBy(fn ($row) => app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->presentation($row, 'HOSPITAL_REVIEW')['sort_order'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->displayNameFor('HOSPITAL_REVIEW'), 'group_code' => $row->group_code])->values()->all();
    }

    public function board(array $filters): array
    {
        Gate::authorize('viewAny', ReviewExposure::class);

        return DB::transaction(function () use ($filters) {
            $scope = $this->query->scope($filters['placement'], $filters['category_id'] ?? null);
            $now = now()->startOfSecond();
            $keys = $this->query->activeKeys($scope, $now);
            $entries = $this->query->entries($scope, $now, $filters);
            $active = (clone $entries)->where('active', 1)->get()->sortBy(fn ($r) => array_search($r->source_key, $keys, true))->values();
            $inactive = (clone $entries)->where('active', 0)->orderByRaw('case when start_at > ? then 0 when end_at < ? then 2 else 1 end', [$now, $now])->orderBy('start_at')->orderBy('source')->orderBy('id')->paginate(15, ['*'], 'page', $filters['page'] ?? 1);

            return ['active' => ReviewExposureDto::rows($active, $now, $scope['key']), 'inactive' => ['items' => ReviewExposureDto::rows($inactive->items(), $now, $scope['key']), 'meta' => ['current_page' => $inactive->currentPage(), 'last_page' => $inactive->lastPage(), 'per_page' => 15, 'total' => $inactive->total()]],
                'active_keys' => $keys, 'count' => count($keys), 'limit' => ReviewExposure::LIMIT, 'revision' => (int) ($this->query->position($scope)?->revision ?? 0)];
        });
    }

    public function targets(array $filters): array
    {
        Gate::authorize('viewAny', ReviewExposure::class);
        $scope = $this->query->scope($filters['placement'], $filters['category_id'] ?? null);
        $query = $this->query->reviews($scope)->with('hospital:id,name');
        if ($term = trim($filters['q'] ?? '')) {
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%'.$term.'%')->orWhereHas('hospital', fn ($h) => $h->where('name', 'like', '%'.$term.'%'));
                if (ctype_digit($term)) {
                    $q->orWhere('hospital_reviews.id', (int) $term);
                }
            });
        }
        if (isset($term) && ctype_digit($term)) {
            $query->orderByRaw('hospital_reviews.id = ? desc', [(int) $term]);
        }
        $page = $query->orderByDesc('id')->paginate(20, ['*'], 'page', $filters['page'] ?? 1);
        $images = HospitalReviewImageSummaryQuery::forReviewIds($page->pluck('id')->all());

        return ['items' => $page->map(function ($e) use ($images) {
            $summary = $images[$e->id] ?? [];
            $image = $summary['first_image'] ?? null;

            return ['id' => $e->id, 'name' => $e->title, 'hospital_name' => $e->hospital?->name,
                'image' => $image ? ['id' => $image->id, 'path' => $image->publicPath(), 'metadata' => $image->publicMetadata()] : null,
                'image_count' => (int) ($summary['image_count'] ?? 0)];
        })->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => 20]];
    }

    public function listing(array $filters): array
    {
        return DB::transaction(function () use ($filters) {
            $scope = $this->query->scope($filters['placement'], $filters['category_id'] ?? null);
            $now = now()->startOfSecond();
            $keys = $this->query->activeKeys($scope, $now);
            $entries = $this->query->entries($scope, $now)->where('active', 1)->get()->sortBy(fn ($e) => array_search($e->source_key, $keys, true))->values();

            return array_map(fn ($row) => [
                ...array_intersect_key($row, array_flip(['source_key', 'review_id', 'name', 'hospital_name', 'image', 'image_count'])),
                'click_url' => URL::temporarySignedRoute('user.review-exposures.click', $now->copy()->addMinutes(30), [
                    'placement' => $scope['placement'], 'sourceKey' => $row['source_key'],
                    ...($scope['category_id'] === null ? [] : ['category_id' => $scope['category_id']]),
                ], absolute: false),
            ], ReviewExposureDto::rows($entries, $now, $scope['key']));
        });
    }
}
