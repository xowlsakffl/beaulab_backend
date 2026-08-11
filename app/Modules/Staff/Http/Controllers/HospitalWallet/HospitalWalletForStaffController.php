<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalWallet;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletListForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletListForStaffRequest;

final class HospitalWalletForStaffController extends Controller
{
    public function getHospitalWalletsForStaff(
        HospitalWalletListForStaffRequest $request,
        HospitalWalletListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
