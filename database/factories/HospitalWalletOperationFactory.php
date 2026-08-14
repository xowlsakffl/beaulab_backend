<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletPayment;
use App\Domains\HospitalWallet\Models\HospitalWalletRefund;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @extends Factory<HospitalWalletOperation>
 */
final class HospitalWalletOperationFactory extends Factory
{
    protected $model = HospitalWalletOperation::class;

    public function configure(): static
    {
        return $this->afterCreating(function (HospitalWalletOperation $operation): void {
            $this->createChargePayment($operation);
            $this->createRefundDetail($operation);

            if ($operation->status === HospitalWalletOperation::STATUS_COMPLETED) {
                $this->applyCompletedOperation($operation);
            } elseif ($operation->type === HospitalWalletOperation::TYPE_REFUND
                && $operation->status === HospitalWalletOperation::STATUS_PENDING) {
                $this->reservePendingRefund($operation);
            }
        });
    }

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(10, 500) * 1000;

        return [
            'hospital_wallet_id' => HospitalWallet::factory(),
            'batch_uuid' => (string) Str::uuid(),
            'type' => HospitalWalletOperation::TYPE_CHARGE,
            'status' => HospitalWalletOperation::STATUS_PENDING,
            'amount' => $amount,
            'idempotency_key' => 'seed:'.Str::uuid(),
            'requester_type' => null,
            'requester_id' => null,
            'requester_kind' => OperationHistory::ACTOR_KIND_SYSTEM,
            'processor_type' => null,
            'processor_id' => null,
            'processor_kind' => null,
            'reference_type' => null,
            'reference_id' => null,
            'reference_label' => null,
            'reason' => null,
            'processed_at' => null,
            'canceled_at' => null,
            'failed_at' => null,
            'metadata' => null,
        ];
    }

    public function forWallet(HospitalWallet $wallet): self
    {
        return $this->state(fn (): array => [
            'hospital_wallet_id' => $wallet->getKey(),
        ]);
    }

    public function chargePending(HospitalWallet $wallet, int $amount): self
    {
        return $this->operation($wallet, HospitalWalletOperation::TYPE_CHARGE, HospitalWalletOperation::STATUS_PENDING, $amount);
    }

    public function chargeCompleted(HospitalWallet $wallet, int $amount): self
    {
        return $this->operation($wallet, HospitalWalletOperation::TYPE_CHARGE, HospitalWalletOperation::STATUS_COMPLETED, $amount);
    }

    public function chargeCanceled(HospitalWallet $wallet, int $amount): self
    {
        return $this->operation($wallet, HospitalWalletOperation::TYPE_CHARGE, HospitalWalletOperation::STATUS_CANCELED, $amount);
    }

    public function serviceGrant(HospitalWallet $wallet, int $amount): self
    {
        return $this->operation($wallet, HospitalWalletOperation::TYPE_SERVICE_GRANT, HospitalWalletOperation::STATUS_COMPLETED, $amount);
    }

    public function serviceReclaim(HospitalWallet $wallet, int $amount): self
    {
        if ($amount > (int) $wallet->fresh()->service_balance) {
            throw new InvalidArgumentException('Service reclaim amount exceeds the wallet balance.');
        }

        return $this->operation($wallet, HospitalWalletOperation::TYPE_SERVICE_RECLAIM, HospitalWalletOperation::STATUS_COMPLETED, $amount);
    }

    public function usage(HospitalWallet $wallet, int $amount, string $referenceLabel): self
    {
        if ($amount > $wallet->fresh()->availableTotalBalance()) {
            throw new InvalidArgumentException('Usage amount exceeds the available wallet balance.');
        }

        return $this->operation($wallet, HospitalWalletOperation::TYPE_USAGE, HospitalWalletOperation::STATUS_COMPLETED, $amount)
            ->state(fn (): array => ['reference_label' => $referenceLabel]);
    }

    public function refundCompleted(HospitalWallet $wallet, int $amount): self
    {
        if ($amount > $wallet->fresh()->availablePaidBalance()) {
            throw new InvalidArgumentException('Refund amount exceeds the available paid balance.');
        }

        return $this->operation($wallet, HospitalWalletOperation::TYPE_REFUND, HospitalWalletOperation::STATUS_COMPLETED, $amount);
    }

    public function refundPending(HospitalWallet $wallet, int $amount): self
    {
        if ($amount > $wallet->fresh()->availablePaidBalance()) {
            throw new InvalidArgumentException('Refund amount exceeds the available paid balance.');
        }

        return $this->operation($wallet, HospitalWalletOperation::TYPE_REFUND, HospitalWalletOperation::STATUS_PENDING, $amount);
    }

    public function refundRejected(HospitalWallet $wallet, int $amount): self
    {
        return $this->operation($wallet, HospitalWalletOperation::TYPE_REFUND, HospitalWalletOperation::STATUS_REJECTED, $amount);
    }

    public function performedBy(AccountStaff $staff): self
    {
        return $this->state(fn (): array => [
            'requester_type' => $staff->getMorphClass(),
            'requester_id' => $staff->getKey(),
            'requester_kind' => OperationHistory::ACTOR_KIND_STAFF,
            'processor_type' => $staff->getMorphClass(),
            'processor_id' => $staff->getKey(),
            'processor_kind' => OperationHistory::ACTOR_KIND_STAFF,
        ]);
    }

    public function requestedBy(AccountStaff $staff): self
    {
        return $this->state(fn (): array => [
            'requester_type' => $staff->getMorphClass(),
            'requester_id' => $staff->getKey(),
            'requester_kind' => OperationHistory::ACTOR_KIND_STAFF,
            'processor_type' => null,
            'processor_id' => null,
            'processor_kind' => null,
        ]);
    }

    public function withRefundSeedMedia(): self
    {
        return $this->afterCreating(function (HospitalWalletOperation $operation): void {
            if ($operation->type !== HospitalWalletOperation::TYPE_REFUND || ! $operation->refund) {
                return;
            }

            $mediaAction = app(MediaAttachDeleteAction::class);
            $mediaAction->attachOne(
                $operation->refund,
                SeedMediaFactory::image("hospital-wallet-refund-business-{$operation->id}"),
                HospitalWalletRefund::COLLECTION_BUSINESS_REGISTRATION_FILE,
                'hospital-wallet-refund',
                'business-registration-file',
            );
            $mediaAction->attachOne(
                $operation->refund,
                SeedMediaFactory::image("hospital-wallet-refund-bankbook-{$operation->id}"),
                HospitalWalletRefund::COLLECTION_BANKBOOK_FILE,
                'hospital-wallet-refund',
                'bankbook-file',
            );
        });
    }

    private function operation(HospitalWallet $wallet, string $type, string $status, int $amount): self
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Wallet operation amount must be positive.');
        }

        $processedAt = in_array($status, [
            HospitalWalletOperation::STATUS_COMPLETED,
            HospitalWalletOperation::STATUS_REJECTED,
        ], true) ? now() : null;
        $canceledAt = $status === HospitalWalletOperation::STATUS_CANCELED ? now() : null;

        return $this->state(fn (): array => [
            'hospital_wallet_id' => $wallet->getKey(),
            'batch_uuid' => (string) Str::uuid(),
            'type' => $type,
            'status' => $status,
            'amount' => $amount,
            'idempotency_key' => 'seed:'.Str::uuid(),
            'processed_at' => $processedAt,
            'canceled_at' => $canceledAt,
        ]);
    }

    private function createChargePayment(HospitalWalletOperation $operation): void
    {
        if ($operation->type !== HospitalWalletOperation::TYPE_CHARGE || $operation->payment()->exists()) {
            return;
        }

        $supplyAmount = (int) $operation->amount;
        $vatAmount = (int) round($supplyAmount * 0.1);

        $payment = HospitalWalletPayment::query()->create([
            'hospital_wallet_operation_id' => $operation->getKey(),
            'payment_method' => HospitalWalletPayment::METHOD_VIRTUAL_ACCOUNT,
            'provider' => 'SEED',
            'provider_transaction_id' => 'seed-payment-'.$operation->getKey(),
            'depositor_name' => $operation->wallet?->hospital?->name,
            'supply_amount' => $supplyAmount,
            'vat_amount' => $vatAmount,
            'payment_amount' => $supplyAmount + $vatAmount,
            'virtual_account_bank' => '테스트은행',
            'virtual_account_number' => (string) $this->faker->numerify('##########'),
            'virtual_account_expires_at' => $operation->created_at?->copy()->addDays(7),
            'paid_at' => $operation->status === HospitalWalletOperation::STATUS_COMPLETED
                ? $operation->processed_at
                : null,
        ]);
        $payment->forceFill([
            'created_at' => $operation->created_at,
            'updated_at' => $operation->updated_at,
        ])->saveQuietly();
    }

    private function createRefundDetail(HospitalWalletOperation $operation): void
    {
        if ($operation->type !== HospitalWalletOperation::TYPE_REFUND || $operation->refund()->exists()) {
            return;
        }

        $supplyAmount = (int) $operation->amount;
        $vatAmount = (int) round($supplyAmount * 0.1);

        HospitalWalletRefund::query()->create([
            'hospital_wallet_operation_id' => $operation->getKey(),
            'supply_amount' => $supplyAmount,
            'vat_amount' => $vatAmount,
            'refund_amount' => $supplyAmount + $vatAmount,
            'bank_name' => '테스트은행',
            'account_number' => (string) $this->faker->numerify('############'),
            'rejection_reason' => $operation->status === HospitalWalletOperation::STATUS_REJECTED
                ? '환불 서류 확인이 필요합니다.'
                : null,
        ]);
    }

    private function reservePendingRefund(HospitalWalletOperation $operation): void
    {
        $wallet = HospitalWallet::query()->findOrFail($operation->hospital_wallet_id);
        $wallet->increment('reserved_paid_balance', (int) $operation->amount);
    }

    private function applyCompletedOperation(HospitalWalletOperation $operation): void
    {
        if ($operation->transaction()->exists()) {
            return;
        }

        $wallet = HospitalWallet::query()->findOrFail($operation->hospital_wallet_id);
        $amount = (int) $operation->amount;
        $paidBefore = (int) $wallet->paid_balance;
        $reservedPaidBefore = (int) $wallet->reserved_paid_balance;
        $serviceBefore = (int) $wallet->service_balance;
        [$paidAfter, $reservedPaidAfter, $serviceAfter] = $this->balancesAfter(
            $operation,
            $paidBefore,
            $reservedPaidBefore,
            $serviceBefore,
        );

        $transaction = $operation->transaction()->make([
            'hospital_wallet_id' => $wallet->getKey(),
            'amount' => $amount,
            'paid_balance_before' => $paidBefore,
            'paid_balance_after' => $paidAfter,
            'reserved_paid_balance_before' => $reservedPaidBefore,
            'reserved_paid_balance_after' => $reservedPaidAfter,
            'service_balance_before' => $serviceBefore,
            'service_balance_after' => $serviceAfter,
        ]);
        $transaction->created_at = $operation->processed_at ?? $operation->created_at;
        $transaction->updated_at = $transaction->created_at;
        $transaction->save();

        $entries = [];
        if ($paidAfter !== $paidBefore) {
            $entries[] = $this->entry(HospitalWalletTransactionEntry::BALANCE_TYPE_PAID, $paidAfter - $paidBefore);
        }
        if ($serviceAfter !== $serviceBefore) {
            $entries[] = $this->entry(HospitalWalletTransactionEntry::BALANCE_TYPE_SERVICE, $serviceAfter - $serviceBefore);
        }
        $transaction->entries()->createMany($entries);

        $wallet->update([
            'paid_balance' => $paidAfter,
            'reserved_paid_balance' => $reservedPaidAfter,
            'service_balance' => $serviceAfter,
            'last_transaction_at' => $operation->processed_at ?? $operation->created_at,
        ]);
    }

    private function balancesAfter(
        HospitalWalletOperation $operation,
        int $paid,
        int $reservedPaid,
        int $service,
    ): array {
        $amount = (int) $operation->amount;

        return match ($operation->type) {
            HospitalWalletOperation::TYPE_CHARGE => [$paid + $amount, $reservedPaid, $service],
            HospitalWalletOperation::TYPE_SERVICE_GRANT => [$paid, $reservedPaid, $service + $amount],
            HospitalWalletOperation::TYPE_SERVICE_RECLAIM => [$paid, $reservedPaid, $service - $amount],
            HospitalWalletOperation::TYPE_USAGE => $this->usageBalances($paid, $reservedPaid, $service, $amount),
            HospitalWalletOperation::TYPE_REFUND => [$paid - $amount, max(0, $reservedPaid - $amount), $service],
            default => throw new InvalidArgumentException('Unsupported completed wallet operation type.'),
        };
    }

    private function usageBalances(int $paid, int $reservedPaid, int $service, int $amount): array
    {
        $serviceUsage = min($service, $amount);
        $paidUsage = $amount - $serviceUsage;

        if ($paidUsage > $paid - $reservedPaid) {
            throw new InvalidArgumentException('Usage amount exceeds the available paid balance.');
        }

        return [$paid - $paidUsage, $reservedPaid, $service - $serviceUsage];
    }

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
