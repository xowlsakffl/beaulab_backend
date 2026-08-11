<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Dto\Staff;

use App\Domains\HospitalWallet\Models\HospitalWalletTransaction;

final readonly class HospitalWalletServicePointTransactionForStaffDto
{
    public function __construct(private HospitalWalletTransaction $transaction) {}

    public static function fromModel(HospitalWalletTransaction $transaction): self
    {
        return new self($transaction);
    }

    public function toArray(): array
    {
        return [
            'id' => (int) $this->transaction->id,
            'hospital' => $this->hospital(),
            'type' => (string) $this->transaction->type,
            'type_label' => HospitalWalletTransaction::typeLabel((string) $this->transaction->type),
            'amount' => (int) $this->transaction->amount,
            'total_balance' => $this->transaction->totalBalanceAfter(),
            'paid_balance' => (int) $this->transaction->paid_balance_after,
            'service_balance' => (int) $this->transaction->service_balance_after,
            'reason' => $this->transaction->reason,
            'created_at' => $this->transaction->created_at?->toISOString(),
        ];
    }

    private function hospital(): ?array
    {
        $wallet = $this->transaction->wallet;
        $hospital = $wallet?->hospital;

        if (! $hospital) {
            return null;
        }

        return [
            'id' => (int) $hospital->id,
            'name' => (string) $hospital->name,
        ];
    }
}
