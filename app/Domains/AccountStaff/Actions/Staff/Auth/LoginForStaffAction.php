<?php

namespace App\Domains\AccountStaff\Actions\Staff\Auth;

use App\Common\Auth\ActorAuthentication;
use App\Common\Auth\AuthActor;
use App\Common\Exceptions\CustomException;
use App\Domains\AccountStaff\Dto\Staff\AccountStaffForStaffDto;
use App\Domains\AccountStaff\Queries\Staff\Auth\LoginForStaffQuery;
use Illuminate\Support\Facades\Log;

/**
 * 스태프 로그인 유스케이스.
 * 계정 검증은 Query에 위임하고 공통 인증 처리 후 API 응답 DTO를 구성한다.
 */
final class LoginForStaffAction
{
    public function __construct(
        private readonly ActorAuthentication $authentication,
        private readonly LoginForStaffQuery $query,
    ) {}

    /**
     * @param  array{nickname:string, password:string}  $filters
     * @return array{session:array, actor:string, staff: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        try {
            $result = $this->query->login($filters);
            $credentials = $this->authentication->login($result['staff'], AuthActor::STAFF);
        } catch (CustomException $exception) {
            Log::warning('뷰랩 직원 로그인 실패', [
                'reason' => $exception->errorCode->value,
                'nickname_hash' => hash('sha256', (string) ($filters['nickname'] ?? '')),
            ]);

            throw $exception;
        }

        Log::info('뷰랩 직원 로그인 성공', [
            'staff_id' => $result['staff']->id,
            'nickname' => $result['staff']->nickname,
        ]);

        return [
            ...$credentials,
            'actor' => 'staff',
            'staff' => AccountStaffForStaffDto::fromModel($result['staff'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
