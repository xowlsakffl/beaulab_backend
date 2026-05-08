<?php

namespace App\Domains\HospitalEvaluation\Queries\Staff;

use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;

final class HospitalEvaluationReceiptUpdateForStaffQuery
{
    public function getForUpdate(int $evaluationId): HospitalEvaluation
    {
        return HospitalEvaluation::query()
            ->select([
                'id',
                'receipt_status',
                'receipt_rejection_reason',
                'receipt_rejection_reason_text',
            ])
            ->withCount('receiptImages')
            ->whereKey($evaluationId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function markVerified(HospitalEvaluation $evaluation): bool
    {
        return $evaluation->forceFill([
            'receipt_status' => HospitalEvaluation::RECEIPT_STATUS_VERIFIED,
            'receipt_rejection_reason' => null,
            'receipt_rejection_reason_text' => null,
        ])->save();
    }

    public function markRejected(HospitalEvaluation $evaluation, string $reason, ?string $reasonText): bool
    {
        return $evaluation->forceFill([
            'receipt_status' => HospitalEvaluation::RECEIPT_STATUS_REJECTED,
            'receipt_rejection_reason' => $reason,
            'receipt_rejection_reason_text' => $reasonText,
        ])->save();
    }
}
