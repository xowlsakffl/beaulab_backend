<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Database\Eloquent\Model;

final class HospitalEntryUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function recordAllowStatusUpdated(
        HospitalEntry $entry,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $changes = OperationHistoryChangeSetBuilder::single(
            key: 'allow_status',
            label: '승인상태',
            before: $beforeStatus,
            after: $afterStatus,
            beforeDisplay: HospitalEntry::allowStatusLabel($beforeStatus),
            afterDisplay: HospitalEntry::allowStatusLabel($afterStatus),
        );

        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $entry,
            action: OperationHistory::ACTION_STATE_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: [
                'source' => 'staff.hospital_entry.allow_status',
                'bulk' => $bulk,
            ],
            changes: $changes,
        );
    }
}
