<?php

namespace App\Domains\Hospital\Actions\Auth;

use App\Domains\Hospital\Dto\Auth\AuthForHospitalDto;
use App\Domains\Hospital\Queries\Auth\LoginForHospitalQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class LoginForHospitalAction
{
    public function __construct(
        private readonly LoginForHospitalQuery $query,
    ) {}

    /**
     * @param array{nickname:string,password:string,device_name?:string|null} $filters
     * @return array{token:string, actor:string, hospital: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        Log::info('파트너 로그인', [
            'nickname' => $filters['nickname'] ?? null,
        ]);

        $result = DB::transaction(fn () => $this->query->login($filters));

        return [
            'token' => $result['token'],
            'actor' => 'hospital',
            'hospital' => AuthForHospitalDto::fromModel($result['hospital'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
