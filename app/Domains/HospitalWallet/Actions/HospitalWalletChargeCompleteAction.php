<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalWallet\Data\HospitalWalletChargeData;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use App\Domains\HospitalWallet\Queries\HospitalWalletBalanceMutationQuery;
use Illuminate\Support\Facades\DB;

final class HospitalWalletChargeCompleteAction
{
    public function __construct(private readonly HospitalWalletBalanceMutationQuery $query) {}

    public function execute(HospitalWalletChargeData $data): HospitalWalletOperation
    {
        $this->validate($data);

        return DB::transaction(function () use ($data): HospitalWalletOperation {
            $wallet = $this->query->walletForUpdate($data->hospitalId);
            if (! $wallet) {
                throw new CustomException(ErrorCode::NOT_FOUND, '병의원 충전금 지갑을 찾을 수 없습니다.');
            }

            $existing = $this->query->operationByIdempotencyKeyForUpdate($data->idempotencyKey);
            if ($existing) {
                $this->assertMatchingRetry($existing, $wallet, $data);

                return $this->query->loadDetail($existing);
            }

            $paidBalanceBefore = (int) $wallet->paid_balance;
            $paidBalanceAfter = $paidBalanceBefore + $data->points;
            $operation = $this->query->createOperation($wallet, [
                'type' => HospitalWalletOperation::TYPE_CHARGE,
                'status' => HospitalWalletOperation::STATUS_COMPLETED,
                'amount' => $data->points,
                'idempotency_key' => $data->idempotencyKey,
                'requester_type' => $data->requester?->getMorphClass(),
                'requester_id' => $data->requester ? (int) $data->requester->getKey() : null,
                'requester_kind' => $data->requesterKind,
                'processor_type' => $data->requester?->getMorphClass(),
                'processor_id' => $data->requester ? (int) $data->requester->getKey() : null,
                'processor_kind' => $data->requesterKind,
                'reason' => $data->reason,
                'processed_at' => $data->paidAt,
                'metadata' => [...$data->metadata, 'source' => $data->source],
            ]);

            $this->query->createPayment($operation, [
                'payment_method' => $data->paymentMethod,
                'provider' => $data->provider,
                'provider_transaction_id' => $data->providerTransactionId,
                'depositor_name' => $data->depositorName,
                'supply_amount' => $data->supplyAmount,
                'vat_amount' => $data->vatAmount,
                'payment_amount' => $data->paymentAmount,
                'virtual_account_bank' => $data->virtualAccountBank,
                'virtual_account_number' => $data->virtualAccountNumber,
                'virtual_account_expires_at' => $data->virtualAccountExpiresAt,
                'paid_at' => $data->paidAt,
                'metadata' => $data->metadata,
            ]);

            $transaction = $this->query->createTransaction($operation, $wallet, [
                'amount' => $data->points,
                'paid_balance_before' => $paidBalanceBefore,
                'paid_balance_after' => $paidBalanceAfter,
                'reserved_paid_balance_before' => (int) $wallet->reserved_paid_balance,
                'reserved_paid_balance_after' => (int) $wallet->reserved_paid_balance,
                'service_balance_before' => (int) $wallet->service_balance,
                'service_balance_after' => (int) $wallet->service_balance,
                'created_at' => $data->paidAt,
                'updated_at' => $data->paidAt,
            ]);
            $this->query->createEntry(
                $transaction,
                HospitalWalletTransactionEntry::BALANCE_TYPE_PAID,
                HospitalWalletTransactionEntry::DIRECTION_CREDIT,
                $data->points,
            );
            $this->query->updateWallet($wallet, $paidBalanceAfter, (int) $wallet->service_balance, $data->paidAt);

            return $this->query->loadDetail($operation);
        });
    }

    private function validate(HospitalWalletChargeData $data): void
    {
        $valid = $data->hospitalId > 0
            && $data->points > 0
            && $data->points === $data->supplyAmount
            && $data->vatAmount >= 0
            && $data->paymentAmount === $data->supplyAmount + $data->vatAmount
            && trim($data->paymentMethod) !== ''
            && mb_strlen($data->paymentMethod) <= 30
            && trim($data->idempotencyKey) !== ''
            && mb_strlen($data->idempotencyKey) <= 100
            && mb_strlen($data->requesterKind) <= 40
            && ($data->provider === null || mb_strlen($data->provider) <= 50)
            && ($data->providerTransactionId === null || mb_strlen($data->providerTransactionId) <= 120)
            && ($data->depositorName === null || mb_strlen($data->depositorName) <= 100)
            && ($data->virtualAccountBank === null || mb_strlen($data->virtualAccountBank) <= 50)
            && ($data->virtualAccountNumber === null || mb_strlen($data->virtualAccountNumber) <= 100);

        if (! $valid) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '충전 금액 또는 결제 정보가 올바르지 않습니다.');
        }

        if ($data->requester === null && $data->requesterKind !== OperationHistory::ACTOR_KIND_SYSTEM) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '충전 요청 주체 정보가 올바르지 않습니다.');
        }
    }

    private function assertMatchingRetry(
        HospitalWalletOperation $operation,
        HospitalWallet $wallet,
        HospitalWalletChargeData $data,
    ): void {
        $operation->loadMissing('payment');
        $payment = $operation->payment;
        $matches = $operation->type === HospitalWalletOperation::TYPE_CHARGE
            && $operation->status === HospitalWalletOperation::STATUS_COMPLETED
            && (int) $operation->hospital_wallet_id === (int) $wallet->id
            && (int) $operation->amount === $data->points
            && $payment
            && $payment->payment_method === $data->paymentMethod
            && $payment->provider_transaction_id === $data->providerTransactionId
            && (int) $payment->payment_amount === $data->paymentAmount;

        if (! $matches) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '동일한 중복 처리 방지 키가 다른 충전 요청에 사용되었습니다.');
        }
    }
}
