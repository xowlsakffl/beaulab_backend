<?php

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Common\Auth\ActorAuthentication;
use App\Common\Auth\AuthActor;
use App\Common\Exceptions\CustomException;
use App\Domains\AccountHospital\Dto\Hospital\AccountHospitalForAccountHospitalDto;
use App\Domains\AccountHospital\Queries\Hospital\LoginForAccountHospitalQuery;
use Illuminate\Support\Facades\Log;

/**
 * 병원 계정 로그인 유스케이스.
 * 계정 검증은 Query에 위임하고 공통 인증 처리 후 API 응답 DTO를 구성한다.
 */
final class LoginForAccountHospitalAction
{
    public function __construct(
        private readonly ActorAuthentication $authentication,
        private readonly LoginForAccountHospitalQuery $query,
    ) {}

    /**
     * @param  array{nickname:string,password:string}  $filters
     * @return array{session:array, actor:string, hospital: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        try {
            $result = $this->query->login($filters);
            $credentials = $this->authentication->login($result['hospital'], AuthActor::HOSPITAL);
        } catch (CustomException $exception) {
            Log::warning('병원 로그인 실패', [
                'reason' => $exception->errorCode->value,
                'nickname_hash' => hash('sha256', (string) ($filters['nickname'] ?? '')),
            ]);

            throw $exception;
        }

        Log::info('병원 로그인 성공', [
            'hospital_id' => $result['hospital']->id,
            'nickname' => $result['hospital']->nickname,
        ]);

        return [
            ...$credentials,
            'actor' => 'hospital',
            'hospital' => AccountHospitalForAccountHospitalDto::fromModel($result['hospital'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
