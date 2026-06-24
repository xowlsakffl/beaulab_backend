<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalAllowStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalAllowStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalAllowStatusUpdateForStaffQuery $query,
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
            $hospitals = $this->query->getForUpdate($ids);
            $hospitals->each(static fn (Hospital $hospital): mixed => Gate::authorize('update', $hospital));

            $existingIds = $hospitals->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAllowStatus($existingIds, $allowStatus);

            foreach ($hospitals as $hospital) {
                $beforeStatus = (string) $hospital->allow_status;
                if ($beforeStatus === $allowStatus) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $hospital,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $payload['reason'] ?? null,
                    metadata: [
                        'source' => 'staff.hospital.allow_status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: OperationHistoryChangeSetBuilder::single(
                        key: 'allow_status',
                        label: '검수상태',
                        before: $beforeStatus,
                        after: $allowStatus,
                        beforeDisplay: Hospital::allowStatusLabel($beforeStatus),
                        afterDisplay: Hospital::allowStatusLabel($allowStatus),
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
