<?php

namespace Database\Seeders;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Database\Seeder;

final class HospitalDoctorSeeder extends Seeder
{
    public function run(): void
    {
        $specialistFields = HospitalDoctor::specialistFields();
        $specialistFieldIndex = 0;
        $doctorCategoryIds = Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->whereHas('usages', static fn ($query) => $query
                ->where('usage', CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT)
                ->where('status', CategoryUsage::STATUS_ACTIVE))
            ->pluck('id')
            ->all();

        Hospital::query()
            ->get()
            ->each(function (Hospital $hospital) use ($doctorCategoryIds, $specialistFields, &$specialistFieldIndex): void {
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

                if ($doctorCategoryIds === []) {
                    return;
                }

                $maxAvailable = count($doctorCategoryIds);
                $minAssignCount = min(1, $maxAvailable);
                $maxAssignCount = min(2, $maxAvailable);

                foreach ($doctors as $doctor) {
                    $assignCount = random_int($minAssignCount, $maxAssignCount);
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
