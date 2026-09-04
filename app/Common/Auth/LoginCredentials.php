<?php

namespace App\Common\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Timebox;

final class LoginCredentials
{
    public function __construct(private readonly Timebox $timebox) {}

    public function validate(?Authenticatable $account, string $password): void
    {
        $this->timebox->call(function (Timebox $timebox) use ($account, $password): void {
            if (! $account || ! Hash::check($password, $account->getAuthPassword())) {
                throw new CustomException(ErrorCode::UNAUTHORIZED, '로그인 정보가 일치하지 않습니다.');
            }

            $timebox->returnEarly();
        }, 200000);
    }
}
