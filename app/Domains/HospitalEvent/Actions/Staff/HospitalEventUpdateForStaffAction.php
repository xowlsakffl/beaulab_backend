<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEvent\Dto\Staff\HospitalEventForStaffDetailDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventUpdateForStaffQuery $query,
        private readonly HospitalEventPayloadResolver $payloadResolver,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly HospitalEventUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(HospitalEvent $event, array $payload): array
    {
        Gate::authorize('update', $event);

        $event = DB::transaction(function () use ($event, $payload): HospitalEvent {
            $beforeHistory = $this->historyRecordAction->capture($event);

            $data = $this->payloadResolver->normalizePersistPayload($payload, $event);

            $categorySync = null;
            if (array_key_exists('category_ids', $payload)) {
                $categorySync = $this->payloadResolver->resolveCategorySyncPayload(
                    $payload['category_ids'] ?? [],
                    (int) ($payload['primary_category_id'] ?? 0),
                );
            }

            $categoryUsage = $categorySync['usage'] ?? $this->resolveCurrentCategoryUsage($event);
            $eventType = (string) ($data['event_type'] ?? $event->event_type);
            $optionsPayloadExists = array_key_exists('options', $payload) || array_key_exists('has_options', $payload);
            $options = null;

            if ($categoryUsage === CategoryUsage::USAGE_HOSPITAL_EVENT_SURGERY) {
                $options = [];
                $data['has_options'] = false;
            } elseif ($optionsPayloadExists) {
                $options = $this->payloadResolver->resolveOptions($payload, $categoryUsage);
            }

            if ($options !== null) {
                $data['has_options'] = $options !== [];
            }

            $event = $this->query->update($event, $data);

            if ($categorySync !== null) {
                $event->categories()->sync($categorySync['payload']);
            }

            if (array_key_exists('doctor_assignments', $payload) || array_key_exists('doctor_ids', $payload)) {
                $this->payloadResolver->syncDoctorAssignments(
                    $event,
                    $this->payloadResolver->resolveDoctorAssignments($payload, (int) $event->hospital_id),
                );
            }

            if ($options !== null) {
                $this->payloadResolver->syncOptions($event, $options);
            }

            if ($eventType === HospitalEvent::TYPE_TEXT) {
                $this->mediaAttachAction->deleteCollectionMedia($event, HospitalEvent::COLLECTION_EVENT_PAGE_IMAGE);
            }

            if (isset($payload['thumbnail_image'])) {
                $this->mediaAttachAction->deleteCollectionMedia($event, HospitalEvent::COLLECTION_THUMBNAIL_IMAGE);
                $this->mediaAttachAction->attachOne($event, $payload['thumbnail_image'], HospitalEvent::COLLECTION_THUMBNAIL_IMAGE, 'hospital-event', 'thumbnail-image', true);
            }

            if (isset($payload['event_page_image'])) {
                $this->mediaAttachAction->deleteCollectionMedia($event, HospitalEvent::COLLECTION_EVENT_PAGE_IMAGE);
                $this->mediaAttachAction->attachOne($event, $payload['event_page_image'], HospitalEvent::COLLECTION_EVENT_PAGE_IMAGE, 'hospital-event', 'event-page-image', true);
            }

            $event->refresh();
            if ($event->event_type === HospitalEvent::TYPE_IMAGE && ! $event->eventPageImage()->exists()) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '이미지등록 이벤트는 이벤트 페이지 이미지를 등록해 주세요.');
            }

            $this->historyRecordAction->recordUpdated($event, $beforeHistory);

            return $event;
        });

        if ($this->shouldForgetSummary($payload)) {
            StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_EVENT);
        }

        return [
            'event' => HospitalEventForStaffDetailDto::fromModel($event->load([
                'hospital.businessRegistration',
                'categories',
                'doctors',
                'options',
                'thumbnailImage',
                'eventPageImage',
            ]))->toArray(),
        ];
    }

    private function resolveCurrentCategoryUsage(HospitalEvent $event): string
    {
        $primaryCategoryId = (int) ($event->categories()->wherePivot('is_primary', true)->value('categories.id') ?? 0);
        $categoryIds = $event->categories()->pluck('categories.id')->map(static fn ($id): int => (int) $id)->all();

        return $this->payloadResolver->resolveCategorySyncPayload($categoryIds, $primaryCategoryId)['usage'];
    }

    private function shouldForgetSummary(array $payload): bool
    {
        return array_key_exists('allow_status', $payload)
            || array_key_exists('admin_status', $payload)
            || array_key_exists('hospital_status', $payload)
            || array_key_exists('is_event_period_unlimited', $payload)
            || array_key_exists('event_start_at', $payload)
            || array_key_exists('event_end_at', $payload);
    }
}
