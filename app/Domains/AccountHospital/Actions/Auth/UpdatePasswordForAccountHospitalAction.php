<?php

namespace App\Domains\AccountHospital\Actions\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Queries\Auth\UpdatePasswordForAccountHospitalQuery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * 병원 계정 비밀번호 변경 유스케이스.
 * 현재 비밀번호를 검증한 뒤 저장과 기존 토큰 만료를 Query에 위임한다.
 */
final class UpdatePasswordForAccountHospitalAction
{
    public function __construct(
        private readonly UpdatePasswordForAccountHospitalQuery $query,
    ) {}

    /**
     * @param array{current_password:string,password:string} $filters
     * @return array{message:string}
     */
    public function execute(AccountHospital $hospital, array $filters): array
    {
        if (! Hash::check($filters['current_password'], $hospital->password)) {
            throw new CustomException(
                errorCode: ErrorCode::INVALID_REQUEST,
                message: '현재 비밀번호가 올바르지 않습니다.',
                details: ['field' => 'current_password']
            );
        }

        Log::info('병원 비밀번호 변경', [
            'hospital_id' => $hospital->id,
        ]);

        $this->query->update($hospital, (string) $filters['password']);

        return [
            'message' => 'Password updated',
        ];
    }
}
