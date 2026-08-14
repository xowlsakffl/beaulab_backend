<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Data;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

final readonly class HospitalWalletChargeData
{
    public function __construct(
        public int $hospitalId,
        public int $points,
        public int $supplyAmount,
        public int $vatAmount,
        public int $paymentAmount,
        public string $paymentMethod,
        public string $idempotencyKey,
        public DateTimeInterface $paidAt,
        public ?string $provider = null,
        public ?string $providerTransactionId = null,
        public ?string $depositorName = null,
        public ?string $virtualAccountBank = null,
        public ?string $virtualAccountNumber = null,
        public ?DateTimeInterface $virtualAccountExpiresAt = null,
        public ?Model $requester = null,
        public string $requesterKind = 'SYSTEM',
        public ?string $reason = null,
        public string $source = 'system.hospital-wallet.charge',
        public array $metadata = [],
    ) {}
}
