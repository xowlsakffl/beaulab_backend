<?php

namespace App\Domains\AccountUser\Actions\User\Auth;

use App\Domains\AccountUser\Dto\User\Auth\AccountUserForAccountUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\User\Auth\ProfileUpdateForAccountUserQuery;
use App\Domains\Common\HarmfulWord\Support\HarmfulWordMatcher;
use Illuminate\Support\Facades\Log;

/**
 * 앱 사용자 프로필 수정 유스케이스.
 * 저장은 Query에 위임하고 변경된 프로필 DTO를 반환한다.
 */
final class ProfileUpdateForAccountUserAction
{
    public function __construct(
        private readonly ProfileUpdateForAccountUserQuery $query,
        private readonly HarmfulWordMatcher $harmfulWords,
    ) {}

    /**
     * @param  array{name?:string,nickname?:string,email?:string}  $filters
     * @return array{profile: array}
     */
    public function execute(AccountUser $user, array $filters): array
    {
        if (isset($filters['nickname']) && $filters['nickname'] !== $user->nickname) {
            $this->harmfulWords->assertFields('NICKNAME', ['nickname' => $filters['nickname']]);
        }

        $user = $this->query->update($user, $filters);

        Log::info('앱 사용자 프로필 수정', [
            'user_id' => $user->id,
            'keys' => array_keys($filters),
        ]);

        return [
            'profile' => AccountUserForAccountUserDto::fromModel($user)->toArray(),
        ];
    }
}
