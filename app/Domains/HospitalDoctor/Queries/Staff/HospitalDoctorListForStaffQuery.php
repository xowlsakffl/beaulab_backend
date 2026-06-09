<?php

namespace App\Domains\HospitalDoctor\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * HospitalDoctorListForStaffQuery 역할 정의.
 * 병원 의사 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalDoctorListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $categoryIds = $filters['category_ids'] ?? null;
        $builder = HospitalDoctor::query()->select([
            'id', 'hospital_id', 'name', 'position', 'specialist_field', 'license_number',
            'gender', 'career_started_at', 'allow_status', 'created_at',
        ]);
        $builder->addSelect(DB::raw('0 as consultation_count'));

        $builder->with([
            'hospital:id,name',
            'profileImage',
            'categories' => fn ($query) => $query
                ->select(['categories.id', 'categories.domain', 'categories.name', 'categories.depth', 'categories.full_path', 'categories.sort_order'])
                ->orderBy('depth')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);
        $builder->withCount(['reviews as review_count']);

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                if (ctype_digit($q)) {
                    $query->whereKey((int) $q)
                        ->orWhere('name', 'like', "%{$q}%");
                } else {
                    $query->where('name', 'like', "%{$q}%");
                }

                $query->orWhere('license_number', 'like', "%{$q}%")
                    ->orWhereHas('hospital', fn ($hospitalQuery) => $hospitalQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        if (is_array($filters['position'] ?? null) && $filters['position'] !== []) {
            $builder->whereIn('position', $filters['position']);
        }

        if (is_array($filters['specialist_field'] ?? null) && $filters['specialist_field'] !== []) {
            $builder->whereIn('specialist_field', $filters['specialist_field']);
        }

        if (! empty($filters['start_date'])) {
            $builder->whereDate('created_at', '>=', (string) $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $builder->whereDate('created_at', '<=', (string) $filters['end_date']);
        }

        if (is_array($categoryIds) && $categoryIds !== []) {
            $expandedCategoryIds = $this->expandWithDescendants($categoryIds);

            if ($expandedCategoryIds === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $expandedCategoryIds));
            }
        }

        $this->applyMetricFilter($builder, $filters);
        $this->applySort($builder, $filters);

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    private function applyMetricFilter($builder, array $filters): void
    {
        $metric = $filters['metric'] ?? null;
        $min = array_key_exists('metric_min', $filters) && $filters['metric_min'] !== null
            ? (int) $filters['metric_min']
            : null;
        $max = array_key_exists('metric_max', $filters) && $filters['metric_max'] !== null
            ? (int) $filters['metric_max']
            : null;

        if ($metric === null || ($min === null && $max === null)) {
            return;
        }

        if ($metric === 'career_years') {
            $builder->whereNotNull('career_started_at');
            $today = Carbon::today();

            if ($min !== null) {
                $builder->whereDate('career_started_at', '<=', $today->copy()->subYears($min)->toDateString());
            }

            if ($max !== null) {
                $builder->whereDate('career_started_at', '>', $today->copy()->subYears($max + 1)->toDateString());
            }

            return;
        }

        if ($metric === 'review_count') {
            if ($min !== null) {
                $builder->having('review_count', '>=', $min);
            }

            if ($max !== null) {
                $builder->having('review_count', '<=', $max);
            }

            return;
        }

        if ($metric === 'consultation_count' && $min !== null && $min > 0) {
            $builder->whereRaw('1 = 0');
        }
    }

    private function applySort($builder, array $filters): void
    {
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';

        if ($sort === 'career_years') {
            $builder->orderBy('career_started_at', $direction === 'desc' ? 'asc' : 'desc');
            return;
        }

        $builder->orderBy($sort, $direction);
    }

    /**
     * @param array<int, int|string> $categoryIds
     * @return array<int, int>
     */
    private function expandWithDescendants(array $categoryIds): array
    {
        $selectedCategoryIds = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->all();

        if ($selectedCategoryIds === []) {
            return [];
        }

        $selectedCategories = Category::query()
            ->select(['id', 'domain', 'name', 'full_path'])
            ->whereIn('id', $selectedCategoryIds)
            ->where('domain', Category::DOMAIN_HOSPITAL_DOCTER)
            ->get();

        if ($selectedCategories->isEmpty()) {
            return [];
        }

        return Category::query()
            ->select('id')
            ->where(function ($query) use ($selectedCategories): void {
                foreach ($selectedCategories as $selectedCategory) {
                    $pathPrefix = trim((string) ($selectedCategory->full_path ?: $selectedCategory->name));

                    $query->orWhere(function ($nested) use ($selectedCategory, $pathPrefix): void {
                        $nested->where('domain', (string) $selectedCategory->domain)
                            ->where(function ($pathQuery) use ($selectedCategory, $pathPrefix): void {
                                $pathQuery->where('id', (int) $selectedCategory->id);

                                if ($pathPrefix !== '') {
                                    $pathQuery->orWhere('full_path', 'like', $pathPrefix . ' > %');
                                }
                            });
                    });
                }
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
