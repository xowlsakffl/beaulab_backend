<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\HospitalEvent\Dto\Staff\HospitalEventForStaffDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventPeriodUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventUpdateForStaffQuery $query,
        private readonly HospitalEventUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(HospitalEvent $event, array $payload): array
    {
        Gate::authorize('update', $event);

        $event = DB::transaction(function () use ($event, $payload): HospitalEvent {
            $before = $this->historyRecordAction->capture($event);
            $isUnlimited = (bool) $payload['is_event_period_unlimited'];

            $event = $this->query->update($event, [
                'is_event_period_unlimited' => $isUnlimited,
                'event_start_at' => $payload['event_start_at'],
                'event_end_at' => $isUnlimited ? null : ($payload['event_end_at'] ?? null),
            ]);

            $event->refresh();
            $this->historyRecordAction->recordUpdated($event, $before);

            return $event;
        });

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_EVENT);

        return [
            'event' => HospitalEventForStaffDto::fromModel($event->load([
                'hospital',
                'managerStaff',
                'categories',
                'doctors',
                'thumbnailImage',
            ]))->toArray(),
        ];
    }
}
