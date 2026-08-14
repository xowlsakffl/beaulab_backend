<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Dto\Staff;

use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;

final readonly class HospitalWalletRefundForStaffDto
{
    public function __construct(private HospitalWalletOperation $operation) {}

    public static function fromModel(HospitalWalletOperation $operation): self
    {
        return new self($operation);
    }

    public function toArray(): array
    {
        $refund = $this->operation->refund;
        $hospital = $this->operation->wallet?->hospital;

        return [
            'id' => $refund ? (int) $refund->id : null,
            'operation_id' => (int) $this->operation->id,
            'hospital' => $hospital ? [
                'id' => (int) $hospital->id,
                'name' => (string) $hospital->name,
            ] : null,
            'status' => (string) $this->operation->status,
            'status_label' => HospitalWalletOperation::statusLabel(
                (string) $this->operation->type,
                (string) $this->operation->status,
            ),
            'points' => (int) $this->operation->amount,
            'supply_amount' => $refund ? (int) $refund->supply_amount : null,
            'vat_amount' => $refund ? (int) $refund->vat_amount : null,
            'refund_amount' => $refund ? (int) $refund->refund_amount : null,
            'reason' => $this->operation->reason,
            'bank_name' => $refund?->bank_name,
            'account_number' => $refund?->account_number,
            'rejection_reason' => $refund?->rejection_reason,
            'business_registration_file' => self::media($refund?->businessRegistrationFile),
            'bankbook_file' => self::media($refund?->bankbookFile),
            'created_at' => $this->operation->created_at?->toISOString(),
            'processed_at' => $this->operation->processed_at?->toISOString(),
        ];
    }

    private static function media(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'collection' => $media->collection,
            'disk' => $media->disk,
            'path' => $media->path,
            'mime_type' => $media->mime_type,
            'size' => (int) $media->size,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
        ];
    }
}
