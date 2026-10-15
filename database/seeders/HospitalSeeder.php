<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalFeature\Models\HospitalFeature;
use Illuminate\Database\Seeder;

final class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        $departments = Hospital::activeDepartments();
        for ($index = 0; $index < 25; $index++) {
            $allow = $index < 16 ? Hospital::ALLOW_APPROVED : [
                Hospital::ALLOW_NOT_APPLIED, Hospital::ALLOW_PENDING,
                Hospital::ALLOW_REVIEWING, Hospital::ALLOW_REJECTED,
            ][($index - 16) % 4];
            $factory = Hospital::factory()->active()->withBusinessRegistration();
            if ($allow !== Hospital::ALLOW_NOT_APPLIED) {
                $factory = $factory->withAccountHospital();
            }
            if ($index !== 24) {
                $factory = $factory->withSeedMedia($index % 5 + 1);
            }
            $hospital = $factory->create([
                'name' => sprintf('테스트 %s %02d의원', ['새봄', '온유', '다온', '하루', '바른'][$index % 5], $index + 1),
                'department' => $departments[$index % count($departments)],
                'allow_status' => $allow,
                'status' => match ($index) {
                    14 => Hospital::STATUS_SUSPENDED,
                    15 => Hospital::STATUS_WITHDRAWN,
                    default => Hospital::STATUS_ACTIVE,
                },
                'created_at' => now()->subMonths(14)->addDays($index),
                'updated_at' => now()->subMinutes($index + 1),
                'video_consented_at' => $index % 3 === 0 ? null : now()->subDays(30 + $index),
            ]);
            $this->attachRandomCategories([$hospital]);
            $this->attachRandomFeatures([$hospital]);
        }
    }

    private function attachRandomCategories(iterable $hospitals): void
    {
        $categoryIds = Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_SUBJECT)
            ->whereIn('categories.id', app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->ids(CategoryUsage::USAGE_HOSPITAL_SUBJECT))
            ->whereNull('categories.parent_id')
            ->pluck('id')
            ->all();

        if ($categoryIds === []) {
            return;
        }

        $maxAvailable = count($categoryIds);
        $minAssignCount = min(2, $maxAvailable);
        $maxAssignCount = min(5, $maxAvailable);

        foreach ($hospitals as $hospital) {
            $assignCount = fake()->numberBetween($minAssignCount, $maxAssignCount);
            $selectedCategoryIds = collect($categoryIds)
                ->shuffle()
                ->take($assignCount)
                ->values()
                ->all();

            $payload = [];
            foreach ($selectedCategoryIds as $index => $categoryId) {
                $payload[$categoryId] = ['is_primary' => $index === 0];
            }

            $hospital->categories()->syncWithoutDetaching($payload);
        }
    }

    private function attachRandomFeatures(iterable $hospitals): void
    {
        $featureIds = HospitalFeature::query()
            ->active()
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        if ($featureIds === []) {
            return;
        }

        $maxAvailable = count($featureIds);
        $minAssignCount = min(2, $maxAvailable);
        $maxAssignCount = min(6, $maxAvailable);

        foreach ($hospitals as $hospital) {
            $assignCount = fake()->numberBetween($minAssignCount, $maxAssignCount);
            $selectedFeatureIds = collect($featureIds)
                ->shuffle()
                ->take($assignCount)
                ->values()
                ->all();

            $hospital->features()->sync($selectedFeatureIds);
        }
    }
}
