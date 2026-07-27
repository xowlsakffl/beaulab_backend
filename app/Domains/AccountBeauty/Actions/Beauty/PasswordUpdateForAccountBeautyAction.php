<?php

namespace App\Domains\AccountBeauty\Actions\Beauty;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountBeauty\Queries\Beauty\PasswordUpdateForAccountBeautyQuery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * 뷰티 계정 비밀번호 변경 유스케이스.
 * 현재 비밀번호를 검증한 뒤 저장과 기존 토큰 만료를 Query에 위임한다.
 */
final class PasswordUpdateForAccountBeautyAction
{
    public function __construct(
        private readonly PasswordUpdateForAccountBeautyQuery $query,
    ) {}

    /**
     * @param array{current_password:string,password:string} $filters
     * @return array{message:string}
     */
    public function execute(AccountBeauty $beauty, array $filters): array
    {
        if (! Hash::check($filters['current_password'], $beauty->password)) {
            Log::warning('뷰티 비밀번호 변경 실패', [
                'reason' => 'current_password_mismatch',
                'beauty_id' => $beauty->id,
            ]);

            throw new CustomException(
                errorCode: ErrorCode::INVALID_REQUEST,
                message: '현재 비밀번호가 올바르지 않습니다.',
                details: ['field' => 'current_password']
            );
        }

        $this->query->update($beauty, (string) $filters['password']);

        Log::info('뷰티 비밀번호 변경', [
            'beauty_id' => $beauty->id,
        ]);

        return [
            'message' => '비밀번호가 변경되었습니다.',
        ];
    }
}
