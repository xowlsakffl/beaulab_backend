<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Database\Seeder;

final class HospitalDoctorSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        $specialistFields = HospitalDoctor::specialistFields();
        $specialistFieldIndex = 0;
        $doctorCategoryIds = Category::query()
            ->where('domain', Category::DOMAIN_DOCTOR_SUBJECT)
            ->whereIn('categories.id', app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->ids(CategoryUsage::USAGE_DOCTOR_SUBJECT))
            ->whereNull('categories.parent_id')
            ->pluck('id')
            ->all();

        Hospital::query()
            ->get()
            ->each(function (Hospital $hospital, int $hospitalIndex) use ($doctorCategoryIds, $specialistFields, &$specialistFieldIndex): void {
                $doctors = collect();
                $doctorCount = $hospitalIndex === 0 ? 7 : 5;

                for ($index = 0; $index < $doctorCount; $index++) {
                    $specialistField = $specialistFields[$specialistFieldIndex % count($specialistFields)];
                    $specialistFieldIndex++;

                    $doctors->push(
                        HospitalDoctor::factory()
                            ->forHospital($hospital)
                            ->specialistField($specialistField)
                            ->withSeedMedia()
                            ->create([
                                'status' => match ($index) {
                                    4 => 'SUSPENDED', 6 => 'INACTIVE', default => 'ACTIVE'
                                },
                                'allow_status' => ['APPROVED', 'PENDING', 'REVIEWING', 'REJECTED', 'APPROVED', 'NOT_APPLIED', 'APPROVED'][$index],
                                'created_at' => now()->subMonths(13),
                            ])
                    );
                }

                if ($doctorCategoryIds === []) {
                    return;
                }

                $maxAvailable = count($doctorCategoryIds);
                $minAssignCount = min(1, $maxAvailable);
                $maxAssignCount = min(2, $maxAvailable);

                foreach ($doctors as $doctor) {
                    $assignCount = fake()->numberBetween($minAssignCount, $maxAssignCount);
                    $selectedCategoryIds = collect($doctorCategoryIds)
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
