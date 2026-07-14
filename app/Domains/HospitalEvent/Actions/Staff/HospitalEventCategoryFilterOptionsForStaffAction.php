<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\Category\Dto\Staff\CategorySelectorForStaffDto;
use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventCategoryFilterOptionsForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEventCategoryFilterOptionsForStaffAction
{
    public function __construct(
        private readonly HospitalEventCategoryFilterOptionsForStaffQuery $query,
    ) {}

    public function execute(): array
    {
        Gate::authorize('viewAny', HospitalEvent::class);

        $majorCategories = $this->query->majorCategories();
        $middleCategories = $this->query->middleCategoriesByParents(
            $majorCategories
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all(),
        );

        return [
            'major_categories' => $majorCategories
                ->map(fn (Category $category): array => CategorySelectorForStaffDto::fromModel($category)->toArray())
                ->values()
                ->all(),
            'middle_categories_by_parent' => $middleCategories
                ->groupBy(static fn (Category $category): string => (string) $category->parent_id)
                ->map(static fn ($items): array => $items
                    ->map(fn (Category $category): array => CategorySelectorForStaffDto::fromModel($category)->toArray())
                    ->values()
                    ->all())
                ->all(),
        ];
    }
}
