<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Data;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

final readonly class HospitalWalletUsageData
{
    public function __construct(
        public int $hospitalId,
        public int $paidPoints,
        public int $servicePoints,
        public string $idempotencyKey,
        public DateTimeInterface $processedAt,
        public Model $reference,
        public string $referenceLabel,
        public ?Model $requester = null,
        public string $requesterKind = 'SYSTEM',
        public ?string $reason = null,
        public string $source = 'system.hospital-wallet.usage',
        public array $metadata = [],
    ) {}

    public function totalPoints(): int
    {
        return $this->paidPoints + $this->servicePoints;
    }
}
