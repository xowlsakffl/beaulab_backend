<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Support\HospitalEventAdCalendarCache;
use App\Domains\HospitalEventAd\Support\HospitalEventAdSalesDeadline;
use Illuminate\Support\Carbon;

final class HospitalEventAdCalendarForStaffQuery
{
    /**
     * @var array<string, array<int, array{id:int,name:string,display_name:string,code:string,full_path:string}>>
     */
    private array $categoriesByUsage = [];

    public function __construct(
        private readonly HospitalEventAdSlotAvailabilityForStaffQuery $slotQuery,
        private readonly HospitalEventAdSalesDeadline $salesDeadline,
    ) {}

    public function get(string $group, ?int $categoryId, Carbon $month): array
    {
        return HospitalEventAdCalendarCache::rememberCalendar(
            $group,
            $categoryId,
            $month,
            fn (): array => $this->uncachedCalendar($group, $categoryId, $month),
        );
    }

    private function uncachedCalendar(string $group, ?int $categoryId, Carbon $month): array
    {
        $days = [];

        foreach ($this->placements($group, $categoryId) as $placement) {
            foreach ($this->placementWeeks($placement, $categoryId, $month) as $week) {
                $date = (string) $week['date'];
                $day = $days[$date] ?? [
                    'date' => $date,
                    'is_sales_closed' => true,
                    'statuses' => [],
                ];

                $day['statuses'][] = $week;
                $day['is_sales_closed'] = (bool) $day['is_sales_closed']
                    && ((bool) $week['is_past'] || (bool) $week['is_deadline_closed']);
                $days[$date] = $day;
            }
        }

        ksort($days);

        return [
            'group' => $group,
            'category_id' => $categoryId,
            'month' => $month->format('Y-m'),
            'categories' => $this->categories($group),
            'category_groups' => [
                HospitalEventAd::GROUP_SURGERY => $this->categories(HospitalEventAd::GROUP_SURGERY),
                HospitalEventAd::GROUP_PETIT => $this->categories(HospitalEventAd::GROUP_PETIT),
            ],
            'days' => array_values(array_map(function (array $day): array {
                usort(
                    $day['statuses'],
                    static fn (array $a, array $b): int => (int) $a['sort_order'] <=> (int) $b['sort_order'],
                );

                return $day;
            }, $days)),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function placements(string $group, ?int $categoryId): array
    {
        $placements = HospitalEventAd::placementsForGroup($group);
        if ($categoryId === null) {
            return $placements;
        }

        return collect($placements)
            ->filter(static fn (string $placement): bool => HospitalEventAd::requiresCategory($placement))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function placementWeeks(string $placement, ?int $categoryId, Carbon $month): array
    {
        $weeks = [];
        $cursor = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $startDayOfWeek = HospitalEventAd::startDayOfWeek($placement);

        while ($cursor->lte($endOfMonth)) {
            if ($cursor->dayOfWeek === $startDayOfWeek) {
                $weeks[] = $this->weekStatus($placement, $categoryId, $cursor);
            }

            $cursor->addDay();
        }

        return $weeks;
    }

    private function weekStatus(string $placement, ?int $categoryId, Carbon $date): array
    {
        $startAt = $date->copy()->setTime(11, 0, 0);
        $ads = $this->slotQuery->reservedAds(
            $placement,
            HospitalEventAd::requiresCategory($placement) ? $categoryId : null,
            $startAt,
            allowStatuses: [HospitalEventAd::ALLOW_APPROVED],
        );
        $reservedCount = $ads->count();
        $slotLimit = $this->slotLimit($placement, $categoryId);
        $remainingCount = max(0, $slotLimit - $reservedCount);
        $isPast = $date->copy()->startOfDay()->lessThanOrEqualTo(now()->startOfDay());
        $isDeadlineClosed = $this->salesDeadline->isClosed($startAt);

        return [
            'date' => $date->toDateString(),
            'placement' => $placement,
            'placement_label' => HospitalEventAd::placementLabel($placement),
            'reserved_count' => $reservedCount,
            'remaining_count' => $remainingCount,
            'slot_limit' => $slotLimit,
            'is_sold_out' => $isPast || $isDeadlineClosed || $remainingCount <= 0,
            'is_past' => $isPast,
            'is_deadline_closed' => $isDeadlineClosed,
            'sort_order' => $this->placementSortOrder($placement),
            'ads' => $ads
                ->map(fn (HospitalEventAd $ad): array => $this->adSummary($ad))
                ->values()
                ->all(),
        ];
    }

    private function adSummary(HospitalEventAd $ad): array
    {
        $adStatus = $ad->adStatus();
        $category = $ad->relationLoaded('categories') ? $ad->categories->first() : null;

        return [
            'id' => (int) $ad->id,
            'hospital_name' => (string) ($ad->hospital?->name ?? '-'),
            'event_name' => (string) ($ad->hospitalEvent?->name ?? '-'),
            'category_name' => $category ? (string) $category->name : null,
            'allow_status' => (string) $ad->allow_status,
            'allow_status_label' => HospitalEventAd::allowStatusLabel((string) $ad->allow_status),
            'ad_status' => $adStatus,
            'ad_status_label' => HospitalEventAd::adStatusLabel($adStatus),
            'manager_name' => (string) ($ad->managerStaff?->name ?? '-'),
        ];
    }

    /**
     * @return array<int, array{id:int,name:string,display_name:string,code:string,full_path:string}>
     */
    private function categories(string $group): array
    {
        $usage = HospitalEventAd::categoryUsageForGroup($group);
        if ($usage === null) {
            return [];
        }

        return $this->categoriesByUsage($usage);
    }

    private function slotLimit(string $placement, ?int $categoryId): int
    {
        if (! HospitalEventAd::requiresCategory($placement) || $categoryId !== null) {
            return HospitalEventAd::WEEKLY_SLOT_LIMIT;
        }

        $usage = HospitalEventAd::categoryUsageForPlacement($placement);
        if ($usage === null) {
            return HospitalEventAd::WEEKLY_SLOT_LIMIT;
        }

        return max(1, count($this->categoriesByUsage($usage))) * HospitalEventAd::WEEKLY_SLOT_LIMIT;
    }

    /**
     * @return array<int, array{id:int,name:string,display_name:string,code:string,full_path:string}>
     */
    private function categoriesByUsage(string $usage): array
    {
        if (array_key_exists($usage, $this->categoriesByUsage)) {
            return $this->categoriesByUsage[$usage];
        }

        return $this->categoriesByUsage[$usage] = Category::query()
            ->select([
                'categories.id',
                'categories.code',
                'categories.name',
                'categories.full_path',
            ])
            ->join('category_usages', 'category_usages.category_id', '=', 'categories.id')
            ->where('category_usages.usage', $usage)
            ->where('category_usages.status', CategoryUsage::STATUS_ACTIVE)
            ->where('categories.domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('categories.status', Category::STATUS_ACTIVE)
            ->orderBy('category_usages.sort_order')
            ->orderBy('categories.id')
            ->get()
            ->map(
                static function (Category $category): array {
                    $name = (string) $category->name;

                    return [
                        'id' => (int) $category->id,
                        'name' => $name,
                        'display_name' => $name,
                        'code' => (string) ($category->code ?? ''),
                        'full_path' => (string) ($category->full_path ?? ''),
                    ];
                }
            )
            ->values()
            ->all();
    }

    private function placementSortOrder(string $placement): int
    {
        $index = array_search($placement, HospitalEventAd::placements(), true);

        return $index === false ? 999 : (int) $index;
    }
}
