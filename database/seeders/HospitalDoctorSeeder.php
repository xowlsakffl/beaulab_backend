<?php

namespace Database\Seeders;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Database\Seeder;

final class HospitalDoctorSeeder extends Seeder
{
    public function run(): void
    {
        $specialistFields = HospitalDoctor::specialistFields();
        $specialistFieldIndex = 0;
        $fallbackCategoryIds = Category::query()
            ->whereIn('domain', [Category::DOMAIN_HOSPITAL_REVIEW_SURGERY, Category::DOMAIN_HOSPITAL_REVIEW_TREATMENT])
            ->whereDoesntHave('children')
            ->pluck('id')
            ->all();

        Hospital::query()
            ->with('categories:id')
            ->get()
            ->each(function (Hospital $hospital) use ($fallbackCategoryIds, $specialistFields, &$specialistFieldIndex): void {
                $doctors = collect();
                $doctorCount = random_int(2, 5);

                for ($index = 0; $index < $doctorCount; $index++) {
                    $specialistField = $specialistFields[$specialistFieldIndex % count($specialistFields)];
                    $specialistFieldIndex++;

                    $doctors->push(
                        HospitalDoctor::factory()
                            ->forHospital($hospital)
                            ->specialistField($specialistField)
                            ->withSeedMedia()
                            ->create()
                    );
                }

                $availableCategoryIds = $hospital->categories->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                if ($availableCategoryIds === []) {
                    $availableCategoryIds = $fallbackCategoryIds;
                }

                if ($availableCategoryIds === []) {
                    return;
                }

                $maxAvailable = count($availableCategoryIds);
                $minAssignCount = min(1, $maxAvailable);
                $maxAssignCount = min(2, $maxAvailable);

                foreach ($doctors as $doctor) {
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

                    $doctor->categories()->sync($payload);
                }
            });
    }
}
