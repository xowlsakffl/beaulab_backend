<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalWallet;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletListForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletServicePointGrantForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletServicePointReclaimForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletServicePointUpdateForStaffRequest;

final class HospitalWalletForStaffController extends Controller
{
    public function getHospitalWalletsForStaff(
        HospitalWalletListForStaffRequest $request,
        HospitalWalletListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function grantHospitalWalletServicePointsForStaff(
        HospitalWalletServicePointUpdateForStaffRequest $request,
        HospitalWalletServicePointGrantForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function reclaimHospitalWalletServicePointsForStaff(
        HospitalWalletServicePointUpdateForStaffRequest $request,
        HospitalWalletServicePointReclaimForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }
}
