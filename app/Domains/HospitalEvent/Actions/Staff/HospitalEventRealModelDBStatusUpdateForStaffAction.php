<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventRealModelDBStatusUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventRealModelDBStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventRealModelDBStatusUpdateForStaffQuery $query,
        private readonly HospitalEventRealModelDBUpdateHistoryRecordAction $historyRecordAction,
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
            $applications = $this->query->getForUpdate($ids);
            $applications->each(static fn (HospitalEventRealModelDB $application): mixed => Gate::authorize('update', $application));

            $existingIds = $applications->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateStatus($existingIds, $status);

            foreach ($applications as $application) {
                $this->historyRecordAction->recordStatusUpdated(
                    $application,
                    (string) $application->status,
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
