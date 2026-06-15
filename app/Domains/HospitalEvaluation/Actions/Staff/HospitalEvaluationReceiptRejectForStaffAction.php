<?php

namespace App\Domains\HospitalEvaluation\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvaluation\Queries\Staff\HospitalEvaluationReceiptUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEvaluationReceiptRejectForStaffAction
{
    public function __construct(
        private readonly HospitalEvaluationReceiptUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(HospitalEvaluation $evaluation, array $payload): array
    {
        Gate::authorize('update', $evaluation);

        $reason = (string) $payload['reason'];
        $reasonText = $reason === HospitalEvaluation::RECEIPT_REJECTION_REASON_OTHER
            ? $this->normalizeReasonText($payload['reason_text'] ?? null)
            : null;
        $actor = auth()->user();

        return DB::transaction(function () use ($evaluation, $reason, $reasonText, $actor): array {
            $lockedEvaluation = $this->query->getForUpdate((int) $evaluation->getKey());
            $beforeStatus = (string) $lockedEvaluation->receipt_status;

            if ((int) $lockedEvaluation->receipt_images_count < 1) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '영수증 이미지가 없는 평가는 부적합 처리할 수 없습니다.');
            }

            $this->query->markRejected($lockedEvaluation, $reason, $reasonText);

            $reasonLabel = HospitalEvaluation::receiptRejectionReasonLabels()[$reason] ?? $reason;

            $this->historyCreateAction->execute(
                target: $lockedEvaluation,
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $actor instanceof Model ? $actor : null,
                reason: $reasonText ?? $reasonLabel,
                metadata: [
                    'rejection_reason' => $reason,
                    'rejection_reason_label' => $reasonLabel,
                    'source' => 'staff.hospital-evaluation.receipt.reject',
                ],
                changes: OperationHistoryChangeSetBuilder::single(
                    key: 'receipt_status',
                    label: '영수증 인증',
                    before: $beforeStatus,
                    after: HospitalEvaluation::RECEIPT_STATUS_REJECTED,
                    beforeDisplay: HospitalEvaluation::receiptStatusLabels()[$beforeStatus] ?? $beforeStatus,
                    afterDisplay: HospitalEvaluation::receiptStatusLabels()[HospitalEvaluation::RECEIPT_STATUS_REJECTED],
                ),
            );

            return [
                'id' => (int) $lockedEvaluation->getKey(),
                'receipt' => [
                    'status' => HospitalEvaluation::RECEIPT_STATUS_REJECTED,
                    'label' => HospitalEvaluation::receiptStatusLabels()[HospitalEvaluation::RECEIPT_STATUS_REJECTED],
                    'rejection_reason' => $reason,
                    'rejection_reason_label' => $reasonLabel,
                    'rejection_reason_text' => $reasonText,
                ],
            ];
        });
    }

    private function normalizeReasonText(mixed $reasonText): ?string
    {
        $reasonText = trim((string) $reasonText);

        return $reasonText === '' ? null : $reasonText;
    }
}
