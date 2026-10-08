<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Actions\StaffNotificationPublisher;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventDBStatusUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventDBAllowStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventDBStatusUpdateForStaffQuery $query,
        private readonly HospitalEventDBUpdateHistoryRecordAction $historyRecordAction,
        private readonly StaffNotificationPublisher $notifications,
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
            $eventDBs = $this->query->getForUpdate($ids);
            $eventDBs->each(static fn (HospitalEventDB $eventDB): mixed => Gate::authorize('updateStatus', $eventDB));

            $existingIds = $eventDBs->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAllowStatus($existingIds, [
                'allow_status' => $allowStatus,
            ]);

            foreach ($eventDBs as $eventDB) {
                if ($eventDB->allow_status === $allowStatus) {
                    continue;
                }

                $history = $this->historyRecordAction->recordAllowStatusUpdated(
                    $eventDB,
                    (string) $eventDB->allow_status,
                    $allowStatus,
                    $payload['reason'] ?? null,
                    count($existingIds) > 1,
                );

                $this->notifications->invalidateTarget('hospital_event_db', (int) $eventDB->id);

                if ($allowStatus === HospitalEventDB::ALLOW_STATUS_UNVERIFIED_REPORTED && $history !== null) {
                    $actor = auth()->user();
                    $this->notifications->record(
                        eventType: 'staff.hospital_event_db.reported',
                        eventKey: 'hospital-event-db:reported:history:'.$history->id,
                        targetType: 'hospital_event_db',
                        targetId: (int) $eventDB->id,
                        payload: array_filter([
                            'hospital_id' => (int) $eventDB->hospital_id,
                            'hospital_name' => $eventDB->hospital?->name,
                            'target_name' => $eventDB->event?->name,
                        ], static fn (mixed $value): bool => $value !== null),
                        actorStaffId: $actor instanceof AccountStaff ? (int) $actor->id : null,
                    );
                }
            }

            return [
                'updated_count' => $updatedCount,
                'allow_status' => $allowStatus,
                'ids' => $existingIds,
            ];
        });
    }
}
