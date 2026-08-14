<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries;

use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletPayment;
use App\Domains\HospitalWallet\Models\HospitalWalletTransaction;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use DateTimeInterface;

final class HospitalWalletBalanceMutationQuery
{
    public function walletForUpdate(int $hospitalId): ?HospitalWallet
    {
        return HospitalWallet::query()
            ->whereHas('hospital')
            ->where('hospital_id', $hospitalId)
            ->lockForUpdate()
            ->first();
    }

    public function operationByIdempotencyKeyForUpdate(string $idempotencyKey): ?HospitalWalletOperation
    {
        return HospitalWalletOperation::query()
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
    }

    public function createOperation(HospitalWallet $wallet, array $attributes): HospitalWalletOperation
    {
        return $wallet->operations()->create($attributes);
    }

    public function createPayment(HospitalWalletOperation $operation, array $attributes): HospitalWalletPayment
    {
        return $operation->payment()->create($attributes);
    }

    public function createTransaction(
        HospitalWalletOperation $operation,
        HospitalWallet $wallet,
        array $attributes,
    ): HospitalWalletTransaction {
        return $operation->transaction()->create([
            'hospital_wallet_id' => $wallet->getKey(),
            ...$attributes,
        ]);
    }

    public function createEntry(
        HospitalWalletTransaction $transaction,
        string $balanceType,
        string $direction,
        int $amount,
    ): HospitalWalletTransactionEntry {
        return $transaction->entries()->create([
            'balance_type' => $balanceType,
            'direction' => $direction,
            'amount' => $amount,
        ]);
    }

    public function updateWallet(
        HospitalWallet $wallet,
        int $paidBalance,
        int $serviceBalance,
        DateTimeInterface $lastTransactionAt,
    ): void {
        $wallet->update([
            'paid_balance' => $paidBalance,
            'service_balance' => $serviceBalance,
            'last_transaction_at' => $lastTransactionAt,
        ]);
    }

    public function loadDetail(HospitalWalletOperation $operation): HospitalWalletOperation
    {
        return $operation->load(['wallet', 'payment', 'transaction.entries', 'reference']);
    }
}
