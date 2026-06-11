<?php

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HospitalEventListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalEvent::query()
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
                'status',
                'view_count',
                'consultation_count',
                'created_at',
                'updated_at',
            ])
            ->with([
                'hospital:id,name',
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.code', 'categories.domain', 'categories.name', 'categories.full_path', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'doctors:id,name,position',
                'thumbnailImage',
            ]);

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
                    ->orWhereHas('hospital', fn ($hospitalQuery) => $hospitalQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (is_array($filters['event_type'] ?? null) && $filters['event_type'] !== []) {
            $builder->whereIn('event_type', $filters['event_type']);
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
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

        if (! empty($filters['start_date'])) {
            $builder->whereDate('created_at', '>=', (string) $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $builder->whereDate('created_at', '<=', (string) $filters['end_date']);
        }

        if ($filters['event_price_min'] !== null) {
            $builder->where('event_price', '>=', (int) $filters['event_price_min']);
        }

        if ($filters['event_price_max'] !== null) {
            $builder->where('event_price', '<=', (int) $filters['event_price_max']);
        }

        $sort = (string) ($filters['sort'] ?? 'id');
        $direction = (string) ($filters['direction'] ?? 'desc');
        $builder->orderBy($sort, $direction);

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
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
