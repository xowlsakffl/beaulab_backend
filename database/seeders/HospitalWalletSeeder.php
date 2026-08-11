<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletTransaction;
use Database\Factories\HospitalWalletTransactionFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class HospitalWalletSeeder extends Seeder
{
    public function run(): void
    {
        $staff = AccountStaff::query()->orderBy('id')->first();

        Hospital::query()
            ->orderBy('id')
            ->each(function (Hospital $hospital) use ($staff): void {
                DB::transaction(function () use ($hospital, $staff): void {
                    $wallet = $hospital->wallet()->first()
                        ?? HospitalWallet::factory()->forHospital($hospital)->create();

                    if ($wallet->transactions()->exists()) {
                        return;
                    }

                    $this->seedTransactions($wallet, $staff);
                });
            });
    }

    private function seedTransactions(HospitalWallet $wallet, ?AccountStaff $staff): void
    {
        $occurredAt = now()->subDays(random_int(30, 90))->startOfDay()->addHours(10);
        $paidCharge = random_int(100, 500) * 10000;
        $serviceGrant = random_int(10, 50) * 10000;

        $this->create(
            HospitalWalletTransaction::factory()->charge($wallet, $paidCharge),
            $occurredAt,
            '가상계좌 충전',
        );

        $occurredAt = $occurredAt->copy()->addDays(random_int(1, 5));
        $this->create(
            HospitalWalletTransaction::factory()->serviceGrant($wallet, $serviceGrant),
            $occurredAt,
            '프로모션 서비스 포인트 지급',
            $staff,
        );

        $occurredAt = $occurredAt->copy()->addDays(random_int(1, 7));
        $usageAmount = min(random_int(10, 100) * 10000, $wallet->refresh()->totalBalance());
        $this->create(
            HospitalWalletTransaction::factory()->usage($wallet, $usageAmount),
            $occurredAt,
            '이벤트 및 광고 이용',
        );

        if ((int) $wallet->refresh()->service_balance >= 20000 && random_int(0, 1) === 1) {
            $occurredAt = $occurredAt->copy()->addDays(random_int(1, 5));
            $reclaimAmount = min(
                random_int(1, 5) * 10000,
                (int) $wallet->service_balance,
            );

            $this->create(
                HospitalWalletTransaction::factory()->serviceReclaim($wallet, $reclaimAmount),
                $occurredAt,
                '미사용 서비스 포인트 회수',
                $staff,
            );
        }

        if ((int) $wallet->refresh()->paid_balance >= 50000 && random_int(0, 2) === 0) {
            $occurredAt = $occurredAt->copy()->addDays(random_int(1, 5));
            $refundAmount = min(
                random_int(1, 5) * 10000,
                (int) $wallet->paid_balance,
            );

            $this->create(
                HospitalWalletTransaction::factory()->refund($wallet, $refundAmount),
                $occurredAt,
                '충전금 환불 테스트 데이터',
                $staff,
            );
        }
    }

    private function create(
        HospitalWalletTransactionFactory $factory,
        Carbon $occurredAt,
        string $reason,
        ?AccountStaff $staff = null,
    ): void {
        if ($staff instanceof AccountStaff) {
            $factory = $factory->performedBy($staff);
        }

        $factory->create([
            'reason' => $reason,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }
}
