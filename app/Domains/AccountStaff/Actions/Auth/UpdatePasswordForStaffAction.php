<?php

namespace App\Domains\Staff\Actions\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Staff\Models\AccountStaff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class UpdatePasswordForStaffAction
{
    /**
     * @param array{current_password:string,password:string} $filters
     * @return array{message:string}
     */
    public function execute(AccountStaff $staff, array $filters): array
    {
        if (!Hash::check($filters['current_password'], $staff->password)) {
            throw new CustomException(
                errorCode: ErrorCode::INVALID_REQUEST,
                message: '현재 비밀번호가 올바르지 않습니다.',
                details: ['field' => 'current_password']
            );
        }

        Log::info('뷰랩 직원 비밀번호 변경', [
            'staff_id' => $staff->id,
        ]);

        DB::transaction(function () use ($staff, $filters) {
            $staff->forceFill(['password' => $filters['password']])->save();

            // 보안: 비밀번호 변경 시 모든 토큰 만료
            $staff->tokens()->delete();
        });

        return [
            'message' => 'Password updated',
        ];
    }
}
