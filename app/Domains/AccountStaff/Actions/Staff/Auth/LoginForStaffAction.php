<?php

namespace App\Domains\AccountStaff\Actions\Staff\Auth;

use App\Common\Exceptions\CustomException;
use App\Domains\AccountStaff\Dto\Staff\AccountStaffForStaffDto;
use App\Domains\AccountStaff\Queries\Staff\Auth\LoginForStaffQuery;
use Illuminate\Support\Facades\Log;

/**
 * 스태프 로그인 유스케이스.
 * 인증/토큰 발급은 Query에 위임하고 API 응답 DTO를 구성한다.
 */
final class LoginForStaffAction
{
    public function __construct(
        private readonly LoginForStaffQuery $query,
    ) {}

    /**
     * @param  array{nickname:string, password:string, keep_logged_in?:bool}  $filters
     * @return array{token:string, actor:string, staff: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        try {
            $result = $this->query->login($filters);
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
            'keep_logged_in' => (bool) ($filters['keep_logged_in'] ?? false),
        ]);

        return [
            'token' => $result['token'],
            'actor' => 'staff',
            'staff' => AccountStaffForStaffDto::fromModel($result['staff'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
