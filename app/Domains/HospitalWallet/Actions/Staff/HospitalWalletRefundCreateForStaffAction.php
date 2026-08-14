<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletRefundForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletRefund;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletRefundForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletRefundCreateForStaffAction
{
    public function __construct(
        private readonly HospitalWalletRefundForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('requestRefund', HospitalWallet::class);

        $actor = auth()->user();
        if (! $actor instanceof AccountStaff) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        $directProcess = $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_REFUND_PROCESS);
        $replayed = false;

        $operation = DB::transaction(function () use ($payload, $actor, $directProcess, &$replayed): HospitalWalletOperation {
            $hospitalId = (int) $payload['hospital_id'];
            $amount = (int) $payload['amount'];
            $reason = trim((string) $payload['reason']);
            $idempotencyKey = (string) $payload['idempotency_key'];
            $wallet = $this->query->walletForUpdate($hospitalId);

            if (! $wallet) {
                throw new CustomException(ErrorCode::NOT_FOUND, '병의원 충전금 지갑을 찾을 수 없습니다.');
            }

            $existing = $this->query->operationByIdempotencyKeyForUpdate($idempotencyKey);
            if ($existing) {
                $this->assertMatchingRetry($existing, $wallet, $payload);
                $replayed = true;

                return $this->query->loadDetail($existing);
            }

            if ($amount > $wallet->availablePaidBalance()) {
                throw new CustomException(
                    ErrorCode::INVALID_REQUEST,
                    '보유 포인트를 초과했습니다.',
                    [
                        'available_paid_balance' => $wallet->availablePaidBalance(),
                        'requested_amount' => $amount,
                    ],
                );
            }

            $processedAt = $directProcess ? now() : null;
            $operation = $this->query->createOperation($wallet, [
                'type' => HospitalWalletOperation::TYPE_REFUND,
                'status' => $directProcess
                    ? HospitalWalletOperation::STATUS_COMPLETED
                    : HospitalWalletOperation::STATUS_PENDING,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
                'requester_type' => $actor->getMorphClass(),
                'requester_id' => (int) $actor->getKey(),
                'requester_kind' => OperationHistory::ACTOR_KIND_STAFF,
                'processor_type' => $directProcess ? $actor->getMorphClass() : null,
                'processor_id' => $directProcess ? (int) $actor->getKey() : null,
                'processor_kind' => $directProcess ? OperationHistory::ACTOR_KIND_STAFF : null,
                'reason' => $reason,
                'processed_at' => $processedAt,
                'metadata' => [
                    'source' => $directProcess
                        ? 'staff.hospital-wallet.refund-direct'
                        : 'staff.hospital-wallet.refund-request',
                ],
            ]);

            $vatAmount = (int) round($amount * 0.1);
            $refund = $this->query->createRefund($operation, [
                'supply_amount' => $amount,
                'vat_amount' => $vatAmount,
                'refund_amount' => $amount + $vatAmount,
                'bank_name' => trim((string) $payload['bank_name']),
                'account_number' => (string) $payload['account_number'],
            ]);

            $this->mediaAction->attachOne(
                $refund,
                $payload['business_registration_file'] ?? null,
                HospitalWalletRefund::COLLECTION_BUSINESS_REGISTRATION_FILE,
                'hospital-wallet-refund',
                'business-registration-file',
            );
            $this->mediaAction->attachOne(
                $refund,
                $payload['bankbook_file'] ?? null,
                HospitalWalletRefund::COLLECTION_BANKBOOK_FILE,
                'hospital-wallet-refund',
                'bankbook-file',
            );

            if ($directProcess) {
                $this->query->createPaidDebitTransaction(
                    $operation,
                    $wallet,
                    (int) $wallet->paid_balance - $amount,
                    (int) $wallet->reserved_paid_balance,
                    $processedAt,
                );
                $this->query->updateWallet(
                    $wallet,
                    (int) $wallet->paid_balance - $amount,
                    (int) $wallet->reserved_paid_balance,
                    $processedAt,
                );
            } else {
                $this->query->updateWallet(
                    $wallet,
                    (int) $wallet->paid_balance,
                    (int) $wallet->reserved_paid_balance + $amount,
                );
            }

            return $this->query->loadDetail($operation);
        });

        return [
            'refund' => HospitalWalletRefundForStaffDto::fromModel($operation)->toArray(),
            'wallet' => [
                'hospital_id' => (int) $operation->wallet->hospital_id,
                'total_balance' => $operation->wallet->availableTotalBalance(),
                'paid_balance' => $operation->wallet->availablePaidBalance(),
                'owned_paid_balance' => (int) $operation->wallet->paid_balance,
                'reserved_paid_balance' => (int) $operation->wallet->reserved_paid_balance,
                'service_balance' => (int) $operation->wallet->service_balance,
            ],
            'direct_processed' => $directProcess,
            'replayed' => $replayed,
        ];
    }

    private function assertMatchingRetry(
        HospitalWalletOperation $operation,
        HospitalWallet $wallet,
        array $payload,
    ): void {
        $refund = $operation->refund;
        $matches = $operation->type === HospitalWalletOperation::TYPE_REFUND
            && (int) $operation->hospital_wallet_id === (int) $wallet->id
            && (int) $operation->amount === (int) $payload['amount']
            && trim((string) $operation->reason) === trim((string) $payload['reason'])
            && $refund
            && (string) $refund->bank_name === trim((string) $payload['bank_name'])
            && (string) $refund->account_number === (string) $payload['account_number'];

        if ($matches) {
            return;
        }

        throw new CustomException(
            ErrorCode::INVALID_REQUEST,
            '동일한 중복 처리 방지 키가 다른 요청에 이미 사용되었습니다.',
        );
    }
}
