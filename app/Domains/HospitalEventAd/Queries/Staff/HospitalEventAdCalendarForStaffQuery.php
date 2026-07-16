<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Support\HospitalEventAdSalesDeadline;
use Illuminate\Support\Carbon;

final class HospitalEventAdCalendarForStaffQuery
{
    /**
     * @var array<string, array<int, array{id:int,name:string,display_name:string,code:string,full_path:string}>>
     */
    private array $categoriesByUsage = [];

    private const CATEGORY_DEFINITIONS = [
        CategoryUsage::USAGE_HOSPITAL_EVENT_AD_SURGERY => [
            ['label' => '눈', 'codes' => ['HS_EYE']],
            ['label' => '코', 'codes' => ['HS_NOSE']],
            ['label' => '지방흡입/이식', 'codes' => ['HS_BODY']],
            ['label' => '가슴', 'codes' => ['HS_BREAST']],
            ['label' => '거상', 'codes' => ['HS_LIFT']],
            ['label' => '안면윤곽/양악', 'codes' => ['HS_FACE_CONTOUR']],
            ['label' => '모발이식', 'codes' => ['HS_HAIR_TRANSPLANT']],
            ['label' => '기타', 'codes' => ['HM_PLASTIC_OTHER']],
        ],
        CategoryUsage::USAGE_HOSPITAL_EVENT_AD_TREATMENT => [
            ['label' => '리프팅', 'codes' => ['HT_LIFTING']],
            ['label' => '필러', 'codes' => ['HT_FILLER']],
            ['label' => '보톡스', 'codes' => ['HT_BOTOX']],
            ['label' => '지방분해주사', 'codes' => ['HM_PETIT_SKIN_BODY_CONTOUR_INJECTION']],
            ['label' => '피부', 'codes' => ['HM_PETIT_SKIN_CARE']],
            ['label' => '헤어', 'codes' => ['HM_PETIT_SKIN_HAIR']],
            ['label' => '치과', 'codes' => ['HM_PETIT_SKIN_DENTAL']],
            ['label' => '부인과', 'codes' => ['HM_PETIT_SKIN_GYNECOLOGY']],
            ['label' => '안과', 'codes' => ['HM_PETIT_SKIN_OPHTHALMOLOGY']],
            ['label' => '한방', 'codes' => ['HM_PETIT_SKIN_ORIENTAL']],
        ],
    ];

    public function __construct(
        private readonly HospitalEventAdSlotAvailabilityForStaffQuery $slotQuery,
        private readonly HospitalEventAdSalesDeadline $salesDeadline,
    ) {}

    public function get(string $group, ?int $categoryId, Carbon $month): array
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

        $categories = Category::query()
            ->select(['id', 'code', 'name', 'full_path'])
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('status', Category::STATUS_ACTIVE)
            ->whereHas('usages', static fn ($query) => $query
                ->where('usage', $usage)
                ->where('status', CategoryUsage::STATUS_ACTIVE))
            ->get()
            ->keyBy('code');

        return $this->categoriesByUsage[$usage] = collect(self::CATEGORY_DEFINITIONS[$usage] ?? [])
            ->map(function (array $definition) use ($categories): ?array {
                $category = collect($definition['codes'])
                    ->map(static fn (string $code) => $categories->get($code))
                    ->filter()
                    ->first();

                if (! $category instanceof Category) {
                    return null;
                }

                return [
                    'id' => (int) $category->id,
                    'name' => (string) $category->name,
                    'display_name' => (string) $definition['label'],
                    'code' => (string) ($category->code ?? ''),
                    'full_path' => (string) ($category->full_path ?? ''),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function placementSortOrder(string $placement): int
    {
        $index = array_search($placement, HospitalEventAd::placements(), true);

        return $index === false ? 999 : (int) $index;
    }
}
