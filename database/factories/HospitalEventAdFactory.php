<?php

namespace Database\Factories;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalEventAd>
 */
final class HospitalEventAdFactory extends Factory
{
    protected $model = HospitalEventAd::class;

    public function configure(): static
    {
        return $this->afterCreating(function (HospitalEventAd $ad): void {
            if (! HospitalEventAd::requiresCategory((string) $ad->placement)) {
                $ad->categories()->sync([]);

                return;
            }

            $categoryId = $this->adCategoryId((string) $ad->placement);
            if ($categoryId !== null) {
                $ad->categories()->sync([$categoryId => ['is_primary' => true]]);
            }
        });
    }

    public function definition(): array
    {
        $hospital = Hospital::query()->inRandomOrder()->first()
            ?? Hospital::factory()->create();

        $event = HospitalEvent::query()
            ->where('hospital_id', $hospital->id)
            ->where('allow_status', HospitalEvent::ALLOW_APPROVED)
            ->where('admin_status', HospitalEvent::ADMIN_STATUS_NORMAL)
            ->inRandomOrder()
            ->first()
            ?? HospitalEvent::factory()->create([
                'hospital_id' => $hospital->id,
                'allow_status' => HospitalEvent::ALLOW_APPROVED,
                'admin_status' => HospitalEvent::ADMIN_STATUS_NORMAL,
            ]);

        $placement = $this->faker->randomElement(HospitalEventAd::placements());
        if (HospitalEventAd::requiresCategory($placement)) {
            if ($this->adCategoryId($placement) === null) {
                $placement = $this->faker->randomElement(array_values(array_diff(
                    HospitalEventAd::placements(),
                    HospitalEventAd::categoryRequiredPlacements(),
                )));
            }
        }

        $startAt = now()
            ->next(HospitalEventAd::startDayOfWeek($placement))
            ->setTime(11, 0);

        return [
            'hospital_id' => (int) $hospital->id,
            'hospital_event_id' => (int) $event->id,
            'manager_staff_id' => AccountStaff::query()->inRandomOrder()->value('id'),
            'placement' => $placement,
            'cost' => HospitalEventAd::placementCost($placement),
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addWeek()->subSecond(),
            'allow_status' => $this->faker->randomElement(HospitalEventAd::allowStatuses()),
        ];
    }

    private function adCategoryId(string $placement): ?int
    {
        $usage = HospitalEventAd::categoryUsageForPlacement($placement);
        if ($usage === null) {
            return null;
        }

        $categoryId = Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('status', Category::STATUS_ACTIVE)
            ->whereHas('usages', static fn ($query) => $query
                ->where('usage', $usage)
                ->where('status', CategoryUsage::STATUS_ACTIVE))
            ->inRandomOrder()
            ->value('id');

        return $categoryId !== null ? (int) $categoryId : null;
    }
}
