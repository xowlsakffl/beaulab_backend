<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Support;

final readonly class HospitalWalletRefundAmount
{
    private const int VAT_PERCENT = 10;

    private function __construct(
        public int $supplyAmount,
        public int $vatAmount,
        public int $totalAmount,
    ) {}

    public static function fromPoints(int $points): self
    {
        $supplyAmount = max(0, $points);
        $vatAmount = (int) round($supplyAmount * self::VAT_PERCENT / 100);

        return new self(
            supplyAmount: $supplyAmount,
            vatAmount: $vatAmount,
            totalAmount: $supplyAmount + $vatAmount,
        );
    }

    public static function pointsFromTotalAmount(int $totalAmount): int
    {
        return max(0, (int) round($totalAmount / (1 + self::VAT_PERCENT / 100)));
    }
}
