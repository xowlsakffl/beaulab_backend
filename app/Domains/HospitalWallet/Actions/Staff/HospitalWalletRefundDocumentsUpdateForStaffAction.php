<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletRefundDocumentsForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletRefund;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletRefundForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletRefundDocumentsUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalWalletRefundForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAction,
    ) {}

    public function execute(HospitalWalletOperation $operation, array $payload): array
    {
        Gate::authorize('updateRefundDocuments', HospitalWallet::class);

        $operation = DB::transaction(function () use ($operation, $payload): HospitalWalletOperation {
            $locked = $this->query->operationForUpdate((int) $operation->id);
            if (! $locked || $locked->type !== HospitalWalletOperation::TYPE_REFUND) {
                throw new CustomException(ErrorCode::NOT_FOUND, '환불 첨부서류를 찾을 수 없습니다.');
            }
            if ($locked->status !== HospitalWalletOperation::STATUS_PENDING) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '처리 완료된 환불 건의 첨부서류는 변경할 수 없습니다.');
            }

            $refund = $locked->refund()->first();
            if (! $refund) {
                throw new CustomException(ErrorCode::NOT_FOUND, '환불 첨부서류를 찾을 수 없습니다.');
            }

            $this->replaceDocument(
                $refund,
                $payload,
                'business_registration_file',
                'remove_business_registration_file',
                HospitalWalletRefund::COLLECTION_BUSINESS_REGISTRATION_FILE,
                'business-registration-file',
            );
            $this->replaceDocument(
                $refund,
                $payload,
                'bankbook_file',
                'remove_bankbook_file',
                HospitalWalletRefund::COLLECTION_BANKBOOK_FILE,
                'bankbook-file',
            );

            return $this->query->loadDetail($locked);
        });

        return [
            'documents' => HospitalWalletRefundDocumentsForStaffDto::fromModel($operation)->toArray(),
        ];
    }

    private function replaceDocument(
        HospitalWalletRefund $refund,
        array $payload,
        string $fileKey,
        string $removeKey,
        string $collection,
        string $directory,
    ): void {
        $file = $payload[$fileKey] ?? null;
        if ($file instanceof UploadedFile) {
            $this->mediaAction->deleteCollectionMedia($refund, $collection);
            $this->mediaAction->attachOne(
                $refund,
                $file,
                $collection,
                'hospital-wallet-refund',
                $directory,
            );

            return;
        }

        if (filter_var($payload[$removeKey] ?? false, FILTER_VALIDATE_BOOL)) {
            $this->mediaAction->deleteCollectionMedia($refund, $collection);
        }
    }
}
