<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletServicePointReclaimForStaffAction
{
    public function __construct(
        private readonly HospitalWalletServicePointProcessForStaffAction $processAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('reclaimService', HospitalWallet::class);

        $actor = auth()->user();
        if (! $actor instanceof AccountStaff) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        return $this->processAction->execute(
            $payload,
            HospitalWalletOperation::TYPE_SERVICE_RECLAIM,
            $actor,
        );
    }
}
