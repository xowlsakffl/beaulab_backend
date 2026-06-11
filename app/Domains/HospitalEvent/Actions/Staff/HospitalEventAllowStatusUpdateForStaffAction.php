<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAllowStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventStatusUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('update', HospitalEvent::class);

        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $allowStatus = (string) $payload['allow_status'];
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $allowStatus, $payload, $actor): array {
            $events = $this->query->getForUpdate($ids);
            $existingIds = $events->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAllowStatus($existingIds, $allowStatus);

            foreach ($events as $event) {
                $beforeStatus = (string) $event->allow_status;
                if ($beforeStatus === $allowStatus) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $event,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    field: 'allow_status',
                    beforeValue: $beforeStatus,
                    afterValue: $allowStatus,
                    reason: $payload['reason'] ?? null,
                    metadata: [
                        'source' => 'staff.hospital_event.allow_status',
                        'bulk' => count($existingIds) > 1,
                    ],
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
