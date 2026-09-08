<?php

namespace App\Domains\Common\PasswordReset\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\PasswordReset\Queries\PasswordResetTokenQuery;
use App\Domains\Common\PasswordReset\Support\PasswordResetActor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PasswordResetAction
{
    public function __construct(
        private readonly PasswordResetTokenQuery $tokenQuery,
    ) {}

    /**
     * @param  array{email:string,token:string,password:string}  $payload
     * @return array{message:string}
     */
    public function execute(string $actor, array $payload): array
    {
        $email = $payload['email'];
        $token = $payload['token'];
        $password = $payload['password'];

        return DB::transaction(function () use ($actor, $email, $token, $password): array {
            $account = PasswordResetActor::findAccountByEmail($actor, $email, forUpdate: true);

            if (! $account || ! PasswordResetActor::canResetPassword($account)) {
                Log::warning('비밀번호 재설정 실패', [
                    'actor' => $actor,
                    'reason' => 'account_unavailable',
                    'email_hash' => hash('sha256', $email),
                ]);

                throw $this->invalidTokenException();
            }

            $isValidToken = $this->tokenQuery->valid(
                actor: $actor,
                email: $email,
                token: $token,
                expireMinutes: PasswordResetActor::expireMinutes($actor),
                forUpdate: true,
            );

            if (! $isValidToken) {
                Log::warning('비밀번호 재설정 실패', [
                    'actor' => $actor,
                    'reason' => 'invalid_or_expired_token',
                    'account_id' => $account->getKey(),
                ]);

                throw $this->invalidTokenException();
            }

            $account->forceFill(['password' => $password])->save();
            $account->tokens()->delete();
            $this->tokenQuery->delete($actor, $email);

            Log::info('비밀번호 재설정 완료', [
                'actor' => $actor,
                'account_id' => $account->getKey(),
            ]);

            return [
                'message' => '비밀번호가 변경되었습니다.',
            ];
        });
    }

    private function invalidTokenException(): CustomException
    {
        return new CustomException(
            errorCode: ErrorCode::TOKEN_ERROR,
            message: '비밀번호 재설정 링크가 유효하지 않거나 만료되었습니다.',
        );
    }
}
