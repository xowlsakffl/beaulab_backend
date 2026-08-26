<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Dto\Staff;

use App\Common\Support\ActorDisplay;
use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\Common\Sms\Models\SmsDelivery;
use Illuminate\Database\Eloquent\Model;

final readonly class HospitalWalletNoticeBatchForStaffDto
{
    public function __construct(private SmsBatch $batch) {}

    public static function fromModel(SmsBatch $batch): self
    {
        return new self($batch);
    }

    public function toArray(): array
    {
        $metadata = is_array($this->batch->metadata) ? $this->batch->metadata : [];

        $data = [
            'id' => (int) $this->batch->id,
            'status' => (string) $this->batch->status,
            'status_label' => SmsBatch::statusLabel((string) $this->batch->status),
            'message_template' => (string) $this->batch->message_template,
            'hospital_ids' => collect($metadata['hospital_ids'] ?? [])
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all(),
            'send_to_manager' => (bool) ($metadata['send_to_manager'] ?? false),
            'send_to_representative' => (bool) ($metadata['send_to_representative'] ?? false),
            'hospital_count' => (int) $this->batch->target_count,
            'recipient_count' => (int) $this->batch->recipient_count,
            'sent_count' => (int) $this->batch->sent_count,
            'failed_count' => (int) $this->batch->failed_count,
            'skipped_count' => (int) $this->batch->skipped_count,
            'actor' => $this->actor(),
            'queued_at' => $this->batch->queued_at?->toISOString(),
            'completed_at' => $this->batch->completed_at?->toISOString(),
            'created_at' => $this->batch->created_at?->toISOString(),
        ];

        if ($this->batch->relationLoaded('deliveries')) {
            $data['deliveries'] = $this->batch->deliveries
                ->map(static fn (SmsDelivery $delivery): array => HospitalWalletNoticeDeliveryForStaffDto::fromModel($delivery)->toArray())
                ->values()
                ->all();
        }

        return $data;
    }

    private function actor(): ?array
    {
        if (! $this->batch->relationLoaded('actor') || ! $this->batch->actor instanceof Model) {
            return null;
        }

        return ActorDisplay::toArray($this->batch->actor);
    }
}
