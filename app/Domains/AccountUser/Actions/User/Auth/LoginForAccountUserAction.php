<?php

namespace App\Domains\AccountUser\Actions\User\Auth;

use App\Common\Auth\ActorAuthentication;
use App\Common\Auth\AuthActor;
use App\Common\Exceptions\CustomException;
use App\Domains\AccountUser\Dto\User\Auth\AccountUserForAccountUserDto;
use App\Domains\AccountUser\Queries\User\Auth\LoginForAccountUserQuery;
use Illuminate\Support\Facades\Log;

/**
 * 앱 사용자 로그인 유스케이스.
 * 계정 검증은 Query에 위임하고 공통 인증 처리 후 API 응답 DTO를 구성한다.
 */
final class LoginForAccountUserAction
{
    public function __construct(
        private readonly ActorAuthentication $authentication,
        private readonly LoginForAccountUserQuery $query,
    ) {}

    /**
     * @param  array{email:string,password:string,device_name?:string|null}  $filters
     * @return array{session?:array, token?:string, actor:string, user: array}
     */
    public function execute(array $filters): array
    {
        try {
            $result = $this->query->login($filters);
            $credentials = $this->authentication->login($result['user'], AuthActor::USER, $filters['device_name'] ?? null);
        } catch (CustomException $exception) {
            Log::warning('앱 사용자 로그인 실패', [
                'reason' => $exception->errorCode->value,
                'email_hash' => hash('sha256', (string) ($filters['email'] ?? '')),
            ]);

            throw $exception;
        }

        Log::info('앱 사용자 로그인 성공', [
            'user_id' => $result['user']->id,
        ]);

        return [
            ...$credentials,
            'actor' => 'user',
            'user' => AccountUserForAccountUserDto::fromModel($result['user'])->toArray(),
        ];
    }
}
