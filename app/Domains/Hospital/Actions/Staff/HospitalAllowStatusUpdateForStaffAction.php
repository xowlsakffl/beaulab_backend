<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalAllowStatusUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalAllowStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalAllowStatusUpdateForStaffQuery $query,
        private readonly HospitalUpdateHistoryRecordAction $historyRecordAction,
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
            $hospitals = $this->query->getForUpdate($ids);
            $hospitals->each(static fn (Hospital $hospital): mixed => Gate::authorize('update', $hospital));

            $existingIds = $hospitals->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAllowStatus($existingIds, $allowStatus);

            foreach ($hospitals as $hospital) {
                $beforeStatus = (string) $hospital->allow_status;
                if ($beforeStatus === $allowStatus) {
                    continue;
                }

                $this->historyRecordAction->recordAllowStatusUpdated(
                    $hospital,
                    $beforeStatus,
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
