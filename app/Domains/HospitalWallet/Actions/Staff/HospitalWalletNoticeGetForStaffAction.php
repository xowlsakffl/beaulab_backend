<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletNoticeBatchForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Support\HospitalWalletSms;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletNoticeGetForStaffAction
{
    public function execute(SmsBatch $batch): array
    {
        Gate::authorize('viewNotices', HospitalWallet::class);

        if ($batch->purpose !== HospitalWalletSms::PURPOSE_BALANCE_NOTICE) {
            throw new CustomException(ErrorCode::NOT_FOUND);
        }

        return HospitalWalletNoticeBatchForStaffDto::fromModel(
            $batch->load(['actor', 'deliveries.reference']),
        )->toArray();
    }
}
