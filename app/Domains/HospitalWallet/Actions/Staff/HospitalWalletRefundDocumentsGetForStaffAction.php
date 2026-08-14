<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletRefundDocumentsForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletRefundForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletRefundDocumentsGetForStaffAction
{
    public function __construct(private readonly HospitalWalletRefundForStaffQuery $query) {}

    public function execute(HospitalWalletOperation $operation): array
    {
        Gate::authorize('viewHistory', HospitalWallet::class);

        $operation = $this->query->loadDetail($operation);
        if ($operation->type !== HospitalWalletOperation::TYPE_REFUND || ! $operation->refund) {
            throw new CustomException(ErrorCode::NOT_FOUND, '환불 첨부서류를 찾을 수 없습니다.');
        }

        return [
            'documents' => HospitalWalletRefundDocumentsForStaffDto::fromModel($operation)->toArray(),
        ];
    }
}
