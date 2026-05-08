<?php

namespace App\Domains\HospitalEvaluation\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
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

            if ($beforeStatus !== HospitalEvaluation::RECEIPT_STATUS_VERIFIED) {
                $this->query->markVerified($lockedEvaluation);

                $this->historyCreateAction->execute(
                    target: $lockedEvaluation,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    field: 'receipt_status',
                    beforeValue: $beforeStatus,
                    afterValue: HospitalEvaluation::RECEIPT_STATUS_VERIFIED,
                    metadata: [
                        'before_label' => HospitalEvaluation::receiptStatusLabels()[$beforeStatus] ?? $beforeStatus,
                        'after_label' => HospitalEvaluation::receiptStatusLabels()[HospitalEvaluation::RECEIPT_STATUS_VERIFIED],
                        'source' => 'staff.hospital-evaluation.receipt.verify',
                    ],
                );
            }

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
