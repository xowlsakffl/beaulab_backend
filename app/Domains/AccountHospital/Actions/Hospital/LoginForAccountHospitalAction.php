<?php

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Domains\AccountHospital\Dto\Hospital\AuthForAccountHospitalDto;
use App\Domains\AccountHospital\Queries\Hospital\LoginForAccountHospitalQuery;
use Illuminate\Support\Facades\Log;

/**
 * 병원 계정 로그인 유스케이스.
 * 인증/토큰 발급은 Query에 위임하고 API 응답 DTO를 구성한다.
 */
final class LoginForAccountHospitalAction
{
    public function __construct(
        private readonly LoginForAccountHospitalQuery $query,
    ) {}

    /**
     * @param array{nickname:string,password:string,device_name?:string|null} $filters
     * @return array{token:string, actor:string, hospital: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        Log::info('병원 로그인', [
            'nickname' => $filters['nickname'] ?? null,
        ]);

        $result = $this->query->login($filters);

        return [
            'token' => $result['token'],
            'actor' => 'hospital',
            'hospital' => AuthForAccountHospitalDto::fromModel($result['hospital'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
