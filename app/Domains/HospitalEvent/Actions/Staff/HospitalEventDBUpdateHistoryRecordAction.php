<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Database\Eloquent\Model;

final class HospitalEventDBUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function recordStatusUpdated(
        HospitalEventDB $eventDB,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $this->record(
            eventDB: $eventDB,
            source: 'staff.hospital_event_db.status',
            changes: OperationHistoryChangeSetBuilder::single(
                key: 'status',
                label: '상담여부',
                before: $beforeStatus,
                after: $afterStatus,
                beforeDisplay: HospitalEventDB::statusLabel($beforeStatus),
                afterDisplay: HospitalEventDB::statusLabel($afterStatus),
            ),
            reason: $reason,
            bulk: $bulk,
        );
    }

    public function recordAllowStatusUpdated(
        HospitalEventDB $eventDB,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $this->record(
            eventDB: $eventDB,
            source: 'staff.hospital_event_db.allow_status',
            changes: OperationHistoryChangeSetBuilder::single(
                key: 'allow_status',
                label: '검증상태',
                before: $beforeStatus,
                after: $afterStatus,
                beforeDisplay: HospitalEventDB::allowStatusLabel($beforeStatus),
                afterDisplay: HospitalEventDB::allowStatusLabel($afterStatus),
            ),
            reason: $reason,
            bulk: $bulk,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function record(
        HospitalEventDB $eventDB,
        string $source,
        array $changes,
        ?string $reason,
        bool $bulk,
    ): void {
        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $eventDB,
            action: OperationHistory::ACTION_STATE_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: [
                'source' => $source,
                'bulk' => $bulk,
            ],
            changes: $changes,
        );
    }
}
