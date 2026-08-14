<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries\Staff;

use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletRefund;
use App\Domains\HospitalWallet\Models\HospitalWalletTransaction;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;

final class HospitalWalletRefundForStaffQuery
{
    public function walletForUpdate(int $hospitalId): ?HospitalWallet
    {
        return HospitalWallet::query()
            ->with('hospital:id,name')
            ->whereHas('hospital')
            ->where('hospital_id', $hospitalId)
            ->lockForUpdate()
            ->first();
    }

    public function walletByIdForUpdate(int $walletId): ?HospitalWallet
    {
        return HospitalWallet::query()
            ->with('hospital:id,name')
            ->whereKey($walletId)
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

    public function operationForUpdate(int $operationId): ?HospitalWalletOperation
    {
        return HospitalWalletOperation::query()
            ->whereKey($operationId)
            ->lockForUpdate()
            ->first();
    }

    public function createOperation(HospitalWallet $wallet, array $attributes): HospitalWalletOperation
    {
        return $wallet->operations()->create($attributes);
    }

    public function createRefund(HospitalWalletOperation $operation, array $attributes): HospitalWalletRefund
    {
        return $operation->refund()->create($attributes);
    }

    public function createPaidDebitTransaction(
        HospitalWalletOperation $operation,
        HospitalWallet $wallet,
        int $paidBalanceAfter,
        int $reservedPaidBalanceAfter,
        \DateTimeInterface $processedAt,
    ): HospitalWalletTransaction {
        $transaction = $operation->transaction()->create([
            'hospital_wallet_id' => $wallet->getKey(),
            'amount' => (int) $operation->amount,
            'paid_balance_before' => (int) $wallet->paid_balance,
            'paid_balance_after' => $paidBalanceAfter,
            'reserved_paid_balance_before' => (int) $wallet->reserved_paid_balance,
            'reserved_paid_balance_after' => $reservedPaidBalanceAfter,
            'service_balance_before' => (int) $wallet->service_balance,
            'service_balance_after' => (int) $wallet->service_balance,
            'created_at' => $processedAt,
            'updated_at' => $processedAt,
        ]);

        $transaction->entries()->create([
            'balance_type' => HospitalWalletTransactionEntry::BALANCE_TYPE_PAID,
            'direction' => HospitalWalletTransactionEntry::DIRECTION_DEBIT,
            'amount' => (int) $operation->amount,
        ]);

        return $transaction;
    }

    public function updateWallet(
        HospitalWallet $wallet,
        int $paidBalance,
        int $reservedPaidBalance,
        ?\DateTimeInterface $lastTransactionAt = null,
    ): void {
        $attributes = [
            'paid_balance' => $paidBalance,
            'reserved_paid_balance' => $reservedPaidBalance,
        ];

        if ($lastTransactionAt !== null) {
            $attributes['last_transaction_at'] = $lastTransactionAt;
        }

        $wallet->update($attributes);
    }

    public function loadDetail(HospitalWalletOperation $operation): HospitalWalletOperation
    {
        return $operation->load($this->detailRelations());
    }

    /** @return array<int, string> */
    private function detailRelations(): array
    {
        return [
            'wallet.hospital' => fn ($query) => $query->withTrashed(),
            'transaction.entries',
            'requester',
            'processor',
            'refund.businessRegistrationFile',
            'refund.bankbookFile',
        ];
    }
}
