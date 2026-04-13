<?php

namespace App\Domains\AccountUser\Actions\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Auth\UpdatePasswordForAccountUserQuery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class UpdatePasswordForAccountUserAction
{
    public function __construct(
        private readonly UpdatePasswordForAccountUserQuery $query,
    ) {}

    /**
     * @param array{current_password:string,password:string} $filters
     * @return array{message:string}
     */
    public function execute(AccountUser $user, array $filters): array
    {
        if (! Hash::check($filters['current_password'], $user->password)) {
            throw new CustomException(
                errorCode: ErrorCode::INVALID_REQUEST,
                message: '현재 비밀번호가 올바르지 않습니다.',
                details: ['field' => 'current_password']
            );
        }

        Log::info('앱 사용자 비밀번호 변경', [
            'user_id' => $user->id,
        ]);

        $this->query->update($user, (string) $filters['password']);

        return [
            'message' => 'Password updated',
        ];
    }
}
