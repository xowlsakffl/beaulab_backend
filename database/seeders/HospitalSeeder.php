<?php

namespace Database\Seeders;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalFeature\Models\HospitalFeature;
use Illuminate\Database\Seeder;

final class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        $approvedHospitals = Hospital::factory()
            ->count(15)
            ->approved()
            ->active()
            ->withBusinessRegistration()
            ->withAccountHospital()
            ->withSeedMedia()
            ->create();
        $this->attachRandomCategories($approvedHospitals);
        $this->attachRandomFeatures($approvedHospitals);

        $mixedHospitals = Hospital::factory()
            ->count(10)
            ->withBusinessRegistration()
            ->withAccountHospital()
            ->withSeedMedia()
            ->create();
        $this->attachRandomCategories($mixedHospitals);
        $this->attachRandomFeatures($mixedHospitals);
    }

    private function attachRandomCategories(iterable $hospitals): void
    {
        $categoryIds = Category::query()
            ->whereIn('domain', [Category::DOMAIN_HOSPITAL_SURGERY, Category::DOMAIN_HOSPITAL_TREATMENT])
            ->whereDoesntHave('children')
            ->pluck('id')
            ->all();

        if ($categoryIds === []) {
            return;
        }

        $maxAvailable = count($categoryIds);
        $minAssignCount = min(2, $maxAvailable);
        $maxAssignCount = min(4, $maxAvailable);

        foreach ($hospitals as $hospital) {
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
            $assignCount = random_int($minAssignCount, $maxAssignCount);
            $selectedFeatureIds = collect($featureIds)
                ->shuffle()
                ->take($assignCount)
                ->values()
                ->all();

            $hospital->features()->sync($selectedFeatureIds);
        }
    }
}
