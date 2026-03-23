<?php

namespace App\Domains\HospitalDoctor\Queries\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HospitalDoctorListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $categoryIds = $filters['category_ids'] ?? null;
        $include = $filters['include'] ?? [];

        $builder = HospitalDoctor::query()->select([
            'id', 'hospital_id', 'name', 'position', 'is_specialist', 'sort_order',
            'gender', 'careers', 'allow_status', 'status', 'view_count', 'created_at', 'updated_at',
        ]);

        $builder->with([
            'hospital:id,name',
            'profileImage',
        ]);

        if (is_array($include) && in_array('categories', $include, true)) {
            $builder->with([
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.name', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);
        }

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%")
                    ->orWhere('license_number', 'like', "%{$q}%")
                    ->orWhereHas('hospital', fn ($hospitalQuery) => $hospitalQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        if (array_key_exists('is_specialist', $filters) && $filters['is_specialist'] !== null) {
            $builder->where('is_specialist', (bool) $filters['is_specialist']);
        }

        if (is_array($categoryIds) && $categoryIds !== []) {
            $expandedCategoryIds = $this->expandWithDescendants($categoryIds);

            if ($expandedCategoryIds === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $expandedCategoryIds));
            }
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
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
            ->whereIn('domain', [Category::DOMAIN_HOSPITAL_TREATMENT, Category::DOMAIN_HOSPITAL_SURGERY])
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
