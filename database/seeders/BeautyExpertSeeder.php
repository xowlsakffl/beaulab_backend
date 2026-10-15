<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\Common\Category\Models\Category;
use Illuminate\Database\Seeder;

final class BeautyExpertSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        $fallbackCategoryIds = Category::query()
            ->where('domain', Category::DOMAIN_BEAUTY_EXPERT)
            ->whereDoesntHave('children')
            ->pluck('id')
            ->all();

        Beauty::query()
            ->get()
            ->each(function (Beauty $beauty) use ($fallbackCategoryIds): void {
                $experts = BeautyExpert::factory()
                    ->count(fake()->numberBetween(2, 5))
                    ->forBeauty($beauty)
                    ->withSeedMedia()
                    ->create();

                // 전문가 분류는 뷰티 매장의 분류와 이름이 같아도 별도 ID를 사용한다.
                $availableCategoryIds = $fallbackCategoryIds;

                if ($availableCategoryIds === []) {
                    return;
                }

                $maxAvailable = count($availableCategoryIds);
                $minAssignCount = min(1, $maxAvailable);
                $maxAssignCount = min(2, $maxAvailable);

                foreach ($experts as $expert) {
                    $assignCount = fake()->numberBetween($minAssignCount, $maxAssignCount);
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
