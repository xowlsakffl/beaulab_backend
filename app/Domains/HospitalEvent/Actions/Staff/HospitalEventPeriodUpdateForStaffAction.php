<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalEvent\Dto\Staff\HospitalEventForStaffDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventPeriodUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(HospitalEvent $event, array $payload): array
    {
        Gate::authorize('update', $event);

        $event = DB::transaction(function () use ($event, $payload): HospitalEvent {
            $beforePeriod = $this->periodLabel($event);
            $isUnlimited = (bool) $payload['is_event_period_unlimited'];

            $event = $this->query->update($event, [
                'is_event_period_unlimited' => $isUnlimited,
                'event_start_at' => $payload['event_start_at'],
                'event_end_at' => $isUnlimited ? null : ($payload['event_end_at'] ?? null),
            ]);

            $event->refresh();
            $afterPeriod = $this->periodLabel($event);

            if ($beforePeriod !== $afterPeriod) {
                $actor = auth()->user();

                $this->historyCreateAction->execute(
                    target: $event,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    field: 'event_period',
                    beforeValue: $beforePeriod,
                    afterValue: $afterPeriod,
                    reason: null,
                    metadata: [
                        'source' => 'staff.hospital_event.period',
                    ],
                );
            }

            return $event;
        });

        return [
            'event' => HospitalEventForStaffDto::fromModel($event->load([
                'hospital.accountHospital',
                'categories',
                'doctors',
                'thumbnailImage',
            ]))->toArray(),
        ];
    }

    private function periodLabel(HospitalEvent $event): string
    {
        $startAt = $event->event_start_at?->toDateString() ?? '';
        if ((bool) $event->is_event_period_unlimited) {
            return "{$startAt} ~ 무기한";
        }

        return "{$startAt} ~ {$event->event_end_at?->toDateString()}";
    }
}
