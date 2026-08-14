<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletServicePointTransactionForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletServicePointUpdateForStaffQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class HospitalWalletServicePointProcessForStaffAction
{
    public function __construct(
        private readonly HospitalWalletServicePointUpdateForStaffQuery $query,
    ) {}

    public function execute(array $payload, string $type, AccountStaff $actor): array
    {
        $hospitalIds = $this->hospitalIds($payload['hospital_ids'] ?? []);
        $amount = (int) $payload['amount'];
        $reason = trim((string) $payload['reason']);
        $batchUuid = (string) $payload['idempotency_key'];

        $operations = DB::transaction(function () use ($hospitalIds, $amount, $reason, $batchUuid, $type, $actor): Collection {
            $wallets = $this->query->getWalletsForUpdate($hospitalIds);
            $this->assertAllWalletsExist($wallets, $hospitalIds);

            $existingOperations = $this->query->getBatchOperations($batchUuid);
            if ($existingOperations->isNotEmpty()) {
                $this->assertMatchingRetry($existingOperations, $hospitalIds, $amount, $reason, $type);

                return $existingOperations;
            }

            if ($type === HospitalWalletOperation::TYPE_SERVICE_RECLAIM) {
                $this->assertReclaimable($wallets, $amount);
            }

            return $wallets
                ->map(fn (HospitalWallet $wallet): HospitalWalletOperation => $this->createCompletedOperation(
                    $wallet,
                    $type,
                    $amount,
                    $reason,
                    $batchUuid,
                    $actor,
                    count($hospitalIds),
                ))
                ->values();
        });

        return [
            'batch_uuid' => $batchUuid,
            'type' => $type,
            'type_label' => HospitalWalletOperation::typeLabel($type),
            'processed_count' => $operations->count(),
            'amount_per_hospital' => $amount,
            'total_amount' => $amount * $operations->count(),
            'items' => $operations
                ->map(fn (HospitalWalletOperation $operation): array => HospitalWalletServicePointTransactionForStaffDto::fromModel($operation)->toArray())
                ->values()
                ->all(),
        ];
    }

    private function hospitalIds(array $hospitalIds): array
    {
        return collect($hospitalIds)
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function assertAllWalletsExist(Collection $wallets, array $hospitalIds): void
    {
        $foundHospitalIds = $wallets
            ->pluck('hospital_id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($foundHospitalIds === $hospitalIds) {
            return;
        }

        throw new CustomException(
            ErrorCode::INVALID_REQUEST,
            '충전금 지갑이 생성되지 않은 병의원이 포함되어 있습니다.',
        );
    }

    private function assertReclaimable(Collection $wallets, int $amount): void
    {
        $insufficientWallets = $wallets
            ->filter(static fn (HospitalWallet $wallet): bool => (int) $wallet->service_balance < $amount)
            ->values();

        if ($insufficientWallets->isEmpty()) {
            return;
        }

        throw new CustomException(
            ErrorCode::INVALID_REQUEST,
            '회수 포인트가 서비스 잔여 포인트를 초과할 수 없습니다.',
            [
                'hospitals' => $insufficientWallets
                    ->map(static fn (HospitalWallet $wallet): array => [
                        'id' => (int) $wallet->hospital_id,
                        'name' => (string) ($wallet->hospital?->name ?? ''),
                        'service_balance' => (int) $wallet->service_balance,
                        'requested_amount' => $amount,
                    ])
                    ->all(),
            ],
        );
    }

    private function createCompletedOperation(
        HospitalWallet $wallet,
        string $type,
        int $amount,
        string $reason,
        string $batchUuid,
        AccountStaff $actor,
        int $batchSize,
    ): HospitalWalletOperation {
        $serviceBalanceBefore = (int) $wallet->service_balance;
        $serviceBalanceAfter = $type === HospitalWalletOperation::TYPE_SERVICE_GRANT
            ? $serviceBalanceBefore + $amount
            : $serviceBalanceBefore - $amount;
        $direction = $type === HospitalWalletOperation::TYPE_SERVICE_GRANT
            ? HospitalWalletTransactionEntry::DIRECTION_CREDIT
            : HospitalWalletTransactionEntry::DIRECTION_DEBIT;
        $processedAt = now();

        $operation = $this->query->createOperation($wallet, [
            'batch_uuid' => $batchUuid,
            'type' => $type,
            'status' => HospitalWalletOperation::STATUS_COMPLETED,
            'amount' => $amount,
            'idempotency_key' => implode(':', [$batchUuid, $type, $wallet->hospital_id]),
            'requester_type' => $actor->getMorphClass(),
            'requester_id' => (int) $actor->getKey(),
            'requester_kind' => OperationHistory::ACTOR_KIND_STAFF,
            'processor_type' => $actor->getMorphClass(),
            'processor_id' => (int) $actor->getKey(),
            'processor_kind' => OperationHistory::ACTOR_KIND_STAFF,
            'reason' => $reason,
            'processed_at' => $processedAt,
            'metadata' => [
                'source' => $type === HospitalWalletOperation::TYPE_SERVICE_GRANT
                    ? 'staff.hospital-wallet.service-grant'
                    : 'staff.hospital-wallet.service-reclaim',
                'batch_size' => $batchSize,
            ],
        ]);

        $transaction = $this->query->createTransaction($operation, $wallet, [
            'amount' => $amount,
            'paid_balance_before' => (int) $wallet->paid_balance,
            'paid_balance_after' => (int) $wallet->paid_balance,
            'reserved_paid_balance_before' => (int) $wallet->reserved_paid_balance,
            'reserved_paid_balance_after' => (int) $wallet->reserved_paid_balance,
            'service_balance_before' => $serviceBalanceBefore,
            'service_balance_after' => $serviceBalanceAfter,
            'created_at' => $processedAt,
            'updated_at' => $processedAt,
        ]);

        $this->query->createServiceEntry($transaction, $direction, $amount);
        $this->query->updateServiceBalance($wallet, $serviceBalanceAfter, $processedAt);

        return $operation->load(['wallet.hospital:id,name', 'transaction']);
    }

    private function assertMatchingRetry(
        Collection $operations,
        array $hospitalIds,
        int $amount,
        string $reason,
        string $type,
    ): void {
        $existingHospitalIds = $operations
            ->map(static fn (HospitalWalletOperation $operation): int => (int) $operation->wallet?->hospital_id)
            ->sort()
            ->values()
            ->all();

        $matches = $existingHospitalIds === $hospitalIds
            && $operations->every(static fn (HospitalWalletOperation $operation): bool => (string) $operation->type === $type
                && $operation->status === HospitalWalletOperation::STATUS_COMPLETED
                && (int) $operation->amount === $amount
                && trim((string) $operation->reason) === $reason);

        if ($matches) {
            return;
        }

        throw new CustomException(
            ErrorCode::INVALID_REQUEST,
            '동일한 중복 처리 방지 키가 다른 요청에 이미 사용되었습니다.',
        );
    }
}
