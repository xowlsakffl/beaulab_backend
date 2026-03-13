<?php

namespace Database\Seeders;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Seeder;

final class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        $approvedHospitals = Hospital::factory()
            ->count(10)
            ->approved()
            ->active()
            ->withBusinessRegistration()
            ->withAccountHospital()
            ->create();
        $this->attachRandomCategories($approvedHospitals);

        $mixedHospitals = Hospital::factory()
            ->count(5)
            ->withBusinessRegistration()
            ->withAccountHospital()
            ->create();
        $this->attachRandomCategories($mixedHospitals);
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
}
