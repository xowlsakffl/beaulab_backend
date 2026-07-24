<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdSlotAvailabilityForStaffQuery;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdStateUpdateForStaffQuery;
use App\Domains\HospitalEventAd\Support\HospitalEventAdCalendarCache;
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

        $result = DB::transaction(function () use ($ids, $allowStatus, $payload): array {
            $ads = $this->query->getForUpdate($ids);
            $ads->each(static fn (HospitalEventAd $ad): mixed => Gate::authorize('update', $ad));
            $this->assertApprovalRequirements($ads, $allowStatus);
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

        HospitalEventAdCalendarCache::flush();

        return $result;
    }

    private function assertApprovalRequirements($ads, string $allowStatus): void
    {
        if ($allowStatus !== HospitalEventAd::ALLOW_APPROVED) {
            return;
        }

        foreach ($ads as $ad) {
            if (! $ad instanceof HospitalEventAd) {
                continue;
            }

            $hospital = $ad->hospital;
            if (
                ! $hospital instanceof Hospital
                || $hospital->allow_status !== Hospital::ALLOW_APPROVED
                || $hospital->status !== Hospital::STATUS_ACTIVE
            ) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '승인할 수 없는 병의원의 광고가 포함되어 있습니다.');
            }

            $event = $ad->hospitalEvent;
            if (
                ! $event instanceof HospitalEvent
                || $event->allow_status !== HospitalEvent::ALLOW_APPROVED
                || $event->admin_status !== HospitalEvent::ADMIN_STATUS_NORMAL
            ) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '광고에 연결할 수 없는 이벤트가 포함되어 있습니다.');
            }

            if ($ad->adImage === null) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '광고 이미지가 없는 광고는 승인할 수 없습니다.');
            }
        }
    }

    private function assertSlotsAvailableForAllowStatus($ads, string $allowStatus): void
    {
        if ($allowStatus === HospitalEventAd::ALLOW_REJECTED) {
            return;
        }

        if ($ads->isEmpty()) {
            return;
        }

        $ads
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

                $additionalCount = $group
                    ->filter(static fn (HospitalEventAd $ad): bool => (string) $ad->allow_status === HospitalEventAd::ALLOW_REJECTED)
                    ->count();
                $reservedCount = $this->slotQuery->reservedCount(
                    (string) $first->placement,
                    $this->categoryId($first),
                    $first->start_at,
                );

                if ($reservedCount + $additionalCount > HospitalEventAd::WEEKLY_SLOT_LIMIT) {
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
