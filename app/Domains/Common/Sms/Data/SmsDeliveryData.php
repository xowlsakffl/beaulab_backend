<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Data;

use Carbon\CarbonInterface;

final readonly class SmsDeliveryData
{
    /**
     * @param  list<string>  $recipientKinds
     */
    public function __construct(
        public string $deduplicationKey,
        public ?string $referenceType,
        public ?int $referenceId,
        public ?string $referenceLabel,
        public ?string $recipientType,
        public ?int $recipientId,
        public array $recipientKinds,
        public ?string $phone,
        public ?string $phoneNormalized,
        public ?string $messageType,
        public ?string $messageBody,
        public ?int $byteLength,
        public string $status,
        public ?CarbonInterface $failedAt = null,
        public ?string $errorMessage = null,
    ) {}

    public function toArray(): array
    {
        return [
            'deduplication_key' => $this->deduplicationKey,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'reference_label' => $this->referenceLabel,
            'recipient_type' => $this->recipientType,
            'recipient_id' => $this->recipientId,
            'recipient_kinds' => $this->recipientKinds,
            'phone' => $this->phone,
            'phone_normalized' => $this->phoneNormalized,
            'message_type' => $this->messageType,
            'message_body' => $this->messageBody,
            'byte_length' => $this->byteLength,
            'status' => $this->status,
            'failed_at' => $this->failedAt,
            'error_message' => $this->errorMessage,
        ];
    }
}
