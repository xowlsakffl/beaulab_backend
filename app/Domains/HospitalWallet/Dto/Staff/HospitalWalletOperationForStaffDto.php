<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Dto\Staff;

use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use Illuminate\Database\Eloquent\Model;

final readonly class HospitalWalletOperationForStaffDto
{
    public function __construct(private HospitalWalletOperation $operation) {}

    public static function fromModel(HospitalWalletOperation $operation): self
    {
        return new self($operation);
    }

    public function toArray(): array
    {
        $transaction = $this->operation->transaction;
        $actor = $this->actor();

        return [
            'id' => (int) $this->operation->id,
            'transaction_id' => $transaction ? (int) $transaction->id : null,
            'hospital' => $this->hospital(),
            'type' => (string) $this->operation->type,
            'type_label' => HospitalWalletOperation::typeLabel((string) $this->operation->type),
            'status' => (string) $this->operation->status,
            'status_label' => HospitalWalletOperation::statusLabel(
                (string) $this->operation->type,
                (string) $this->operation->status,
            ),
            'direction' => $this->operation->isCredit()
                ? HospitalWalletTransactionEntry::DIRECTION_CREDIT
                : HospitalWalletTransactionEntry::DIRECTION_DEBIT,
            'amount' => (int) $this->operation->amount,
            'signed_amount' => $this->operation->isCredit()
                ? (int) $this->operation->amount
                : -(int) $this->operation->amount,
            'balance_changes' => $transaction ? [
                'total' => $transaction->totalBalanceAfter() - $transaction->totalBalanceBefore(),
                'paid' => (int) $transaction->paid_balance_after - (int) $transaction->paid_balance_before,
                'service' => (int) $transaction->service_balance_after - (int) $transaction->service_balance_before,
            ] : null,
            'reference' => $this->reference(),
            'reason' => $this->operation->reason,
            'actor' => $actor,
            'actor_label' => $this->actorLabel($actor),
            'payment' => $this->payment(),
            'refund' => $this->refund(),
            'created_at' => $this->operation->created_at?->toISOString(),
            'processed_at' => $this->operation->processed_at?->toISOString(),
        ];
    }

    private function hospital(): ?array
    {
        $hospital = $this->operation->wallet?->hospital;
        if (! $hospital) {
            return null;
        }

        return [
            'id' => (int) $hospital->id,
            'name' => (string) $hospital->name,
        ];
    }

    private function actor(): ?Model
    {
        if ($this->operation->processor instanceof Model) {
            return $this->operation->processor;
        }

        return $this->operation->requester instanceof Model
            ? $this->operation->requester
            : null;
    }

    private function actorLabel(?Model $actor): string
    {
        $kind = $this->operation->processor_kind ?: $this->operation->requester_kind;
        if ($kind === OperationHistory::ACTOR_KIND_SYSTEM) {
            return '시스템';
        }
        if (! $actor) {
            return '-';
        }

        $name = trim((string) ($actor->name ?? $actor->nickname ?? ''));

        return $name !== '' ? $name : (string) ($actor->email ?? '-');
    }

    private function reference(): ?array
    {
        if (! $this->operation->reference_type && ! $this->operation->reference_label) {
            return null;
        }

        return [
            'type' => $this->operation->reference_type,
            'id' => $this->operation->reference_id ? (int) $this->operation->reference_id : null,
            'label' => $this->operation->reference_label,
        ];
    }

    private function payment(): ?array
    {
        $payment = $this->operation->payment;
        if (! $payment) {
            return null;
        }

        return [
            'payment_method' => (string) $payment->payment_method,
            'provider' => $payment->provider,
            'depositor_name' => $payment->depositor_name,
            'supply_amount' => (int) $payment->supply_amount,
            'vat_amount' => (int) $payment->vat_amount,
            'payment_amount' => (int) $payment->payment_amount,
            'paid_at' => $payment->paid_at?->toISOString(),
        ];
    }

    private function refund(): ?array
    {
        $refund = $this->operation->refund;
        if (! $refund) {
            return null;
        }

        return [
            'supply_amount' => (int) $refund->supply_amount,
            'vat_amount' => (int) $refund->vat_amount,
            'refund_amount' => (int) $refund->refund_amount,
            'has_business_registration_file' => $refund->businessRegistrationFile !== null,
            'has_bankbook_file' => $refund->bankbookFile !== null,
            'rejection_reason' => $refund->rejection_reason,
        ];
    }
}
