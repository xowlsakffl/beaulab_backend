<?php

namespace App\Domains\Partner\Actions\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Partner\Models\AccountPartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class UpdatePasswordForPartnerAction
{
    /**
     * @param array{current_password:string,password:string} $filters
     * @return array{message:string}
     */
    public function execute(AccountPartner $partner, array $filters): array
    {
        if (! Hash::check($filters['current_password'], $partner->password)) {
            throw new CustomException(
                errorCode: ErrorCode::INVALID_REQUEST,
                message: '현재 비밀번호가 올바르지 않습니다.',
                details: ['field' => 'current_password']
            );
        }

        Log::info('파트너 비밀번호 변경', [
            'partner_id' => $partner->id,
        ]);

        DB::transaction(function () use ($partner, $filters) {
            $partner->forceFill(['password' => $filters['password']])->save();
            $partner->tokens()->delete();
        });

        return [
            'message' => 'Password updated',
        ];
    }
}
