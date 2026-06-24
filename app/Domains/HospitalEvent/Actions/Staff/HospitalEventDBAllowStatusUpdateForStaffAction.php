<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventDBStatusUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventDBAllowStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventDBStatusUpdateForStaffQuery $query,
        private readonly HospitalEventDBUpdateHistoryRecordAction $historyRecordAction,
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
            $eventDBs->each(static fn (HospitalEventDB $eventDB): mixed => Gate::authorize('update', $eventDB));

            $existingIds = $eventDBs->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAllowStatus($existingIds, [
                'allow_status' => $allowStatus,
            ]);

            foreach ($eventDBs as $eventDB) {
                if ($eventDB->allow_status === $allowStatus) {
                    continue;
                }

                $this->historyRecordAction->recordAllowStatusUpdated(
                    $eventDB,
                    (string) $eventDB->allow_status,
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
    }
}
