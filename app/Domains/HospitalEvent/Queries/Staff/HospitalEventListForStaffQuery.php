<?php

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HospitalEventListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = $this->baseBuilder();

        $this->applyFilters($builder, $filters);

        $sort = $filters['sort'] ?? null;
        $direction = (string) ($filters['direction'] ?? 'desc');
        if ($sort === null) {
            $this->applyDefaultSort($builder);
        } else {
            $builder->orderBy($sort, $direction);
            if ($sort !== 'id') {
                $builder->orderByDesc('id');
            }
        }

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    private function baseBuilder(): Builder
    {
        return HospitalEvent::query()
            ->select([
                'id',
                'hospital_id',
                'event_type',
                'name',
                'description',
                'is_event_period_unlimited',
                'event_start_at',
                'event_end_at',
                'normal_price',
                'event_price',
                'is_vat_included',
                'discount_rate',
                'base_consultation_price',
                'consultation_price',
                'has_options',
                'allow_status',
                'hospital_status',
                'admin_status',
                'view_count',
                'created_at',
                'updated_at',
            ])
            ->with([
                'hospital:id,name',
                'hospital.accountHospital:id,hospital_id,name,nickname,email',
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.code', 'categories.domain', 'categories.name', 'categories.full_path', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'doctors:id,name,position',
                'thumbnailImage',
            ])
            ->withCount([
                'eventDBs as consultation_count',
                'eventDBs as confirmed_consultation_count' => fn ($query) => $query
                    ->where('status', HospitalEventDB::STATUS_CONFIRMED),
            ])
            ->withSum([
                'eventDBs as total_spent_point' => fn ($query) => $query
                    ->where('status', HospitalEventDB::STATUS_CONFIRMED),
            ], 'consultation_price');
    }

    private function applyDefaultSort(Builder $builder): void
    {
        $allowStatusColumn = $builder->getModel()->qualifyColumn('allow_status');
        $createdAtColumn = $builder->getModel()->qualifyColumn('created_at');
        $idColumn = $builder->getModel()->qualifyColumn('id');

        $builder
            ->orderByRaw("case when {$allowStatusColumn} in (?, ?) then 0 else 1 end", [
                HospitalEvent::ALLOW_PENDING,
                HospitalEvent::ALLOW_REVIEWING,
            ])
            ->orderByDesc($createdAtColumn)
            ->orderByDesc($idColumn);
    }

    private function applyFilters(Builder $builder, array $filters): void
    {
        $this->applySummaryFilter($builder, (string) ($filters['summary_filter'] ?? ''));

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                if (ctype_digit($q)) {
                    $query->whereKey((int) $q)
                        ->orWhere('name', 'like', "%{$q}%");
                } else {
                    $query->where('name', 'like', "%{$q}%");
                }

                $query
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('hospital', fn ($hospitalQuery) => $hospitalQuery
                        ->where('name', 'like', "%{$q}%")
                        ->orWhereHas('accountHospital', fn ($accountQuery) => $accountQuery
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('nickname', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")));
            });
        }

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (is_array($filters['event_type'] ?? null) && $filters['event_type'] !== []) {
            $builder->whereIn('event_type', $filters['event_type']);
        }

        if (is_array($filters['admin_status'] ?? null) && $filters['admin_status'] !== []) {
            $builder->whereIn('admin_status', $filters['admin_status']);
        }

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        $categoryIds = $filters['category_ids'] ?? null;
        if (is_array($categoryIds) && $categoryIds !== []) {
            $expandedCategoryIds = $this->expandCategoryIdsWithDescendants($categoryIds);

            if ($expandedCategoryIds === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $expandedCategoryIds));
            }
        }

        $dateTypes = is_array($filters['date_types'] ?? null) && $filters['date_types'] !== []
            ? $filters['date_types']
            : ['event_start_at'];

        if (! empty($filters['start_date']) || ! empty($filters['end_date'])) {
            $this->applyDateRangeFilter(
                $builder,
                $dateTypes,
                $filters['start_date'] ?? null,
                $filters['end_date'] ?? null,
            );
        }

        $this->applyMetricRangeFilter(
            $builder,
            (string) ($filters['quantity_metric'] ?? 'all'),
            ['view_count'],
            $filters['quantity_min'] ?? null,
            $filters['quantity_max'] ?? null,
        );

        $this->applyMetricRangeFilter(
            $builder,
            (string) ($filters['amount_metric'] ?? 'all'),
            ['event_price', 'consultation_price'],
            $filters['amount_min'] ?? null,
            $filters['amount_max'] ?? null,
        );

        if (($filters['event_price_min'] ?? null) !== null) {
            $builder->where('event_price', '>=', (int) $filters['event_price_min']);
        }

        if (($filters['event_price_max'] ?? null) !== null) {
            $builder->where('event_price', '<=', (int) $filters['event_price_max']);
        }
    }

    private function applySummaryFilter(Builder $builder, string $summaryFilter): void
    {
        if ($summaryFilter === '') {
            return;
        }

        $now = now();
        $recentStart = $now->copy()->subDays(30)->startOfDay();

        if ($summaryFilter === 'active') {
            $builder
                ->where('hospital_status', HospitalEvent::HOSPITAL_STATUS_PUBLIC)
                ->where('admin_status', HospitalEvent::ADMIN_STATUS_NORMAL)
                ->where('allow_status', HospitalEvent::ALLOW_APPROVED);

            return;
        }

        if ($summaryFilter === 'recent_created') {
            $builder->where('created_at', '>=', $recentStart);

            return;
        }

        if ($summaryFilter === 'ending_soon') {
            $builder
                ->where('is_event_period_unlimited', false)
                ->whereNotNull('event_end_at')
                ->whereBetween('event_end_at', [
                    $now->copy()->startOfDay(),
                    $now->copy()->addDays(30)->endOfDay(),
                ]);

            return;
        }

        if ($summaryFilter === 'recent_stopped') {
            $this->applyRecentPrivateOrEndedFilter($builder, $recentStart, $now);

            return;
        }

        $allowStatus = match ($summaryFilter) {
            'pending' => HospitalEvent::ALLOW_PENDING,
            'reviewing' => HospitalEvent::ALLOW_REVIEWING,
            'approved' => HospitalEvent::ALLOW_APPROVED,
            'rejected' => HospitalEvent::ALLOW_REJECTED,
            default => null,
        };

        if ($allowStatus !== null) {
            $builder->where('allow_status', $allowStatus);
        }
    }

    /**
     * @return array<int, int>
     */
    private function recentHospitalPrivateEventIds(mixed $recentStart): array
    {
        return OperationHistory::query()
            ->where('target_type', HospitalEvent::class)
            ->where('actor_kind', OperationHistory::ACTOR_KIND_HOSPITAL)
            ->whereHas('changes', static fn ($query) => $query
                ->where('field_key', 'hospital_status')
                ->whereIn('after_display', [HospitalEvent::HOSPITAL_STATUS_PRIVATE, '비공개']))
            ->where('created_at', '>=', $recentStart)
            ->distinct()
            ->pluck('target_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();
    }

    private function applyRecentPrivateOrEndedFilter(Builder $builder, mixed $recentStart, mixed $now): void
    {
        $privateEventIds = $this->recentHospitalPrivateEventIds($recentStart);

        $builder->where(function (Builder $query) use ($privateEventIds, $recentStart, $now): void {
            $query
                ->where(function (Builder $privateQuery) use ($privateEventIds): void {
                    if ($privateEventIds === []) {
                        $privateQuery->whereRaw('1 = 0');

                        return;
                    }

                    $privateQuery
                        ->where('hospital_status', HospitalEvent::HOSPITAL_STATUS_PRIVATE)
                        ->whereIn('id', $privateEventIds);
                })
                ->orWhere(fn (Builder $endedQuery) => $this->applyRecentlyEndedFilter($endedQuery, $recentStart, $now));
        });
    }

    private function applyRecentlyEndedFilter(Builder $builder, mixed $recentStart, mixed $now): void
    {
        $builder
            ->where('is_event_period_unlimited', false)
            ->whereNotNull('event_end_at')
            ->whereBetween('event_end_at', [
                $recentStart,
                $now->copy()->subDay()->endOfDay(),
            ]);
    }

    /**
     * @param  array<int, string>  $dateTypes
     */
    private function applyDateRangeFilter(Builder $builder, array $dateTypes, mixed $startDate, mixed $endDate): void
    {
        $columns = collect($dateTypes)
            ->map(static fn (mixed $dateType): string => (string) $dateType)
            ->filter(static fn (string $dateType): bool => in_array($dateType, ['event_start_at', 'event_end_at'], true))
            ->unique()
            ->values()
            ->all();

        if ($columns === []) {
            return;
        }

        $builder->where(function ($query) use ($columns, $startDate, $endDate): void {
            foreach ($columns as $column) {
                $query->orWhere(function ($nested) use ($column, $startDate, $endDate): void {
                    DateRangeFilter::apply($nested, $column, $startDate, $endDate);
                });
            }
        });
    }

    /**
     * @param  array<int, string>  $allowedColumns
     */
    private function applyMetricRangeFilter(Builder $builder, string $metric, array $allowedColumns, mixed $min, mixed $max): void
    {
        if ($min === null && $max === null) {
            return;
        }

        $columns = $metric === 'all'
            ? $allowedColumns
            : (in_array($metric, $allowedColumns, true) ? [$metric] : []);

        if ($columns === []) {
            return;
        }

        $builder->where(function ($query) use ($columns, $min, $max): void {
            foreach ($columns as $column) {
                $query->orWhere(function ($nested) use ($column, $min, $max): void {
                    if ($min !== null) {
                        $nested->where($column, '>=', (int) $min);
                    }

                    if ($max !== null) {
                        $nested->where($column, '<=', (int) $max);
                    }
                });
            }
        });
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     * @return array<int, int>
     */
    private function expandCategoryIdsWithDescendants(array $categoryIds): array
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
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->get();

        if ($selectedCategories->isEmpty()) {
            return [];
        }

        return Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where(function ($query) use ($selectedCategories): void {
                foreach ($selectedCategories as $selectedCategory) {
                    $pathPrefix = trim((string) ($selectedCategory->full_path ?: $selectedCategory->name));

                    $query->orWhere(function ($nested) use ($selectedCategory, $pathPrefix): void {
                        $nested->where('id', (int) $selectedCategory->id);

                        if ($pathPrefix !== '') {
                            $nested->orWhere('full_path', 'like', $pathPrefix.' > %');
                        }
                    });
                }
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
