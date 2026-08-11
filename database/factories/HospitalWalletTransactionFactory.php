<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletTransaction;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @extends Factory<HospitalWalletTransaction>
 */
final class HospitalWalletTransactionFactory extends Factory
{
    protected $model = HospitalWalletTransaction::class;

    public function configure(): static
    {
        return $this->afterCreating(function (HospitalWalletTransaction $transaction): void {
            $this->createEntries($transaction);

            $transaction->wallet()->update([
                'paid_balance' => $transaction->paid_balance_after,
                'service_balance' => $transaction->service_balance_after,
                'last_transaction_at' => $transaction->created_at,
            ]);
        });
    }

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(10, 500) * 1000;

        return [
            'hospital_wallet_id' => HospitalWallet::factory(),
            'type' => HospitalWalletTransaction::TYPE_CHARGE,
            'amount' => $amount,
            'paid_balance_before' => 0,
            'paid_balance_after' => $amount,
            'service_balance_before' => 0,
            'service_balance_after' => 0,
            'batch_uuid' => (string) Str::uuid(),
            'idempotency_key' => 'seed:'.Str::uuid(),
            'actor_type' => null,
            'actor_id' => null,
            'actor_kind' => OperationHistory::ACTOR_KIND_SYSTEM,
            'reference_type' => null,
            'reference_id' => null,
            'reversed_transaction_id' => null,
            'reason' => '시드 충전금 거래',
            'metadata' => null,
        ];
    }

    public function charge(HospitalWallet $wallet, int $amount): self
    {
        $this->assertPositiveAmount($amount);
        $wallet->refresh();

        return $this->state($this->transactionState(
            $wallet,
            HospitalWalletTransaction::TYPE_CHARGE,
            $amount,
            paidAfter: (int) $wallet->paid_balance + $amount,
            serviceAfter: (int) $wallet->service_balance,
        ));
    }

    public function serviceGrant(HospitalWallet $wallet, int $amount): self
    {
        $this->assertPositiveAmount($amount);
        $wallet->refresh();

        return $this->state($this->transactionState(
            $wallet,
            HospitalWalletTransaction::TYPE_SERVICE_GRANT,
            $amount,
            paidAfter: (int) $wallet->paid_balance,
            serviceAfter: (int) $wallet->service_balance + $amount,
        ));
    }

    public function serviceReclaim(HospitalWallet $wallet, int $amount): self
    {
        $this->assertPositiveAmount($amount);
        $wallet->refresh();

        if ($amount > (int) $wallet->service_balance) {
            throw new InvalidArgumentException('Service reclaim amount exceeds the wallet balance.');
        }

        return $this->state($this->transactionState(
            $wallet,
            HospitalWalletTransaction::TYPE_SERVICE_RECLAIM,
            $amount,
            paidAfter: (int) $wallet->paid_balance,
            serviceAfter: (int) $wallet->service_balance - $amount,
        ));
    }

    public function usage(HospitalWallet $wallet, int $amount): self
    {
        $this->assertPositiveAmount($amount);
        $wallet->refresh();

        if ($amount > $wallet->totalBalance()) {
            throw new InvalidArgumentException('Usage amount exceeds the wallet balance.');
        }

        $serviceUsage = min((int) $wallet->service_balance, $amount);
        $paidUsage = $amount - $serviceUsage;

        return $this->state($this->transactionState(
            $wallet,
            HospitalWalletTransaction::TYPE_USAGE,
            $amount,
            paidAfter: (int) $wallet->paid_balance - $paidUsage,
            serviceAfter: (int) $wallet->service_balance - $serviceUsage,
        ));
    }

    public function refund(HospitalWallet $wallet, int $amount): self
    {
        $this->assertPositiveAmount($amount);
        $wallet->refresh();

        if ($amount > (int) $wallet->paid_balance) {
            throw new InvalidArgumentException('Refund amount exceeds the paid balance.');
        }

        return $this->state($this->transactionState(
            $wallet,
            HospitalWalletTransaction::TYPE_REFUND,
            $amount,
            paidAfter: (int) $wallet->paid_balance - $amount,
            serviceAfter: (int) $wallet->service_balance,
        ));
    }

    public function performedBy(AccountStaff $staff): self
    {
        return $this->state(fn (): array => [
            'actor_type' => $staff::class,
            'actor_id' => $staff->getKey(),
            'actor_kind' => OperationHistory::ACTOR_KIND_STAFF,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionState(
        HospitalWallet $wallet,
        string $type,
        int $amount,
        int $paidAfter,
        int $serviceAfter,
    ): array {
        return [
            'hospital_wallet_id' => $wallet->getKey(),
            'type' => $type,
            'amount' => $amount,
            'paid_balance_before' => (int) $wallet->paid_balance,
            'paid_balance_after' => $paidAfter,
            'service_balance_before' => (int) $wallet->service_balance,
            'service_balance_after' => $serviceAfter,
            'batch_uuid' => (string) Str::uuid(),
            'idempotency_key' => 'seed:'.Str::uuid(),
        ];
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Wallet transaction amount must be positive.');
        }
    }

    private function createEntries(HospitalWalletTransaction $transaction): void
    {
        $entries = [];

        $paidDifference = (int) $transaction->paid_balance_after - (int) $transaction->paid_balance_before;
        if ($paidDifference !== 0) {
            $entries[] = $this->entry(
                HospitalWalletTransactionEntry::BALANCE_TYPE_PAID,
                $paidDifference,
            );
        }

        $serviceDifference = (int) $transaction->service_balance_after - (int) $transaction->service_balance_before;
        if ($serviceDifference !== 0) {
            $entries[] = $this->entry(
                HospitalWalletTransactionEntry::BALANCE_TYPE_SERVICE,
                $serviceDifference,
            );
        }

        $transaction->entries()->createMany($entries);
    }

    /**
     * @return array{balance_type: string, direction: string, amount: int}
     */
    private function entry(string $balanceType, int $difference): array
    {
        return [
            'balance_type' => $balanceType,
            'direction' => $difference > 0
                ? HospitalWalletTransactionEntry::DIRECTION_CREDIT
                : HospitalWalletTransactionEntry::DIRECTION_DEBIT,
            'amount' => abs($difference),
        ];
    }
}
