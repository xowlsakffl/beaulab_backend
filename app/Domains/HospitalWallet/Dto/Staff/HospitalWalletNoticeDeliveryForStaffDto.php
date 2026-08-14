<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Dto\Staff;

use App\Domains\Common\Sms\Models\SmsDelivery;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalWallet\Support\HospitalWalletSms;

final readonly class HospitalWalletNoticeDeliveryForStaffDto
{
    public function __construct(private SmsDelivery $delivery) {}

    public static function fromModel(SmsDelivery $delivery): self
    {
        return new self($delivery);
    }

    public function toArray(): array
    {
        $recipientKinds = collect($this->delivery->recipient_kinds ?? [])
            ->filter(static fn ($kind): bool => is_string($kind))
            ->values();

        return [
            'id' => (int) $this->delivery->id,
            'hospital' => $this->hospital(),
            'recipient_kinds' => $recipientKinds->all(),
            'recipient_kind_labels' => $recipientKinds
                ->map(static fn (string $kind): string => HospitalWalletSms::recipientKindLabel($kind))
                ->all(),
            'phone' => $this->delivery->phone,
            'message_type' => $this->delivery->message_type,
            'message_body' => $this->delivery->message_body,
            'byte_length' => $this->delivery->byte_length,
            'status' => (string) $this->delivery->status,
            'status_label' => SmsDelivery::statusLabel((string) $this->delivery->status),
            'provider' => $this->delivery->provider,
            'provider_message_id' => $this->delivery->provider_message_id,
            'attempt_count' => (int) $this->delivery->attempt_count,
            'queued_at' => $this->delivery->queued_at?->toISOString(),
            'attempted_at' => $this->delivery->attempted_at?->toISOString(),
            'sent_at' => $this->delivery->sent_at?->toISOString(),
            'failed_at' => $this->delivery->failed_at?->toISOString(),
            'error_message' => $this->delivery->error_message,
        ];
    }

    private function hospital(): ?array
    {
        if ($this->delivery->relationLoaded('reference') && $this->delivery->reference instanceof Hospital) {
            return [
                'id' => (int) $this->delivery->reference->id,
                'name' => (string) $this->delivery->reference->name,
            ];
        }

        if ($this->delivery->reference_id === null) {
            return null;
        }

        return [
            'id' => (int) $this->delivery->reference_id,
            'name' => (string) ($this->delivery->reference_label ?: '-'),
        ];
    }
}
