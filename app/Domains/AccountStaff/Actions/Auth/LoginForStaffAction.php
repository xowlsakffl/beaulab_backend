<?php

namespace App\Domains\AccountStaff\Actions\Auth;

use App\Domains\AccountStaff\Dto\Auth\AuthForStaffDto;
use App\Domains\AccountStaff\Dto\Auth\StaffLoginDto;
use App\Domains\AccountStaff\Queries\Auth\LoginForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class LoginForStaffAction
{
    public function __construct(
        private readonly LoginForStaffQuery $query,
    ) {}

    /**
     * @param array{nickname:string, password:string} $filters
     * @return array{token:string, actor:string, staff: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        Log::info('뷰랩 직원 로그인', [
            'nickname' => $filters['nickname'] ?? null,
        ]);

        $result = DB::transaction(function () use ($filters) {
            return $this->query->login($filters);
        });

        return [
            'token' => $result['token'],
            'actor' => 'staff',
            'staff' => AuthForStaffDto::fromModel($result['staff'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
