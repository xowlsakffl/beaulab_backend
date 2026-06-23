<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventDBStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventDBStatusUpdateForStaffAction
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
        $status = (string) $payload['status'];
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $payload, $actor): array {
            $eventDBs = $this->query->getForUpdate($ids);
            $eventDBs->each(static fn (HospitalEventDB $eventDB): mixed => Gate::authorize('update', $eventDB));

            if ($eventDBs->isEmpty()) {
                return [
                    'updated_count' => 0,
                    'status' => $status,
                    'ids' => [],
                ];
            }

            $existingIds = $eventDBs->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $values = $this->statusUpdateValues($status);
            $updatedCount = $this->query->updateStatus($existingIds, $values);

            foreach ($eventDBs as $eventDB) {
                $changes = OperationHistoryChangeSetBuilder::single(
                    key: 'status',
                    label: '상담여부',
                    before: $eventDB->status,
                    after: $status,
                    beforeDisplay: HospitalEventDB::statusLabel($eventDB->status),
                    afterDisplay: HospitalEventDB::statusLabel($status),
                );

                if ($changes === []) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $eventDB,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $payload['reason'] ?? null,
                    metadata: [
                        'source' => 'staff.hospital_event_db.status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: $changes,
                );
            }

            return [
                'updated_count' => $updatedCount,
                'status' => $status,
                'ids' => $existingIds,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function statusUpdateValues(string $status): array
    {
        $now = Carbon::now();

        return match ($status) {
            HospitalEventDB::STATUS_CONFIRMED => [
                'status' => $status,
                'contacted_at' => $now,
                'confirmed_at' => $now,
                'duplicated_at' => null,
            ],
            HospitalEventDB::STATUS_DUPLICATE => [
                'status' => $status,
                'contacted_at' => $now,
                'confirmed_at' => null,
                'duplicated_at' => $now,
            ],
            default => [
                'status' => HospitalEventDB::STATUS_NEW,
                'contacted_at' => null,
                'confirmed_at' => null,
                'duplicated_at' => null,
            ],
        };
    }
}
