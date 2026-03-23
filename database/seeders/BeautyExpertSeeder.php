<?php

namespace Database\Seeders;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\Common\Models\Category\Category;
use Illuminate\Database\Seeder;

final class BeautyExpertSeeder extends Seeder
{
    public function run(): void
    {
        $fallbackCategoryIds = Category::query()
            ->where('domain', Category::DOMAIN_BEAUTY)
            ->whereDoesntHave('children')
            ->pluck('id')
            ->all();

        Beauty::query()
            ->with('categories:id')
            ->get()
            ->each(function (Beauty $beauty) use ($fallbackCategoryIds): void {
                $experts = BeautyExpert::factory()
                    ->count(random_int(2, 5))
                    ->forBeauty($beauty)
                    ->withSeedMedia()
                    ->create();

                $availableCategoryIds = $beauty->categories->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                if ($availableCategoryIds === []) {
                    $availableCategoryIds = $fallbackCategoryIds;
                }

                if ($availableCategoryIds === []) {
                    return;
                }

                $maxAvailable = count($availableCategoryIds);
                $minAssignCount = min(1, $maxAvailable);
                $maxAssignCount = min(2, $maxAvailable);

                foreach ($experts as $expert) {
                    $assignCount = random_int($minAssignCount, $maxAssignCount);
                    $selectedCategoryIds = collect($availableCategoryIds)
                        ->shuffle()
                        ->take($assignCount)
                        ->values()
                        ->all();

                    $payload = [];
                    foreach ($selectedCategoryIds as $index => $categoryId) {
                        $payload[$categoryId] = ['is_primary' => $index === 0];
                    }

                    $expert->categories()->sync($payload);
                }
            });
    }
}
