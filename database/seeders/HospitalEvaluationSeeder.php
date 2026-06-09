<?php

namespace Database\Seeders;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class HospitalEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        CategoryFactory::seedHospitalEvaluationCategories();
        $this->ensureSeedUsers();
        $this->ensureSeedHospitals();

        $authorIds = $this->activeUserIds();
        $hospitalIds = $this->approvedHospitalIds();
        $categoryIdsByCode = $this->categoryIdsByCode(HospitalEvaluation::categoryCodes());

        if ($authorIds === [] || $hospitalIds === [] || $categoryIdsByCode === []) {
            $this->command?->warn('HospitalEvaluationSeeder skipped: active users, approved hospitals, or evaluation categories are missing.');

            return;
        }

        $this->seedEvaluations(120, $authorIds, $hospitalIds, $categoryIdsByCode, 'active');
        $this->seedEvaluations(12, $authorIds, $hospitalIds, $categoryIdsByCode, 'autoBlind');
        $this->seedEvaluations(8, $authorIds, $hospitalIds, $categoryIdsByCode, 'adminStopped');
        $this->seedEvaluations(6, $authorIds, $hospitalIds, $categoryIdsByCode, 'userDeleted');
        $this->seedEvaluations(8, $authorIds, $hospitalIds, $categoryIdsByCode, 'inactive');
    }

    private function ensureSeedUsers(): void
    {
        if (AccountUser::query()->where('status', AccountUser::STATUS_ACTIVE)->exists()) {
            return;
        }

        AccountUser::factory()->count(10)->create();
    }

    private function ensureSeedHospitals(): void
    {
        if (Hospital::query()
            ->where('status', Hospital::STATUS_ACTIVE)
            ->where('allow_status', Hospital::ALLOW_APPROVED)
            ->exists()
        ) {
            return;
        }

        $hospitals = Hospital::factory()
            ->count(5)
            ->active()
            ->approved()
            ->withBusinessRegistration()
            ->create();

        foreach ($hospitals as $hospital) {
            HospitalDoctor::factory()
                ->count(2)
                ->forHospital($hospital)
                ->active()
                ->approved()
                ->withSeedMedia()
                ->create();
        }
    }

    /**
     * @param  array<int, int>  $authorIds
     * @param  array<int, int>  $hospitalIds
     * @param  array<string, array<int, int>>  $categoryIdsByCode
     * @return Collection<int, HospitalEvaluation>
     */
    private function seedEvaluations(
        int $count,
        array $authorIds,
        array $hospitalIds,
        array $categoryIdsByCode,
        string $factoryState,
    ): Collection {
        $evaluations = collect();

        for ($index = 0; $index < $count; $index++) {
            $categoryCode = array_rand($categoryIdsByCode);
            $hospitalId = $hospitalIds[array_rand($hospitalIds)];
            $receiptState = $this->receiptState();
            $withReceiptImages = $receiptState !== null;

            $factory = HospitalEvaluation::factory()
                ->{$factoryState}()
                ->withSeedMedia(random_int(1, 3), $withReceiptImages);

            if ($receiptState !== null) {
                $factory = $factory->{$receiptState}();
            }

            $evaluation = $factory->create([
                'author_id' => $authorIds[array_rand($authorIds)],
                'hospital_id' => $hospitalId,
                'doctor_id' => $this->randomDoctorId($hospitalId),
            ]);

            $this->syncCategories($evaluation, $categoryIdsByCode[$categoryCode]);
            $evaluations->push($evaluation);
        }

        return $evaluations;
    }

    private function receiptState(): ?string
    {
        return fake()->randomElement([
            null,
            null,
            null,
            'receiptUploaded',
            'receiptVerified',
            'receiptRejected',
        ]);
    }

    /**
     * @param  array<int, int>  $categoryIds
     */
    private function syncCategories(HospitalEvaluation $evaluation, array $categoryIds): void
    {
        $selectedIds = collect($categoryIds)->take(1)->values();

        $evaluation->categories()->sync(
            $selectedIds
                ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                    $categoryId => ['is_primary' => $index === 0],
                ])
                ->all(),
        );
    }

    /**
     * @return array<int, int>
     */
    private function activeUserIds(): array
    {
        return AccountUser::query()
            ->where('status', AccountUser::STATUS_ACTIVE)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function approvedHospitalIds(): array
    {
        return Hospital::query()
            ->where('status', Hospital::STATUS_ACTIVE)
            ->where('allow_status', Hospital::ALLOW_APPROVED)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values()
            ->all();
    }

    private function randomDoctorId(int $hospitalId): ?int
    {
        $doctorIds = HospitalDoctor::query()
            ->where('hospital_id', $hospitalId)
            ->where('status', HospitalDoctor::STATUS_ACTIVE)
            ->where('allow_status', HospitalDoctor::ALLOW_APPROVED)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values()
            ->all();

        if ($doctorIds === []) {
            $doctor = HospitalDoctor::factory()
                ->forHospital($hospitalId)
                ->active()
                ->approved()
                ->withSeedMedia()
                ->create();

            return (int) $doctor->id;
        }

        return $doctorIds[array_rand($doctorIds)];
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<string, array<int, int>>
     */
    private function categoryIdsByCode(array $codes): array
    {
        $categories = Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_EVALUATION)
            ->whereIn('code', $codes)
            ->whereNull('parent_id')
            ->where('status', Category::STATUS_ACTIVE)
            ->orderBy('depth')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code']);

        $grouped = [];
        foreach ($categories as $category) {
            $grouped[(string) $category->code][] = (int) $category->id;
        }

        return array_filter($grouped, static fn (array $ids): bool => $ids !== []);
    }
}
