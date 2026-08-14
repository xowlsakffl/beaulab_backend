<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries\Staff;

use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletTransaction;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use Illuminate\Support\Collection;

final class HospitalWalletServicePointUpdateForStaffQuery
{
    public function getWalletsForUpdate(array $hospitalIds): Collection
    {
        return HospitalWallet::query()
            ->with('hospital:id,name')
            ->whereIn('hospital_id', $hospitalIds)
            ->orderBy('hospital_id')
            ->lockForUpdate()
            ->get([
                'id',
                'hospital_id',
                'paid_balance',
                'reserved_paid_balance',
                'service_balance',
                'last_transaction_at',
            ]);
    }

    public function getBatchOperations(string $batchUuid): Collection
    {
        return HospitalWalletOperation::query()
            ->with(['wallet.hospital:id,name', 'transaction'])
            ->where('batch_uuid', $batchUuid)
            ->orderBy('hospital_wallet_id')
            ->lockForUpdate()
            ->get();
    }

    public function createOperation(HospitalWallet $wallet, array $attributes): HospitalWalletOperation
    {
        return $wallet->operations()->create($attributes);
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

    public function createServiceEntry(
        HospitalWalletTransaction $transaction,
        string $direction,
        int $amount,
    ): HospitalWalletTransactionEntry {
        return $transaction->entries()->create([
            'balance_type' => HospitalWalletTransactionEntry::BALANCE_TYPE_SERVICE,
            'direction' => $direction,
            'amount' => $amount,
        ]);
    }

    public function updateServiceBalance(
        HospitalWallet $wallet,
        int $serviceBalance,
        \DateTimeInterface $lastTransactionAt,
    ): void {
        $wallet->update([
            'service_balance' => $serviceBalance,
            'last_transaction_at' => $lastTransactionAt,
        ]);
    }
}
