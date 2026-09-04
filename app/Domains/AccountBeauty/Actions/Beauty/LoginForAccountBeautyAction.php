<?php

namespace App\Domains\AccountBeauty\Actions\Beauty;

use App\Common\Auth\ActorAuthentication;
use App\Common\Auth\AuthActor;
use App\Common\Exceptions\CustomException;
use App\Domains\AccountBeauty\Dto\Beauty\AccountBeautyForAccountBeautyDto;
use App\Domains\AccountBeauty\Queries\Beauty\LoginForAccountBeautyQuery;
use Illuminate\Support\Facades\Log;

/**
 * 뷰티 계정 로그인 유스케이스.
 * 계정 검증은 Query에 위임하고 공통 인증 처리 후 API 응답 DTO를 구성한다.
 */
final class LoginForAccountBeautyAction
{
    public function __construct(
        private readonly ActorAuthentication $authentication,
        private readonly LoginForAccountBeautyQuery $query,
    ) {}

    /**
     * @param  array{nickname:string,password:string}  $filters
     * @return array{session:array, actor:string, beauty: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        try {
            $result = $this->query->login($filters);
            $credentials = $this->authentication->login($result['beauty'], AuthActor::BEAUTY);
        } catch (CustomException $exception) {
            Log::warning('뷰티 로그인 실패', [
                'reason' => $exception->errorCode->value,
                'nickname_hash' => hash('sha256', (string) ($filters['nickname'] ?? '')),
            ]);

            throw $exception;
        }

        Log::info('뷰티 로그인 성공', [
            'beauty_id' => $result['beauty']->id,
            'nickname' => $result['beauty']->nickname,
        ]);

        return [
            ...$credentials,
            'actor' => 'beauty',
            'beauty' => AccountBeautyForAccountBeautyDto::fromModel($result['beauty'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
