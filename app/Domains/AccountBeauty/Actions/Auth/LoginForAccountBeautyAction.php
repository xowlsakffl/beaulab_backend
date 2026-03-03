<?php

namespace App\Domains\AccountBeauty\Actions\Auth;

use App\Domains\AccountBeauty\Dto\Auth\AuthForAccountBeautyDto;
use App\Domains\AccountBeauty\Queries\Auth\LoginForAccountBeautyQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class LoginForAccountBeautyAction
{
    public function __construct(
        private readonly LoginForAccountBeautyQuery $query,
    ) {}

    /**
     * @param array{nickname:string,password:string,device_name?:string|null} $filters
     * @return array{token:string, actor:string, beauty: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        Log::info('뷰티 로그인', [
            'nickname' => $filters['nickname'] ?? null,
        ]);

        $result = DB::transaction(fn () => $this->query->login($filters));

        return [
            'token' => $result['token'],
            'actor' => 'beauty',
            'beauty' => AuthForAccountBeautyDto::fromModel($result['beauty'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
