<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Dto\Staff;

use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletRefund;

final readonly class HospitalWalletRefundDocumentsForStaffDto
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
            'operation_id' => (int) $this->operation->id,
            'status' => (string) $this->operation->status,
            'hospital' => $hospital ? [
                'id' => (int) $hospital->id,
                'name' => (string) $hospital->name,
            ] : null,
            'business_registration_file' => self::media(
                $refund?->businessRegistrationFile,
                (int) $this->operation->id,
                HospitalWalletRefund::DOCUMENT_BUSINESS_REGISTRATION,
            ),
            'bankbook_file' => self::media(
                $refund?->bankbookFile,
                (int) $this->operation->id,
                HospitalWalletRefund::DOCUMENT_BANKBOOK,
            ),
        ];
    }

    private static function media(?Media $media, int $operationId, string $document): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'path' => $media->path,
            'mime_type' => $media->mime_type,
            'size' => (int) $media->size,
            'metadata' => $media->metadata,
            'download_path' => "/hospital-wallet-operations/{$operationId}/refund-documents/{$document}/download",
        ];
    }
}
