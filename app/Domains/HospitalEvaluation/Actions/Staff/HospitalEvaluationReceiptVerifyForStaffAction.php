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

final class HospitalEvaluationReceiptVerifyForStaffAction
{
    public function __construct(
        private readonly HospitalEvaluationReceiptUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(HospitalEvaluation $evaluation): array
    {
        Gate::authorize('update', $evaluation);

        $actor = auth()->user();

        return DB::transaction(function () use ($evaluation, $actor): array {
            $lockedEvaluation = $this->query->getForUpdate((int) $evaluation->getKey());
            $beforeStatus = (string) $lockedEvaluation->receipt_status;

            if ((int) $lockedEvaluation->receipt_images_count < 1) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '영수증 이미지가 없는 평가는 인증 처리할 수 없습니다.');
            }

            if ($beforeStatus === HospitalEvaluation::RECEIPT_STATUS_VERIFIED) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 인증 적합 처리된 영수증입니다.');
            }

            $this->query->markVerified($lockedEvaluation);

            $this->historyCreateAction->execute(
                target: $lockedEvaluation,
                action: OperationHistory::ACTION_STATE_UPDATED,
                actor: $actor instanceof Model ? $actor : null,
                metadata: [
                    'source' => 'staff.hospital-evaluation.receipt.verify',
                ],
                changes: OperationHistoryChangeSetBuilder::single(
                    key: 'receipt_status',
                    label: '영수증 상태',
                    before: $beforeStatus,
                    after: HospitalEvaluation::RECEIPT_STATUS_VERIFIED,
                    beforeDisplay: HospitalEvaluation::receiptStatusLabels()[$beforeStatus] ?? $beforeStatus,
                    afterDisplay: HospitalEvaluation::receiptStatusLabels()[HospitalEvaluation::RECEIPT_STATUS_VERIFIED],
                ),
            );

            return [
                'id' => (int) $lockedEvaluation->getKey(),
                'receipt' => [
                    'status' => HospitalEvaluation::RECEIPT_STATUS_VERIFIED,
                    'label' => HospitalEvaluation::receiptStatusLabels()[HospitalEvaluation::RECEIPT_STATUS_VERIFIED],
                ],
            ];
        });
    }
}
