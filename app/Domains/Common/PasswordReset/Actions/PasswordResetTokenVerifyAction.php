<?php

namespace App\Domains\Common\PasswordReset\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\PasswordReset\Queries\PasswordResetTokenQuery;
use App\Domains\Common\PasswordReset\Support\PasswordResetActor;

final class PasswordResetTokenVerifyAction
{
    public function __construct(
        private readonly PasswordResetTokenQuery $tokenQuery,
    ) {}

    /**
     * @param  array{email:string,token:string}  $payload
     * @return array{valid:bool}
     */
    public function execute(string $actor, array $payload): array
    {
        $email = $payload['email'];
        $token = $payload['token'];
        $account = PasswordResetActor::findAccountByEmail($actor, $email);

        if (! $account || ! PasswordResetActor::canResetPassword($account)) {
            throw $this->invalidTokenException();
        }

        $isValidToken = $this->tokenQuery->valid(
            actor: $actor,
            email: $email,
            token: $token,
            expireMinutes: PasswordResetActor::expireMinutes($actor),
        );

        if (! $isValidToken) {
            throw $this->invalidTokenException();
        }

        return [
            'valid' => true,
        ];
    }

    private function invalidTokenException(): CustomException
    {
        return new CustomException(
            errorCode: ErrorCode::TOKEN_ERROR,
            message: '비밀번호 재설정 링크가 유효하지 않거나 만료되었습니다.',
        );
    }
}
