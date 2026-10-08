<?php

namespace App\Domains\RemoteConsultation\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\ConsultationPricing\Support\ConsultationPricingResolver;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalWallet\Actions\HospitalWalletUsageCompleteAction;
use App\Domains\HospitalWallet\Data\HospitalWalletUsageData;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use App\Domains\RemoteConsultation\Models\RemoteConsultationQuote;
use Illuminate\Support\Facades\DB;

final class RemoteConsultationAnswerForHospitalAction
{
    public function execute(AccountHospital $actor, int $quoteId, array $input): array
    {
        abort_unless($actor->isActive(), 403);

        return DB::transaction(function () use ($actor, $quoteId, $input) {
            $reference = RemoteConsultationQuote::whereKey($quoteId)->where('hospital_id', $actor->hospital_id)->firstOrFail();
            $consultation = RemoteConsultation::whereKey($reference->remote_consultation_id)->lockForUpdate()->firstOrFail();
            $quote = RemoteConsultationQuote::whereKey($quoteId)->lockForUpdate()->firstOrFail();
            if ($consultation->approval_status !== 'APPROVED') {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '승인된 상담에만 답변할 수 있습니다.');
            }
            $hospital = Hospital::whereKey($actor->hospital_id)->where('status', Hospital::STATUS_ACTIVE)->where('allow_status', Hospital::ALLOW_APPROVED)->firstOrFail();
            $answer = trim($input['answer']);
            if ($answer === '') {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '답변을 입력해 주세요.');
            }
            // 최초 답변만 과금한다. 같은 견적의 재전송과 수정은 추가 소진을 만들지 않는다.
            if ($quote->answered_at === null && $quote->status === 'WAITING') {
                $pricing = app(ConsultationPricingResolver::class)->fixed('REMOTE');
                $wallet = HospitalWallet::where('hospital_id', $hospital->id)->lockForUpdate()->first();
                if (! $wallet || $wallet->availableTotalBalance() < $pricing['price']) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '충전금 잔액이 부족합니다. 충전 후 답변을 등록해 주세요.');
                }
                if ($pricing['price'] > 0) {
                    $service = min($pricing['price'], (int) $wallet->service_balance);
                    app(HospitalWalletUsageCompleteAction::class)->execute(new HospitalWalletUsageData(
                        hospitalId: (int) $hospital->id, paidPoints: $pricing['price'] - $service, servicePoints: $service,
                        idempotencyKey: 'remote-consultation:answer:'.$quote->id, processedAt: now(),
                        reference: $quote, referenceLabel: '비대면상담 #'.$consultation->id,
                        requester: $actor, requesterKind: 'HOSPITAL', source: 'hospital.remote-consultation.answer',
                        metadata: ['pricing_version_id' => $pricing['version_id']],
                    ));
                }
            }
            $quote->update(['answer' => $answer, 'total_amount' => $input['total_amount'], 'status' => 'ANSWERED', 'answered_at' => $quote->answered_at ?? now()]);

            return ['id' => $quote->id, 'status' => $quote->status, 'answered_at' => $quote->answered_at->toIso8601String()];
        }, 3);
    }
}
