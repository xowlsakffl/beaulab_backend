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
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdSlotAvailabilityForStaffQuery;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventAdUpdateForStaffQuery $query,
        private readonly HospitalEventAdPeriodResolver $periodResolver,
        private readonly HospitalEventAdSlotAvailabilityForStaffQuery $slotQuery,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly HospitalEventAdUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(HospitalEventAd $ad, array $payload): array
    {
        Gate::authorize('update', $ad);

        $ad = DB::transaction(function () use ($ad, $payload) {
            $locked = HospitalEventAd::query()
                ->with(['categories:id,code,name,full_path,depth'])
                ->whereKey($ad->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $this->historyRecordAction->capture($locked);
            $normalized = $this->normalizePayload($locked, $payload);

            if ($this->isSlotChanged($locked, $normalized)) {
                $this->assertSlotAvailable($locked, $normalized);
            }

            $updated = $this->query->update($locked, $normalized);
            $this->syncCategory($updated, $normalized['category_id'] ?? null);

            if (array_key_exists('ad_image_file', $normalized) && $normalized['ad_image_file'] instanceof UploadedFile) {
                $this->mediaAttachAction->deleteCollectionMedia($updated, HospitalEventAd::COLLECTION_AD_IMAGE);
                $this->mediaAttachAction->attachOne(
                    $updated,
                    $normalized['ad_image_file'],
                    HospitalEventAd::COLLECTION_AD_IMAGE,
                    'hospital-event-ad',
                    'ad-image',
                );
            } elseif (array_key_exists('existing_ad_image_id', $normalized) && empty($normalized['existing_ad_image_id'])) {
                $this->mediaAttachAction->deleteCollectionMedia($updated, HospitalEventAd::COLLECTION_AD_IMAGE);
            }

            $updated = $updated->fresh($this->detailRelations());

            $this->historyRecordAction->recordUpdated($updated, $before);

            return $updated;
        });

        return [
            'hospital_event_ad' => HospitalEventAdForStaffDetailDto::fromModel($ad)->toArray(),
        ];
    }

    private function normalizePayload(HospitalEventAd $ad, array $payload): array
    {
        $hospitalId = array_key_exists('hospital_id', $payload) ? (int) $payload['hospital_id'] : (int) $ad->hospital_id;
        $eventId = array_key_exists('hospital_event_id', $payload) ? (int) $payload['hospital_event_id'] : (int) $ad->hospital_event_id;
        $placement = array_key_exists('placement', $payload) ? (string) $payload['placement'] : (string) $ad->placement;
        $categoryId = array_key_exists('category_id', $payload) ? $payload['category_id'] : $this->categoryId($ad);

        $this->assertEventBelongsToHospital($eventId, $hospitalId);

        if (HospitalEventAd::requiresCategory($placement)) {
            if (empty($categoryId)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '카테고리별 광고는 카테고리를 선택해야 합니다.');
            }

            $this->assertCategoryMatchesPlacement((int) $categoryId, $placement);
        } else {
            $categoryId = null;
        }

        $normalized = [
            ...$payload,
            'hospital_id' => $hospitalId,
            'hospital_event_id' => $eventId,
            'placement' => $placement,
            'category_id' => $categoryId !== null ? (int) $categoryId : null,
            'cost' => $this->resolveCost($ad, $placement, $payload),
        ];

        if (array_key_exists('start_date', $payload)) {
            $period = $this->periodResolver->resolve((string) $payload['start_date'], $placement);
            $normalized['start_at'] = $period['start_at'];
            $normalized['end_at'] = $period['end_at'];
        } else {
            $normalized['start_at'] = $ad->start_at;
            $normalized['end_at'] = $ad->end_at;
        }

        return $normalized;
    }

    private function resolveCost(HospitalEventAd $ad, string $placement, array $payload): int
    {
        if (array_key_exists('is_free_event', $payload)) {
            return filter_var($payload['is_free_event'], FILTER_VALIDATE_BOOL)
                ? 0
                : HospitalEventAd::placementCost($placement);
        }

        if ((string) $ad->placement !== $placement) {
            return HospitalEventAd::placementCost($placement);
        }

        return (int) $ad->cost;
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

    private function assertSlotAvailable(HospitalEventAd $ad, array $payload): void
    {
        $reservedCount = $this->slotQuery->reservedCount(
            (string) $payload['placement'],
            isset($payload['category_id']) ? (int) $payload['category_id'] : null,
            $payload['start_at'],
            (int) $ad->id,
        );

        if ($reservedCount >= HospitalEventAd::WEEKLY_SLOT_LIMIT) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '선택한 광고 위치의 해당 주차 구좌가 마감되었습니다.');
        }
    }

    private function isSlotChanged(HospitalEventAd $ad, array $payload): bool
    {
        $payloadCategoryId = isset($payload['category_id']) ? (int) $payload['category_id'] : null;

        return (string) $ad->placement !== (string) $payload['placement']
            || $this->categoryId($ad) !== $payloadCategoryId
            || ! $ad->start_at?->equalTo($payload['start_at']);
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
