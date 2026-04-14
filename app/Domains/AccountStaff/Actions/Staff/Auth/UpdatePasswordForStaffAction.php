<?php

namespace App\Domains\AccountStaff\Actions\Staff\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Queries\Staff\Auth\UpdatePasswordForStaffQuery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * 스태프 비밀번호 변경 유스케이스.
 * 현재 비밀번호를 검증한 뒤 저장과 기존 토큰 만료를 Query에 위임한다.
 */
final class UpdatePasswordForStaffAction
{
    public function __construct(
        private readonly UpdatePasswordForStaffQuery $query,
    ) {}

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

        $this->query->update($staff, (string) $filters['password']);

        return [
            'message' => 'Password updated',
        ];
    }
}
