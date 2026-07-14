<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdSlotAvailabilityForStaffQuery;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdStateUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdAllowStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventAdStateUpdateForStaffQuery $query,
        private readonly HospitalEventAdSlotAvailabilityForStaffQuery $slotQuery,
        private readonly HospitalEventAdUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(array $payload): array
    {
        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $allowStatus = (string) $payload['allow_status'];

        return DB::transaction(function () use ($ids, $allowStatus, $payload): array {
            $ads = $this->query->getForUpdate($ids);
            $ads->each(static fn (HospitalEventAd $ad): mixed => Gate::authorize('update', $ad));
            $this->assertSlotsAvailableForAllowStatus($ads, $allowStatus);

            $existingIds = $ads->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAllowStatus($existingIds, $allowStatus);

            foreach ($ads as $ad) {
                $beforeStatus = (string) $ad->allow_status;
                if ($beforeStatus === $allowStatus) {
                    continue;
                }

                $this->historyRecordAction->recordAllowStatusUpdated(
                    $ad,
                    $beforeStatus,
                    $allowStatus,
                    $payload['reason'] ?? null,
                    count($existingIds) > 1,
                );
            }

            return [
                'updated_count' => $updatedCount,
                'allow_status' => $allowStatus,
                'ids' => $existingIds,
            ];
        });
    }

    private function assertSlotsAvailableForAllowStatus($ads, string $allowStatus): void
    {
        if ($allowStatus === HospitalEventAd::ALLOW_REJECTED) {
            return;
        }

        $transitioningAds = $ads
            ->filter(static fn (HospitalEventAd $ad): bool => (string) $ad->allow_status === HospitalEventAd::ALLOW_REJECTED)
            ->values();

        if ($transitioningAds->isEmpty()) {
            return;
        }

        $transitioningAds
            ->groupBy(function (HospitalEventAd $ad): string {
                $categoryId = $this->categoryId($ad);

                return implode('|', [
                    (string) $ad->placement,
                    $categoryId !== null ? (string) $categoryId : 'null',
                    $ad->start_at?->toISOString() ?? '',
                ]);
            })
            ->each(function ($group): void {
                $first = $group->first();
                if (! $first instanceof HospitalEventAd || $first->start_at === null) {
                    return;
                }

                $reservedCount = $this->slotQuery->reservedCount(
                    (string) $first->placement,
                    $this->categoryId($first),
                    $first->start_at,
                );

                if ($reservedCount + $group->count() > HospitalEventAd::WEEKLY_SLOT_LIMIT) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '선택한 광고 위치의 해당 주차 구좌가 마감되었습니다.');
                }
            });
    }

    private function categoryId(HospitalEventAd $ad): ?int
    {
        if (! $ad->relationLoaded('categories')) {
            return null;
        }

        $category = $ad->categories
            ->sortByDesc(static fn ($category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->first();

        return $category ? (int) $category->id : null;
    }
}
