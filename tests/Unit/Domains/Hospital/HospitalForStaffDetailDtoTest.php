<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Hospital;

use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use PHPUnit\Framework\TestCase;

final class HospitalForStaffDetailDtoTest extends TestCase
{
    public function test_it_exposes_available_wallet_balances_when_wallet_is_loaded(): void
    {
        $hospital = $this->hospital();
        $wallet = new HospitalWallet([
            'hospital_id' => 1,
            'paid_balance' => 120_000,
            'reserved_paid_balance' => 20_000,
            'service_balance' => 30_000,
        ]);
        $wallet->setAttribute('id', 10);
        $hospital->setRelation('wallet', $wallet);

        $data = HospitalForStaffDetailDto::fromModel($hospital)->toArray();

        self::assertSame(130_000, $data['wallet']['total_balance']);
        self::assertSame(100_000, $data['wallet']['paid_balance']);
        self::assertSame(120_000, $data['wallet']['owned_paid_balance']);
        self::assertSame(20_000, $data['wallet']['reserved_paid_balance']);
        self::assertSame(30_000, $data['wallet']['service_balance']);
    }

    public function test_it_omits_wallet_when_relation_is_not_loaded(): void
    {
        $data = HospitalForStaffDetailDto::fromModel($this->hospital())->toArray();

        self::assertArrayNotHasKey('wallet', $data);
    }

    private function hospital(): Hospital
    {
        $hospital = new Hospital([
            'name' => '테스트 병원',
            'department' => Hospital::DEPARTMENT_OTHER,
            'allow_status' => Hospital::ALLOW_APPROVED,
            'status' => Hospital::STATUS_ACTIVE,
        ]);
        $hospital->setAttribute('id', 1);

        return $hospital;
    }
}
