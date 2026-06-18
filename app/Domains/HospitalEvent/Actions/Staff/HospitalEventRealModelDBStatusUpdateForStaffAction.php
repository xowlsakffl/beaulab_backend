<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventRealModelDBStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventRealModelDBStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventRealModelDBStatusUpdateForStaffQuery $query,
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
            $applications = $this->query->getForUpdate($ids);
            $applications->each(static function (HospitalEventRealModelDB $application): void {
                if ($application->event !== null) {
                    Gate::authorize('update', $application->event);
                }
            });

            $existingIds = $applications->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateStatus($existingIds, $status);

            foreach ($applications as $application) {
                $changes = OperationHistoryChangeSetBuilder::single(
                    key: 'status',
                    label: '승인여부',
                    before: $application->status,
                    after: $status,
                    beforeDisplay: HospitalEventRealModelDB::statusLabel($application->status),
                    afterDisplay: HospitalEventRealModelDB::statusLabel($status),
                );

                if ($changes === []) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $application,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $payload['reason'] ?? null,
                    metadata: [
                        'source' => 'staff.hospital_event_real_model_db.status',
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
}
