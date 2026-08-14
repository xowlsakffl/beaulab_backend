<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalWallet\Data\HospitalWalletUsageData;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use App\Domains\HospitalWallet\Queries\HospitalWalletBalanceMutationQuery;
use Illuminate\Support\Facades\DB;

final class HospitalWalletUsageCompleteAction
{
    public function __construct(private readonly HospitalWalletBalanceMutationQuery $query) {}

    public function execute(HospitalWalletUsageData $data): HospitalWalletOperation
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

            if ($data->paidPoints > $wallet->availablePaidBalance() || $data->servicePoints > (int) $wallet->service_balance) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '사용 가능한 충전금 잔액이 부족합니다.', [
                    'available_paid_balance' => $wallet->availablePaidBalance(),
                    'service_balance' => (int) $wallet->service_balance,
                    'requested_paid_points' => $data->paidPoints,
                    'requested_service_points' => $data->servicePoints,
                ]);
            }

            $paidBalanceBefore = (int) $wallet->paid_balance;
            $serviceBalanceBefore = (int) $wallet->service_balance;
            $paidBalanceAfter = $paidBalanceBefore - $data->paidPoints;
            $serviceBalanceAfter = $serviceBalanceBefore - $data->servicePoints;
            $operation = $this->query->createOperation($wallet, [
                'type' => HospitalWalletOperation::TYPE_USAGE,
                'status' => HospitalWalletOperation::STATUS_COMPLETED,
                'amount' => $data->totalPoints(),
                'idempotency_key' => $data->idempotencyKey,
                'requester_type' => $data->requester?->getMorphClass(),
                'requester_id' => $data->requester ? (int) $data->requester->getKey() : null,
                'requester_kind' => $data->requesterKind,
                'processor_type' => $data->requester?->getMorphClass(),
                'processor_id' => $data->requester ? (int) $data->requester->getKey() : null,
                'processor_kind' => $data->requesterKind,
                'reference_type' => $data->reference->getMorphClass(),
                'reference_id' => (int) $data->reference->getKey(),
                'reference_label' => $data->referenceLabel,
                'reason' => $data->reason,
                'processed_at' => $data->processedAt,
                'metadata' => [
                    ...$data->metadata,
                    'source' => $data->source,
                    'paid_points' => $data->paidPoints,
                    'service_points' => $data->servicePoints,
                ],
            ]);

            $transaction = $this->query->createTransaction($operation, $wallet, [
                'amount' => $data->totalPoints(),
                'paid_balance_before' => $paidBalanceBefore,
                'paid_balance_after' => $paidBalanceAfter,
                'reserved_paid_balance_before' => (int) $wallet->reserved_paid_balance,
                'reserved_paid_balance_after' => (int) $wallet->reserved_paid_balance,
                'service_balance_before' => $serviceBalanceBefore,
                'service_balance_after' => $serviceBalanceAfter,
                'created_at' => $data->processedAt,
                'updated_at' => $data->processedAt,
            ]);
            if ($data->paidPoints > 0) {
                $this->query->createEntry(
                    $transaction,
                    HospitalWalletTransactionEntry::BALANCE_TYPE_PAID,
                    HospitalWalletTransactionEntry::DIRECTION_DEBIT,
                    $data->paidPoints,
                );
            }
            if ($data->servicePoints > 0) {
                $this->query->createEntry(
                    $transaction,
                    HospitalWalletTransactionEntry::BALANCE_TYPE_SERVICE,
                    HospitalWalletTransactionEntry::DIRECTION_DEBIT,
                    $data->servicePoints,
                );
            }
            $this->query->updateWallet($wallet, $paidBalanceAfter, $serviceBalanceAfter, $data->processedAt);

            return $this->query->loadDetail($operation);
        });
    }

    private function validate(HospitalWalletUsageData $data): void
    {
        $valid = $data->hospitalId > 0
            && $data->paidPoints >= 0
            && $data->servicePoints >= 0
            && $data->totalPoints() > 0
            && trim($data->idempotencyKey) !== ''
            && mb_strlen($data->idempotencyKey) <= 100
            && trim($data->referenceLabel) !== ''
            && mb_strlen($data->referenceLabel) <= 255
            && mb_strlen($data->requesterKind) <= 40
            && $data->reference->exists;

        if (! $valid) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '충전금 사용 정보가 올바르지 않습니다.');
        }

        if ($data->requester === null && $data->requesterKind !== OperationHistory::ACTOR_KIND_SYSTEM) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '충전금 사용 주체 정보가 올바르지 않습니다.');
        }
    }

    private function assertMatchingRetry(
        HospitalWalletOperation $operation,
        HospitalWallet $wallet,
        HospitalWalletUsageData $data,
    ): void {
        $metadata = is_array($operation->metadata) ? $operation->metadata : [];
        $matches = $operation->type === HospitalWalletOperation::TYPE_USAGE
            && $operation->status === HospitalWalletOperation::STATUS_COMPLETED
            && (int) $operation->hospital_wallet_id === (int) $wallet->id
            && (int) $operation->amount === $data->totalPoints()
            && $operation->reference_type === $data->reference->getMorphClass()
            && (int) $operation->reference_id === (int) $data->reference->getKey()
            && (int) ($metadata['paid_points'] ?? -1) === $data->paidPoints
            && (int) ($metadata['service_points'] ?? -1) === $data->servicePoints;

        if (! $matches) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '동일한 중복 처리 방지 키가 다른 충전금 사용 요청에 사용되었습니다.');
        }
    }
}
