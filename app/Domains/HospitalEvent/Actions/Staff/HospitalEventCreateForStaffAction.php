<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEvent\Dto\Staff\HospitalEventForStaffDetailDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventCreateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventCreateForStaffAction
{
    public function __construct(
        private readonly HospitalEventCreateForStaffQuery $query,
        private readonly HospitalEventPayloadResolver $payloadResolver,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly HospitalEventBeforeAfterPhotosSyncAction $beforeAfterPhotosAction,
        private readonly HospitalEventUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(AccountStaff $actor, array $payload): array
    {
        Gate::authorize('create', HospitalEvent::class);
        if (array_intersect(['allow_status', 'hospital_status', 'admin_status'], array_keys($payload)) !== []) {
            Gate::authorize('updateStatus', HospitalEvent::class);
        }

        $event = DB::transaction(function () use ($actor, $payload): HospitalEvent {
            $data = $this->payloadResolver->normalizePersistPayload($payload);
            $data['manager_staff_id'] = $payload['manager_staff_id'] ?? $actor->getKey();
            $categorySync = $this->payloadResolver->resolveCategorySyncPayload(
                $payload['category_ids'] ?? [],
                (int) ($payload['primary_category_id'] ?? 0),
            );
            $doctorAssignments = $this->payloadResolver->resolveDoctorAssignments($payload, (int) $data['hospital_id']);
            $options = [];
            if ($categorySync['usage'] === CategoryUsage::USAGE_HOSPITAL_EVENT_TREATMENT) {
                $options = $this->payloadResolver->resolveOptions($payload, (string) $categorySync['usage']);
            }
            $data['has_options'] = $options !== [];

            $event = $this->query->create($data);

            $event->categories()->sync($categorySync['payload']);
            $this->payloadResolver->syncDoctorAssignments($event, $doctorAssignments);
            $this->payloadResolver->syncOptions($event, $options);
            $this->beforeAfterPhotosAction->execute($event, $payload);

            $this->mediaAttachAction->attachOne($event, $payload['thumbnail_image'] ?? null, HospitalEvent::COLLECTION_THUMBNAIL_IMAGE, 'hospital-event', 'thumbnail-image', true);

            if ($event->event_type === HospitalEvent::TYPE_IMAGE) {
                $this->mediaAttachAction->attachOne($event, $payload['event_page_image'] ?? null, HospitalEvent::COLLECTION_EVENT_PAGE_IMAGE, 'hospital-event', 'event-page-image', true);
            }

            $event = $event->fresh([
                'hospital',
                'categories',
                'doctors',
                'options',
                'thumbnailImage',
                'eventPageImage',
                'beforeAfterPhotos',
            ]);

            $this->historyRecordAction->recordCreated($event);

            return $event;
        });

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_EVENT);

        return [
            'event' => HospitalEventForStaffDetailDto::fromModel($event->load([
                'hospital.businessRegistration',
                'categories',
                'doctors',
                'options',
                'thumbnailImage',
                'eventPageImage',
                'beforeAfterPhotos',
            ]))->toArray(),
        ];
    }
}
