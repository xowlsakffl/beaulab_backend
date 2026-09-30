<?php

namespace App\Domains\RemoteConsultation\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RemoteConsultationStatusForStaffAction
{
    public function __construct(private readonly RemoteConsultationReadForStaffAction $read) {}

    public function execute(int $id, string $status): array
    {
        Gate::authorize('approve', new RemoteConsultation);
        DB::transaction(function () use ($id, $status) {
            $c = RemoteConsultation::whereKey($id)->lockForUpdate()->firstOrFail();
            if (! in_array($status, ['REVIEWING', 'REJECTED'], true)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '허용되지 않는 승인상태입니다.');
            }
            if ($c->approval_status === $status) {
                return;
            }
            if (! in_array($c->approval_status, ['PENDING', 'REVIEWING'], true)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신청 또는 검수 상태에서만 변경할 수 있습니다.');
            }
            $before = $c->approval_status;
            $c->update(['approval_status' => $status]);
            $labels = ['PENDING' => '신청', 'REVIEWING' => '검수', 'REJECTED' => '반려'];
            app(OperationHistoryCreateAction::class)->execute(
                target: $c, action: OperationHistory::ACTION_STATE_UPDATED, actor: auth()->user(),
                metadata: ['source' => 'staff.remote_consultation.status'],
                changes: OperationHistoryChangeSetBuilder::single(key: 'approval_status', label: '승인상태', before: $before, after: $status, beforeDisplay: $labels[$before], afterDisplay: $labels[$status]),
            );
        }, 3);

        return $this->read->show($id);
    }
}
