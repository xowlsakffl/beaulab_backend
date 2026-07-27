<?php

namespace App\Domains\AccountUser\Actions\User\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\User\Auth\PasswordUpdateForAccountUserQuery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * 앱 사용자 비밀번호 변경 유스케이스.
 * 현재 비밀번호 검증 후 새 비밀번호 저장과 기존 토큰 만료를 Query에 위임한다.
 */
final class PasswordUpdateForAccountUserAction
{
    public function __construct(
        private readonly PasswordUpdateForAccountUserQuery $query,
    ) {}

    /**
     * @param array{current_password:string,password:string} $filters
     * @return array{message:string}
     */
    public function execute(AccountUser $user, array $filters): array
    {
        if (! Hash::check($filters['current_password'], $user->password)) {
            Log::warning('앱 사용자 비밀번호 변경 실패', [
                'reason' => 'current_password_mismatch',
                'user_id' => $user->id,
            ]);

            throw new CustomException(
                errorCode: ErrorCode::INVALID_REQUEST,
                message: '현재 비밀번호가 올바르지 않습니다.',
                details: ['field' => 'current_password']
            );
        }

        $this->query->update($user, (string) $filters['password']);

        Log::info('앱 사용자 비밀번호 변경', [
            'user_id' => $user->id,
        ]);

        return [
            'message' => 'Password updated',
        ];
    }
}
