<?php

namespace Database\Seeders;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Common\Models\Category\Category;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;

final class BeautySeeder extends Seeder
{
    public function run(): void
    {
        CategoryFactory::seedBeautyCategories();

        $approvedBeauties = Beauty::factory()
            ->count(10)
            ->approved()
            ->active()
            ->withAccountBeauty()
            ->create();
        $this->attachRandomCategories($approvedBeauties);

        $mixedBeauties = Beauty::factory()
            ->count(5)
            ->withAccountBeauty()
            ->create();
        $this->attachRandomCategories($mixedBeauties);
    }

    private function attachRandomCategories(iterable $beauties): void
    {
        $categoryIds = Category::query()
            ->where('domain', Category::DOMAIN_BEAUTY)
            ->whereDoesntHave('children')
            ->pluck('id')
            ->all();

        if ($categoryIds === []) {
            return;
        }

        $maxAvailable = count($categoryIds);
        $minAssignCount = min(2, $maxAvailable);
        $maxAssignCount = min(4, $maxAvailable);

        foreach ($beauties as $beauty) {
            $assignCount = random_int($minAssignCount, $maxAssignCount);
            $selectedCategoryIds = collect($categoryIds)
                ->shuffle()
                ->take($assignCount)
                ->values()
                ->all();

            $payload = [];
            foreach ($selectedCategoryIds as $index => $categoryId) {
                $payload[$categoryId] = ['is_primary' => $index === 0];
            }

            $beauty->categories()->syncWithoutDetaching($payload);
        }
    }
}
