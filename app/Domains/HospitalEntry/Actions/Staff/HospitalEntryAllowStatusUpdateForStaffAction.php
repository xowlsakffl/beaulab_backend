<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Domains\HospitalEntry\Queries\Staff\HospitalEntryAllowStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEntryAllowStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEntryAllowStatusUpdateForStaffQuery $query,
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
            $entries = $this->query->getForUpdate($ids);
            $entries->each(static fn (HospitalEntry $entry): mixed => Gate::authorize('update', $entry));

            $existingIds = $entries->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAllowStatus($existingIds, $allowStatus);

            foreach ($entries as $entry) {
                $beforeStatus = (string) $entry->allow_status;
                if ($beforeStatus === $allowStatus) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $entry,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $payload['reason'] ?? null,
                    metadata: [
                        'source' => 'staff.hospital_entry.allow_status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: OperationHistoryChangeSetBuilder::single(
                        key: 'allow_status',
                        label: '승인상태',
                        before: $beforeStatus,
                        after: $allowStatus,
                        beforeDisplay: HospitalEntry::allowStatusLabel($beforeStatus),
                        afterDisplay: HospitalEntry::allowStatusLabel($allowStatus),
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
