<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use Database\Factories\HospitalWalletOperationFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class HospitalWalletSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        $staff = AccountStaff::where('nickname', 'test_super_admin')->firstOrFail();
        Hospital::orderBy('id')->get()->each(function (Hospital $hospital, int $index) use ($staff): void {
            DB::transaction(function () use ($hospital, $index, $staff): void {
                $wallet = $hospital->wallet()->first() ?? HospitalWallet::factory()->forHospital($hospital)->create();
                if ($wallet->operations()->exists()) {
                    return;
                }
                if ($wallet->paid_balance || $wallet->service_balance || $wallet->reserved_paid_balance) {
                    throw new \LogicException('원장이 없는 잔액을 샘플 거래와 혼합하지 않습니다.');
                }
                // 승인된 병의원 중 무거래·잔액 부족 사례를 각각 남긴다.
                if ($index === 12) {
                    return;
                }
                if ($index === 13) {
                    $this->create(HospitalWalletOperationFactory::new()->chargeCompleted($wallet, 5000), now()->subDays(3), '개발용 소액 충전');

                    return;
                }
                for ($month = 5; $month >= 0; $month--) {
                    $at = now()->subMonthsNoOverflow($month)->subHours(8);
                    $amount = 500000 + $index * 10000 + (5 - $month) * 25000;
                    $this->create(HospitalWalletOperationFactory::new()->chargeCompleted($wallet, $amount), $at, '개발용 충전 완료');
                    $this->create(HospitalWalletOperationFactory::new()->serviceGrant($wallet, 180000)->performedBy($staff), $at->copy()->addMinute(), '개발용 서비스 포인트 적립');
                    $this->create(HospitalWalletOperationFactory::new()->serviceReclaim($wallet, 20000)->performedBy($staff), $at->copy()->addMinutes(2), '개발용 서비스 포인트 회수');
                    $this->create(HospitalWalletOperationFactory::new()->usage($wallet, 220000, '개발용 과거 소진'), $at->copy()->addMinutes(3), '서비스 포인트 우선 소진 사례');
                    $this->create(HospitalWalletOperationFactory::new()->refundCompleted($wallet, 20000)->performedBy($staff), $at->copy()->addMinutes(4), '개발용 완료 환불');
                }
                $this->create(HospitalWalletOperationFactory::new()->serviceGrant($wallet, 100000)->performedBy($staff), now()->subHours(7), '비대면 답변용 서비스 포인트');
                $this->create(HospitalWalletOperationFactory::new()->chargePending($wallet, 100000), now()->subHours(2), '개발용 입금 대기', 'PENDING');
                $this->create(HospitalWalletOperationFactory::new()->chargeCanceled($wallet, 50000), now()->subHour(), '개발용 입금 취소', 'CANCELED');
                $this->create(HospitalWalletOperationFactory::new()->chargePending($wallet, 30000)->state(['status' => 'FAILED', 'failed_at' => now()->subMinutes(50)]), now()->subMinutes(50), '개발용 결제 실패', 'FAILED');
                $this->create(HospitalWalletOperationFactory::new()->refundRejected($wallet, 10000)->performedBy($staff)->withRefundSeedMedia(), now()->subMinutes(45), '개발용 서류 미비 반려', 'REJECTED');
                $this->create(HospitalWalletOperationFactory::new()->refundPending($wallet, 10000)->requestedBy($staff)->withRefundSeedMedia(), now()->subMinutes(40), '개발용 승인 대기 환불', 'PENDING');
            });
        });
    }

    private function create(HospitalWalletOperationFactory $factory, Carbon $at, string $reason, string $status = 'COMPLETED'): void
    {
        $factory->create([
            'reason' => $reason, 'created_at' => $at, 'updated_at' => $at,
            'processed_at' => in_array($status, ['COMPLETED', 'REJECTED'], true) ? $at : null,
            'canceled_at' => $status === 'CANCELED' ? $at : null,
        ]);
    }
}
