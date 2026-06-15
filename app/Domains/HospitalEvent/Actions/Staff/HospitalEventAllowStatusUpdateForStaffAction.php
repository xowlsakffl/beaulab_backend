<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
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
            $events->each(static fn (HospitalEvent $event): mixed => Gate::authorize('update', $event));

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
                    reason: $payload['reason'] ?? null,
                    metadata: [
                        'source' => 'staff.hospital_event.allow_status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: OperationHistoryChangeSetBuilder::single(
                        key: 'allow_status',
                        label: '검수상태',
                        before: $beforeStatus,
                        after: $allowStatus,
                        beforeDisplay: $this->allowStatusLabel($beforeStatus),
                        afterDisplay: $this->allowStatusLabel($allowStatus),
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

    private function allowStatusLabel(string $status): string
    {
        return match ($status) {
            HospitalEvent::ALLOW_PENDING => '검수신청중',
            HospitalEvent::ALLOW_REVIEWING => '검토중',
            HospitalEvent::ALLOW_APPROVED => '검수완료',
            HospitalEvent::ALLOW_REJECTED => '검수반려',
            HospitalEvent::ALLOW_PARTNER_CANCELED => '파트너취소',
            default => $status,
        };
    }
}
