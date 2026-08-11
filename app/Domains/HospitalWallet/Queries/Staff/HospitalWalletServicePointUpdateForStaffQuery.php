<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries\Staff;

use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletTransaction;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use Illuminate\Support\Collection;

final class HospitalWalletServicePointUpdateForStaffQuery
{
    /**
     * @param  array<int, int>  $hospitalIds
     * @return Collection<int, HospitalWallet>
     */
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
                'service_balance',
                'last_transaction_at',
            ]);
    }

    /**
     * @return Collection<int, HospitalWalletTransaction>
     */
    public function getBatchTransactions(string $batchUuid): Collection
    {
        return HospitalWalletTransaction::query()
            ->with('wallet.hospital:id,name')
            ->where('batch_uuid', $batchUuid)
            ->orderBy('hospital_wallet_id')
            ->lockForUpdate()
            ->get();
    }

    public function createTransaction(HospitalWallet $wallet, array $attributes): HospitalWalletTransaction
    {
        return $wallet->transactions()->create($attributes);
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
