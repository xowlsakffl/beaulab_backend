<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HospitalWallet;

use App\Domains\HospitalWallet\Support\HospitalWalletRefundAmount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HospitalWalletRefundAmountTest extends TestCase
{
    #[DataProvider('amounts')]
    public function test_it_calculates_the_canonical_refund_amount(
        int $points,
        int $expectedVat,
        int $expectedTotal,
    ): void {
        $amount = HospitalWalletRefundAmount::fromPoints($points);

        self::assertSame($points, $amount->supplyAmount);
        self::assertSame($expectedVat, $amount->vatAmount);
        self::assertSame($expectedTotal, $amount->totalAmount);
    }

    #[DataProvider('amounts')]
    public function test_it_converts_a_total_amount_back_to_points(
        int $points,
        int $expectedVat,
        int $expectedTotal,
    ): void {
        self::assertSame($points, HospitalWalletRefundAmount::pointsFromTotalAmount($expectedTotal));
    }

    public static function amounts(): array
    {
        return [
            'one point' => [1, 0, 1],
            'half-up VAT boundary' => [5, 1, 6],
            'typical amount' => [95, 10, 105],
            'large amount' => [300_000, 30_000, 330_000],
        ];
    }
}
