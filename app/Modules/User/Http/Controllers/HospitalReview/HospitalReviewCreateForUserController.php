<?php

namespace App\Modules\User\Http\Controllers\HospitalReview;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalReview\Actions\User\HospitalReviewCreateForUserAction;
use App\Modules\User\Http\Requests\HospitalReview\HospitalReviewCreateForUserRequest;

final class HospitalReviewCreateForUserController extends Controller
{
    public function createHospitalReviewForUser(HospitalReviewCreateForUserRequest $request, HospitalReviewCreateForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($user, $request->validated());

        return ApiResponse::success($result['hospital_review'] ?? $result);
    }
}
