<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Dto\Staff;

use App\Domains\HospitalWallet\Models\HospitalWallet;

final readonly class HospitalWalletForStaffDto
{
    public function __construct(private HospitalWallet $wallet) {}

    public static function fromModel(HospitalWallet $wallet): self
    {
        return new self($wallet);
    }

    public function toArray(): array
    {
        return [
            'id' => (int) $this->wallet->id,
            'hospital' => $this->hospital(),
            'total_balance' => $this->wallet->availableTotalBalance(),
            'paid_balance' => $this->wallet->availablePaidBalance(),
            'owned_paid_balance' => (int) $this->wallet->paid_balance,
            'reserved_paid_balance' => (int) $this->wallet->reserved_paid_balance,
            'service_balance' => (int) $this->wallet->service_balance,
            'active_event_count' => (int) $this->wallet->getAttribute('active_event_count'),
            'active_ad_count' => (int) $this->wallet->getAttribute('active_ad_count'),
            'last_transaction_at' => $this->wallet->last_transaction_at?->toISOString(),
        ];
    }

    private function hospital(): ?array
    {
        if (! $this->wallet->relationLoaded('hospital') || ! $this->wallet->hospital) {
            return null;
        }

        return [
            'id' => (int) $this->wallet->hospital->id,
            'name' => (string) $this->wallet->hospital->name,
            'is_deleted' => $this->wallet->hospital->trashed(),
        ];
    }
}
