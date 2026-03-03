<?php

namespace App\Domains\Beauty\Actions\Auth;

use App\Domains\Beauty\Dto\Auth\AuthForBeautyDto;
use App\Domains\Beauty\Queries\Auth\LoginForBeautyQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class LoginForBeautyAction
{
    public function __construct(
        private readonly LoginForBeautyQuery $query,
    ) {}

    /**
     * @param array{nickname:string,password:string,device_name?:string|null} $filters
     * @return array{token:string, actor:string, beauty: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        Log::info('파트너 로그인', [
            'nickname' => $filters['nickname'] ?? null,
        ]);

        $result = DB::transaction(fn () => $this->query->login($filters));

        return [
            'token' => $result['token'],
            'actor' => 'beauty',
            'beauty' => AuthForBeautyDto::fromModel($result['beauty'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
