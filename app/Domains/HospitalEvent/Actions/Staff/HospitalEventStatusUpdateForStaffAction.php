<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventStatusUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventStatusUpdateForStaffQuery $query,
        private readonly HospitalEventUpdateHistoryRecordAction $historyRecordAction,
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

        return DB::transaction(function () use ($ids, $status, $payload): array {
            $events = $this->query->getForUpdate($ids);
            $events->each(static fn (HospitalEvent $event): mixed => Gate::authorize('update', $event));

            $existingIds = $events->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateStatus($existingIds, $status);

            foreach ($events as $event) {
                $beforeStatus = (string) $event->status;
                if ($beforeStatus === $status) {
                    continue;
                }

                $this->historyRecordAction->recordStatusUpdated(
                    $event,
                    $beforeStatus,
                    $status,
                    $payload['reason'] ?? null,
                    count($existingIds) > 1,
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
