<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventDBStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventDBAllowStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventDBStatusUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
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
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $allowStatus, $payload, $actor): array {
            $eventDBs = $this->query->getForUpdate($ids);
            $eventDBs->each(static function (HospitalEventDB $eventDB): void {
                if ($eventDB->event !== null) {
                    Gate::authorize('update', $eventDB->event);
                }
            });

            $existingIds = $eventDBs->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAllowStatus($existingIds, [
                'allow_status' => $allowStatus,
            ]);

            foreach ($eventDBs as $eventDB) {
                if ($eventDB->allow_status === $allowStatus) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $eventDB,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $payload['reason'] ?? null,
                    metadata: [
                        'source' => 'staff.hospital_event_db.allow_status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: OperationHistoryChangeSetBuilder::single(
                        key: 'allow_status',
                        label: '검증상태',
                        before: $eventDB->allow_status,
                        after: $allowStatus,
                        beforeDisplay: HospitalEventDB::allowStatusLabel($eventDB->allow_status),
                        afterDisplay: HospitalEventDB::allowStatusLabel($allowStatus),
                    ),
                );
            }

            return [
                'updated_count' => $updatedCount,
                'allow_status' => $allowStatus,
                'ids' => $existingIds,
            ];
        });
    }
}
