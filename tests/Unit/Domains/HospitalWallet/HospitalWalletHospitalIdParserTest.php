<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HospitalWallet;

use App\Domains\HospitalWallet\Support\HospitalWalletHospitalIdParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HospitalWalletHospitalIdParserTest extends TestCase
{
    #[DataProvider('hidKeywords')]
    public function test_it_parses_hid_keywords(string $keyword, int $expected): void
    {
        self::assertSame($expected, HospitalWalletHospitalIdParser::fromKeyword($keyword));
    }

    public function test_plain_numbers_are_opt_in(): void
    {
        self::assertNull(HospitalWalletHospitalIdParser::fromKeyword('123'));
        self::assertSame(123, HospitalWalletHospitalIdParser::fromKeyword('123', allowPlainNumber: true));
    }

    public static function hidKeywords(): array
    {
        return [
            ['HID123', 123],
            ['hid-123', 123],
            ['HID_123', 123],
            ['HID 123', 123],
        ];
    }
}
