<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use Database\Factories\HospitalWalletOperationFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class HospitalWalletSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('Demo wallet data is only available in local/testing environments.');
        }

        $staff = AccountStaff::query()->orderBy('id')->first();

        Hospital::query()
            ->orderBy('id')
            ->each(function (Hospital $hospital) use ($staff): void {
                DB::transaction(function () use ($hospital, $staff): void {
                    $wallet = $hospital->wallet()->first()
                        ?? HospitalWallet::factory()->forHospital($hospital)->create();

                    if ($wallet->operations()->exists()) {
                        return;
                    }

                    $this->seedOperations($wallet, $staff);
                });
            });
    }

    private function seedOperations(HospitalWallet $wallet, ?AccountStaff $staff): void
    {
        $occurredAt = now()->subDays(random_int(30, 90))->startOfDay()->addHours(10);
        $paidCharge = random_int(100, 500) * 10000;
        $serviceGrant = random_int(10, 50) * 10000;

        $this->create(
            HospitalWalletOperationFactory::new()->chargeCompleted($wallet, $paidCharge),
            $occurredAt,
            '가상계좌 입금 완료',
        );

        $occurredAt = $occurredAt->copy()->addDays(random_int(1, 5));
        $this->create(
            HospitalWalletOperationFactory::new()->serviceGrant($wallet, $serviceGrant),
            $occurredAt,
            '프로모션 서비스 포인트 지급',
            $staff,
        );

        $occurredAt = $occurredAt->copy()->addDays(random_int(1, 7));
        $usageAmount = min(random_int(10, 100) * 10000, $wallet->refresh()->availableTotalBalance());
        $this->create(
            HospitalWalletOperationFactory::new()->usage($wallet, $usageAmount, '이벤트 DB 확인'),
            $occurredAt,
            '이벤트 DB 확인',
        );

        if ((int) $wallet->refresh()->service_balance >= 20000 && random_int(0, 1) === 1) {
            $occurredAt = $occurredAt->copy()->addDays(random_int(1, 5));
            $reclaimAmount = min(random_int(1, 5) * 10000, (int) $wallet->service_balance);

            $this->create(
                HospitalWalletOperationFactory::new()->serviceReclaim($wallet, $reclaimAmount),
                $occurredAt,
                '미사용 서비스 포인트 회수',
                $staff,
            );
        }

        $pendingAt = now()->subDays(random_int(0, 10))->setTime(random_int(9, 17), random_int(0, 59));
        $this->create(
            HospitalWalletOperationFactory::new()->chargePending($wallet, random_int(10, 100) * 10000),
            $pendingAt,
            '가상계좌 입금 대기',
            status: HospitalWalletOperation::STATUS_PENDING,
        );

        if (random_int(0, 2) === 0) {
            $canceledAt = now()->subDays(random_int(3, 20))->setTime(random_int(9, 17), random_int(0, 59));
            $this->create(
                HospitalWalletOperationFactory::new()->chargeCanceled($wallet, random_int(10, 100) * 10000),
                $canceledAt,
                '입금 기한 만료',
                status: HospitalWalletOperation::STATUS_CANCELED,
            );
        }

        $wallet->refresh();
        if ($wallet->availablePaidBalance() >= 30000) {
            $refundAt = now()->subDays(random_int(1, 25))->setTime(random_int(9, 17), random_int(0, 59));
            $refundAmount = min(random_int(1, 3) * 10000, $wallet->availablePaidBalance());
            $refundStatus = collect([
                HospitalWalletOperation::STATUS_PENDING,
                HospitalWalletOperation::STATUS_COMPLETED,
                HospitalWalletOperation::STATUS_REJECTED,
            ])->random();
            $refundFactory = (match ($refundStatus) {
                HospitalWalletOperation::STATUS_COMPLETED => HospitalWalletOperationFactory::new()
                    ->refundCompleted($wallet, $refundAmount),
                HospitalWalletOperation::STATUS_REJECTED => HospitalWalletOperationFactory::new()
                    ->refundRejected($wallet, $refundAmount),
                default => HospitalWalletOperationFactory::new()->refundPending($wallet, $refundAmount),
            })->withRefundSeedMedia();
            $refundActor = $staff;
            if ($refundStatus === HospitalWalletOperation::STATUS_PENDING && $staff instanceof AccountStaff) {
                $refundFactory = $refundFactory->requestedBy($staff);
                $refundActor = null;
            }

            $this->create(
                $refundFactory,
                $refundAt,
                '병의원 요청에 따른 충전금 환불',
                $refundActor,
                $refundStatus,
            );
        }
    }

    private function create(
        HospitalWalletOperationFactory $factory,
        Carbon $occurredAt,
        string $reason,
        ?AccountStaff $staff = null,
        string $status = HospitalWalletOperation::STATUS_COMPLETED,
    ): void {
        if ($staff instanceof AccountStaff) {
            $factory = $factory->performedBy($staff);
        }

        $timestamps = match ($status) {
            HospitalWalletOperation::STATUS_COMPLETED => ['processed_at' => $occurredAt],
            HospitalWalletOperation::STATUS_REJECTED => ['processed_at' => $occurredAt],
            HospitalWalletOperation::STATUS_CANCELED => ['canceled_at' => $occurredAt],
            default => [],
        };

        $factory->create([
            'reason' => $reason,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
            ...$timestamps,
        ]);
    }
}
