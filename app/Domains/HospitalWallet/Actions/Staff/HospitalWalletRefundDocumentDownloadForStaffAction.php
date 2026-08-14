<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletRefund;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletRefundForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class HospitalWalletRefundDocumentDownloadForStaffAction
{
    public function __construct(private readonly HospitalWalletRefundForStaffQuery $query) {}

    public function execute(HospitalWalletOperation $operation, string $document): StreamedResponse
    {
        Gate::authorize('viewHistory', HospitalWallet::class);

        $operation = $this->query->loadDetail($operation);
        if ($operation->type !== HospitalWalletOperation::TYPE_REFUND || ! $operation->refund) {
            throw new CustomException(ErrorCode::NOT_FOUND, '환불 첨부서류를 찾을 수 없습니다.');
        }

        $media = match ($document) {
            HospitalWalletRefund::DOCUMENT_BUSINESS_REGISTRATION => $operation->refund->businessRegistrationFile,
            HospitalWalletRefund::DOCUMENT_BANKBOOK => $operation->refund->bankbookFile,
            default => null,
        };

        if (! $media || ! Storage::disk((string) $media->disk)->exists((string) $media->path)) {
            throw new CustomException(ErrorCode::NOT_FOUND, '첨부서류 파일을 찾을 수 없습니다.');
        }

        $originalName = $media->metadata['original_name'] ?? null;
        $fileName = is_string($originalName) && trim($originalName) !== ''
            ? trim($originalName)
            : basename((string) $media->path);

        return Storage::disk((string) $media->disk)->download((string) $media->path, $fileName);
    }
}
