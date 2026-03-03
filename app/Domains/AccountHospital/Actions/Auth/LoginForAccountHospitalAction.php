<?php

namespace App\Domains\AccountHospital\Actions\Auth;

use App\Domains\AccountHospital\Dto\Auth\AuthForAccountHospitalDto;
use App\Domains\AccountHospital\Queries\Auth\LoginForAccountHospitalQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $result = DB::transaction(fn () => $this->query->login($filters));

        return [
            'token' => $result['token'],
            'actor' => 'hospital',
            'hospital' => AuthForAccountHospitalDto::fromModel($result['hospital'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
