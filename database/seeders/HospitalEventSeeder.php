<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Database\Seeder;

final class HospitalEventSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        DevelopmentDataGuard::requireCategories(Category::DOMAIN_HOSPITAL_MEDICAL);
        $this->ensureHospitalsAndDoctors();

        $hospitalIds = $this->approvedHospitalIds();
        $categoryIdsByUsage = $this->categoryIdsByUsage();

        if ($hospitalIds === [] || $categoryIdsByUsage === []) {
            $this->command?->warn('이벤트 시드 생성을 건너뜁니다: 승인 병의원 또는 이벤트 카테고리가 없습니다.');

            return;
        }

        foreach ($hospitalIds as $hospitalIndex => $hospitalId) {
            $eventCount = $hospitalIndex < 3 ? 18 : 3;

            for ($index = 0; $index < $eventCount; $index++) {
                $usages = CategoryUsage::hospitalEventUsages();
                $usage = $usages[$index % count($usages)];

                if (empty($categoryIdsByUsage[$usage])) {
                    $usage = array_key_first($categoryIdsByUsage);
                }

                $event = $this->createEvent($hospitalId, $usage, intdiv($index, 3));
                $this->syncCategories($event, $categoryIdsByUsage[$usage], $usage);
                $this->syncDoctors($event, $hospitalId);
                $category = $event->categories()->wherePivot('is_primary', true)->firstOrFail();
                $price = app(\App\Domains\ConsultationPricing\Support\ConsultationPricingResolver::class)->forEvent($event)['price'];
                $event->forceFill(['name' => mb_substr($category->name, 0, 12).' 상담 이벤트',
                    'base_consultation_price' => $price, 'consultation_price' => $price])->save();
            }
        }
    }

    private function ensureHospitalsAndDoctors(): void
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
            ->withSeedMedia()
            ->create();

        foreach ($hospitals as $hospital) {
            HospitalDoctor::factory()
                ->count(3)
                ->forHospital($hospital)
                ->active()
                ->approved()
                ->withSeedMedia()
                ->create();
        }
    }

    private function createEvent(int $hospitalId, string $usage, int $index): HospitalEvent
    {
        $factory = HospitalEvent::factory()
            ->forHospital($hospitalId)
            ->withSeedMedia();

        $isTextType = $index % 2 === 0;
        $factory = $isTextType ? $factory->textType() : $factory->imageType();
        $factory = match (true) {
            $index === 3 => $factory->rejected(),
            $index === 4 => $factory->pending(),
            $index === 5 => $factory->reviewing(),
            default => $factory->approved(),
        };
        $factory = $index === 3 ? $factory->forcedStopped() : $factory->active();

        if (! $isTextType && $usage === CategoryUsage::USAGE_HOSPITAL_EVENT_TREATMENT && $index % 2 === 1) {
            $factory = $factory->withOptions(fake()->numberBetween(2, 4));
        }

        return $factory->create([
            'created_at' => now()->subMonths(12),
            'updated_at' => now()->subDays(3),
            'is_event_period_unlimited' => $index === 0,
            'event_start_at' => $index === 1 ? now()->addDays(3) : now()->subMonths(11),
            'event_end_at' => match ($index) {
                0 => null, 2 => now()->subDays(2), default => now()->addDays(30)
            },
        ]);
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

    /**
     * @return array<string, array<int, int>>
     */
    private function categoryIdsByUsage(): array
    {
        $pathsByUsage = CategoryUsage::activeCategoryFullPathsByUsage(CategoryUsage::hospitalEventUsages());

        $out = [];
        foreach ($pathsByUsage as $usage => $rootPaths) {
            $out[$usage] = Category::query()
                ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
                ->where('status', Category::STATUS_ACTIVE)
                ->where(function ($query) use ($rootPaths): void {
                    foreach ($rootPaths as $rootPath) {
                        $query->orWhere('full_path', $rootPath)
                            ->orWhere('full_path', 'like', $rootPath.' > %');
                    }
                })
                ->where(static fn ($query) => Category::constrainActiveLeafHospitalMedicalCategory($query))
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();
        }

        return array_filter($out, static fn (array $ids): bool => $ids !== []);
    }

    /**
     * @param  array<int, int>  $categoryIds
     */
    private function syncCategories(HospitalEvent $event, array $categoryIds, string $usage): void
    {
        $maxCount = $usage === CategoryUsage::USAGE_HOSPITAL_EVENT_PROMOTION ? 1 : HospitalEvent::MAX_CATEGORY_COUNT;
        $selectedIds = collect($categoryIds)
            ->shuffle()
            ->take(fake()->numberBetween(1, min($maxCount, count($categoryIds))))
            ->values();
        if ($usage === CategoryUsage::USAGE_HOSPITAL_EVENT_PROMOTION) {
            $selectedIds = collect([$categoryIds[($event->hospital_id - 1) % count($categoryIds)]]);
        }

        $event->categories()->sync(
            $selectedIds
                ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                    $categoryId => ['is_primary' => $index === 0],
                ])
                ->all(),
        );
    }

    private function syncDoctors(HospitalEvent $event, int $hospitalId): void
    {
        $doctorIds = HospitalDoctor::query()
            ->where('hospital_id', $hospitalId)
            ->where('status', HospitalDoctor::STATUS_ACTIVE)
            ->where('allow_status', HospitalDoctor::ALLOW_APPROVED)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values();

        if ($doctorIds->isEmpty()) {
            $doctorIds = collect([
                (int) HospitalDoctor::factory()
                    ->forHospital($hospitalId)
                    ->active()
                    ->approved()
                    ->withSeedMedia()
                    ->create()
                    ->id,
            ]);
        }

        $event->doctors()->sync(
            $doctorIds
                ->shuffle()
                ->take(fake()->numberBetween(1, min(3, $doctorIds->count())))
                ->values()
                ->mapWithKeys(static fn (int $doctorId, int $index): array => [
                    $doctorId => [
                        'sort_order' => $index,
                        'is_career_visible' => true,
                        'is_activity_visible' => $index % 2 === 0,
                    ],
                ])
                ->all(),
        );
    }
}
