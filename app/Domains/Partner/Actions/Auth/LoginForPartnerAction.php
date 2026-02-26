<?php

namespace App\Domains\Partner\Actions\Auth;

use App\Domains\Partner\Dto\Auth\AuthForPartnerDto;
use App\Domains\Partner\Queries\Auth\LoginForPartnerQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class LoginForPartnerAction
{
    public function __construct(
        private readonly LoginForPartnerQuery $query,
    ) {}

    /**
     * @param array{nickname:string,password:string,device_name?:string|null} $filters
     * @return array{token:string, actor:string, partner: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        Log::info('파트너 로그인', [
            'nickname' => $filters['nickname'] ?? null,
        ]);

        $result = DB::transaction(fn () => $this->query->login($filters));

        return [
            'token' => $result['token'],
            'actor' => 'partner',
            'partner' => AuthForPartnerDto::fromModel($result['partner'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
