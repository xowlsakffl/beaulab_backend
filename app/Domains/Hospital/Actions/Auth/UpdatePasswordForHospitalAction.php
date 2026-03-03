<?php

namespace App\Domains\Hospital\Actions\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Hospital\Models\AccountHospital;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class UpdatePasswordForHospitalAction
{
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

        Log::info('파트너 비밀번호 변경', [
            'hospital_id' => $hospital->id,
        ]);

        DB::transaction(function () use ($hospital, $filters) {
            $hospital->forceFill(['password' => $filters['password']])->save();
            $hospital->tokens()->delete();
        });

        return [
            'message' => 'Password updated',
        ];
    }
}
