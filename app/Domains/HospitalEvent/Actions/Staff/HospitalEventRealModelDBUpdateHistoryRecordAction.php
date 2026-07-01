<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Database\Eloquent\Model;

final class HospitalEventRealModelDBUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function recordStatusUpdated(
        HospitalEventRealModelDB $application,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $changes = OperationHistoryChangeSetBuilder::single(
            key: 'status',
            label: '승인여부',
            before: $beforeStatus,
            after: $afterStatus,
            beforeDisplay: HospitalEventRealModelDB::statusLabel($beforeStatus),
            afterDisplay: HospitalEventRealModelDB::statusLabel($afterStatus),
        );

        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $application,
            action: OperationHistory::ACTION_STATE_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: [
                'source' => 'staff.hospital_event_real_model_db.status',
                'bulk' => $bulk,
            ],
            changes: $changes,
        );
    }
}
