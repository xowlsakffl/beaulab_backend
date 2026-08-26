<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Actions\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Domains\HospitalEntry\Queries\Staff\HospitalEntryAllowStatusUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEntryAllowStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEntryAllowStatusUpdateForStaffQuery $query,
        private readonly HospitalEntryUpdateHistoryRecordAction $historyRecordAction,
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

        $result = DB::transaction(function () use ($ids, $allowStatus, $payload): array {
            $entries = $this->query->getForUpdate($ids);
            $entries->each(static fn (HospitalEntry $entry): mixed => Gate::authorize('updateStatus', $entry));

            $existingIds = $entries->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAllowStatus($existingIds, $allowStatus);

            foreach ($entries as $entry) {
                $beforeStatus = (string) $entry->allow_status;
                if ($beforeStatus === $allowStatus) {
                    continue;
                }

                $this->historyRecordAction->recordAllowStatusUpdated(
                    $entry,
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

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_ENTRY);

        return $result;
    }
}
