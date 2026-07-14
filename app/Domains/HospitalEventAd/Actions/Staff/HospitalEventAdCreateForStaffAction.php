<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEventAd\Dto\Staff\HospitalEventAdForStaffDetailDto;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdCreateForStaffQuery;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdSlotAvailabilityForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdCreateForStaffAction
{
    public function __construct(
        private readonly HospitalEventAdCreateForStaffQuery $query,
        private readonly HospitalEventAdPeriodResolver $periodResolver,
        private readonly HospitalEventAdSlotAvailabilityForStaffQuery $slotQuery,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly HospitalEventAdUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', HospitalEventAd::class);

        $normalized = $this->normalizePayload($payload);

        $ad = DB::transaction(function () use ($normalized) {
            $this->assertSlotAvailable($normalized);

            $ad = $this->query->create($normalized);
            $this->syncCategory($ad, $normalized['category_id'] ?? null);

            if (! empty($normalized['ad_image_file'])) {
                $this->mediaAttachAction->attachOne(
                    $ad,
                    $normalized['ad_image_file'],
                    HospitalEventAd::COLLECTION_AD_IMAGE,
                    'hospital-event-ad',
                    'ad-image',
                );
            }

            $ad = $ad->fresh($this->detailRelations());

            $this->historyRecordAction->recordCreated($ad);

            return $ad;
        });

        return [
            'hospital_event_ad' => HospitalEventAdForStaffDetailDto::fromModel($ad)->toArray(),
        ];
    }

    private function normalizePayload(array $payload): array
    {
        $hospitalId = (int) $payload['hospital_id'];
        $eventId = (int) $payload['hospital_event_id'];
        $placement = (string) $payload['placement'];
        $categoryId = $payload['category_id'] ?? null;

        $this->assertEventBelongsToHospital($eventId, $hospitalId);

        if (HospitalEventAd::requiresCategory($placement)) {
            if (empty($categoryId)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '카테고리별 광고는 카테고리를 선택해야 합니다.');
            }

            $this->assertCategoryMatchesPlacement((int) $categoryId, $placement);
        } else {
            $categoryId = null;
        }

        $period = $this->periodResolver->resolve((string) $payload['start_date'], $placement);

        return [
            ...$payload,
            'category_id' => $categoryId !== null ? (int) $categoryId : null,
            'cost' => $this->resolveCost($placement, $payload),
            'start_at' => $period['start_at'],
            'end_at' => $period['end_at'],
        ];
    }

    private function resolveCost(string $placement, array $payload): int
    {
        return filter_var($payload['is_free_event'] ?? false, FILTER_VALIDATE_BOOL)
            ? 0
            : HospitalEventAd::placementCost($placement);
    }

    private function assertEventBelongsToHospital(int $eventId, int $hospitalId): void
    {
        $exists = HospitalEvent::query()
            ->whereKey($eventId)
            ->where('hospital_id', $hospitalId)
            ->exists();

        if (! $exists) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '요청하신 병의원에 소속된 이벤트가 아닙니다.');
        }
    }

    private function assertCategoryMatchesPlacement(int $categoryId, string $placement): void
    {
        $usage = HospitalEventAd::categoryUsageForPlacement($placement);
        if ($usage === null) {
            return;
        }

        $exists = Category::query()
            ->whereKey($categoryId)
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('status', Category::STATUS_ACTIVE)
            ->whereHas('usages', static fn ($query) => $query
                ->where('usage', $usage)
                ->where('status', CategoryUsage::STATUS_ACTIVE))
            ->exists();

        if (! $exists) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '광고 위치에 맞는 카테고리를 선택해야 합니다.');
        }
    }

    private function assertSlotAvailable(array $payload): void
    {
        $reservedCount = $this->slotQuery->reservedCount(
            (string) $payload['placement'],
            isset($payload['category_id']) ? (int) $payload['category_id'] : null,
            $payload['start_at'],
        );

        if ($reservedCount >= HospitalEventAd::WEEKLY_SLOT_LIMIT) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '선택한 광고 위치의 해당 주차 구좌가 마감되었습니다.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'hospital',
            'hospitalEvent',
            'categories',
            'managerStaff',
            'adImage',
        ];
    }

    private function syncCategory(HospitalEventAd $ad, ?int $categoryId): void
    {
        $ad->categories()->sync($categoryId !== null
            ? [$categoryId => ['is_primary' => true]]
            : []);
    }
}
