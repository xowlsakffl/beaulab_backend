<?php

namespace App\Domains\Beauty\Actions\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Beauty\Models\AccountBeauty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class UpdatePasswordForBeautyAction
{
    /**
     * @param array{current_password:string,password:string} $filters
     * @return array{message:string}
     */
    public function execute(AccountBeauty $beauty, array $filters): array
    {
        if (! Hash::check($filters['current_password'], $beauty->password)) {
            throw new CustomException(
                errorCode: ErrorCode::INVALID_REQUEST,
                message: '현재 비밀번호가 올바르지 않습니다.',
                details: ['field' => 'current_password']
            );
        }

        Log::info('파트너 비밀번호 변경', [
            'beauty_id' => $beauty->id,
        ]);

        DB::transaction(function () use ($beauty, $filters) {
            $beauty->forceFill(['password' => $filters['password']])->save();
            $beauty->tokens()->delete();
        });

        return [
            'message' => 'Password updated',
        ];
    }
}
