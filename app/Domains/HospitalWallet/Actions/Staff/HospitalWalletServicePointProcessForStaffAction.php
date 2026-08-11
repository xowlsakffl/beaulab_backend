<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletServicePointTransactionForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletTransaction;
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

        $transactions = DB::transaction(function () use ($hospitalIds, $amount, $reason, $batchUuid, $type, $actor): Collection {
            $wallets = $this->query->getWalletsForUpdate($hospitalIds);
            $this->assertAllWalletsExist($wallets, $hospitalIds);

            $existingTransactions = $this->query->getBatchTransactions($batchUuid);
            if ($existingTransactions->isNotEmpty()) {
                $this->assertMatchingRetry($existingTransactions, $hospitalIds, $amount, $reason, $type);

                return $existingTransactions;
            }

            if ($type === HospitalWalletTransaction::TYPE_SERVICE_RECLAIM) {
                $this->assertReclaimable($wallets, $amount);
            }

            return $wallets
                ->map(fn (HospitalWallet $wallet): HospitalWalletTransaction => $this->createTransaction(
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
            'type_label' => HospitalWalletTransaction::typeLabel($type),
            'processed_count' => $transactions->count(),
            'amount_per_hospital' => $amount,
            'total_amount' => $amount * $transactions->count(),
            'items' => $transactions
                ->map(fn (HospitalWalletTransaction $transaction): array => HospitalWalletServicePointTransactionForStaffDto::fromModel($transaction)->toArray())
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, int|string>  $hospitalIds
     * @return array<int, int>
     */
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

    /**
     * @param  Collection<int, HospitalWallet>  $wallets
     * @param  array<int, int>  $hospitalIds
     */
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

    /**
     * @param  Collection<int, HospitalWallet>  $wallets
     */
    private function assertReclaimable(Collection $wallets, int $amount): void
    {
        $insufficientWallets = $wallets
            ->filter(static fn (HospitalWallet $wallet): bool => (int) $wallet->service_balance < $amount)
            ->values();

        if ($insufficientWallets->isEmpty()) {
            return;
        }

        $first = $insufficientWallets->first();
        $hospitalName = $first?->hospital?->name ?? '병의원';

        throw new CustomException(
            ErrorCode::INVALID_REQUEST,
            "회수 포인트가 서비스 잔여 P를 초과할 수 없습니다. ({$hospitalName})",
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

    private function createTransaction(
        HospitalWallet $wallet,
        string $type,
        int $amount,
        string $reason,
        string $batchUuid,
        AccountStaff $actor,
        int $batchSize,
    ): HospitalWalletTransaction {
        $serviceBalanceBefore = (int) $wallet->service_balance;
        $serviceBalanceAfter = $type === HospitalWalletTransaction::TYPE_SERVICE_GRANT
            ? $serviceBalanceBefore + $amount
            : $serviceBalanceBefore - $amount;
        $direction = $type === HospitalWalletTransaction::TYPE_SERVICE_GRANT
            ? HospitalWalletTransactionEntry::DIRECTION_CREDIT
            : HospitalWalletTransactionEntry::DIRECTION_DEBIT;

        $transaction = $this->query->createTransaction($wallet, [
            'type' => $type,
            'amount' => $amount,
            'paid_balance_before' => (int) $wallet->paid_balance,
            'paid_balance_after' => (int) $wallet->paid_balance,
            'service_balance_before' => $serviceBalanceBefore,
            'service_balance_after' => $serviceBalanceAfter,
            'batch_uuid' => $batchUuid,
            'idempotency_key' => implode(':', [$batchUuid, $type, $wallet->hospital_id]),
            'actor_type' => $actor->getMorphClass(),
            'actor_id' => (int) $actor->getKey(),
            'actor_kind' => OperationHistory::ACTOR_KIND_STAFF,
            'reason' => $reason,
            'metadata' => [
                'source' => $type === HospitalWalletTransaction::TYPE_SERVICE_GRANT
                    ? 'staff.hospital-wallet.service-grant'
                    : 'staff.hospital-wallet.service-reclaim',
                'batch_size' => $batchSize,
            ],
        ]);

        $this->query->createServiceEntry($transaction, $direction, $amount);
        $this->query->updateServiceBalance($wallet, $serviceBalanceAfter, $transaction->created_at);

        return $transaction->load('wallet.hospital:id,name');
    }

    /**
     * @param  Collection<int, HospitalWalletTransaction>  $transactions
     * @param  array<int, int>  $hospitalIds
     */
    private function assertMatchingRetry(
        Collection $transactions,
        array $hospitalIds,
        int $amount,
        string $reason,
        string $type,
    ): void {
        $existingHospitalIds = $transactions
            ->map(static fn (HospitalWalletTransaction $transaction): int => (int) $transaction->wallet?->hospital_id)
            ->sort()
            ->values()
            ->all();

        $matches = $existingHospitalIds === $hospitalIds
            && $transactions->every(static fn (HospitalWalletTransaction $transaction): bool => (string) $transaction->type === $type
                && (int) $transaction->amount === $amount
                && trim((string) $transaction->reason) === $reason);

        if ($matches) {
            return;
        }

        throw new CustomException(
            ErrorCode::INVALID_REQUEST,
            '동일한 중복 처리 방지 키가 다른 요청에 이미 사용되었습니다.',
        );
    }
}
