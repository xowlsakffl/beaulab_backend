<?php

namespace App\Domains\AccountUser\Actions\Auth;

use App\Domains\AccountUser\Dto\Auth\ProfileForAccountUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Auth\UpdateProfileForAccountUserQuery;
use Illuminate\Support\Facades\Log;

/**
 * 앱 사용자 프로필 수정 유스케이스.
 * 저장은 Query에 위임하고 변경된 프로필 DTO를 반환한다.
 */
final class UpdateProfileForAccountUserAction
{
    public function __construct(
        private readonly UpdateProfileForAccountUserQuery $query,
    ) {}

    /**
     * @param array{name?:string,email?:string} $filters
     * @return array{profile: array}
     */
    public function execute(AccountUser $user, array $filters): array
    {
        Log::info('앱 사용자 프로필 수정', [
            'user_id' => $user->id,
            'keys' => array_keys($filters),
        ]);

        $user = $this->query->update($user, $filters);

        return [
            'profile' => ProfileForAccountUserDto::fromModel($user)->toArray(),
        ];
    }
}
