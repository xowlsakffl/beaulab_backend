<?php

namespace App\Domains\AccountUser\Actions\User\Auth;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Nickname\Support\NicknameGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AccountUserCreateWithNicknameAction
{
    public function __construct(private readonly NicknameGenerator $generator) {}

    /** 인증·가입 자격과 필수값을 검증한 가입 흐름에서 호출한다. 외부 API로 직접 공개하지 않는다. */
    public function execute(array $validatedAttributes): AccountUser
    {
        $base = $this->generator->base();
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $nickname = $this->generator->available($base, $attempt > 0);
            try {
                return DB::transaction(fn () => AccountUser::query()->create([
                    ...$validatedAttributes,
                    'nickname' => $nickname,
                ]));
            } catch (UniqueConstraintViolationException $exception) {
                // 사전 조회 이후 발생한 동시 가입 충돌만 재시도한다. 이메일 등 다른 고유키 오류는 보존한다.
                $message = $exception->getMessage();
                if (! str_contains($message, 'account_users_nickname_unique') && ! str_contains($message, 'account_users.nickname')) {
                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages(['nickname' => '닉네임을 생성하지 못했습니다. 잠시 후 다시 시도해 주세요.']);
    }
}
