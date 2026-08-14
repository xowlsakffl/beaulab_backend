<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Dto\Staff;

use App\Domains\HospitalWallet\Models\HospitalWalletOperation;

final readonly class HospitalWalletServicePointTransactionForStaffDto
{
    public function __construct(private HospitalWalletOperation $operation) {}

    public static function fromModel(HospitalWalletOperation $operation): self
    {
        return new self($operation);
    }

    public function toArray(): array
    {
        $transaction = $this->operation->transaction;

        return [
            'id' => (int) $this->operation->id,
            'hospital' => $this->hospital(),
            'type' => (string) $this->operation->type,
            'type_label' => HospitalWalletOperation::typeLabel((string) $this->operation->type),
            'amount' => (int) $this->operation->amount,
            'total_balance' => $transaction?->totalBalanceAfter() ?? $this->operation->wallet?->availableTotalBalance() ?? 0,
            'paid_balance' => (int) ($transaction?->paid_balance_after ?? $this->operation->wallet?->availablePaidBalance() ?? 0),
            'service_balance' => (int) ($transaction?->service_balance_after ?? $this->operation->wallet?->service_balance ?? 0),
            'reason' => $this->operation->reason,
            'created_at' => $this->operation->processed_at?->toISOString(),
        ];
    }

    private function hospital(): ?array
    {
        $hospital = $this->operation->wallet?->hospital;
        if (! $hospital) {
            return null;
        }

        return [
            'id' => (int) $hospital->id,
            'name' => (string) $hospital->name,
        ];
    }
}
