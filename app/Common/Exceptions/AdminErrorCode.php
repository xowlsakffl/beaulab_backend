<?php

namespace App\Common\Exceptions;

enum AdminErrorCode: string
{
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    case SESSION_EXPIRED = 'SESSION_EXPIRED';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case UNAUTHORIZED = 'UNAUTHORIZED';

    public function status(): int
    {
        return match ($this) {
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::METHOD_NOT_ALLOWED => 405,
            self::SESSION_EXPIRED => 419,
            default => 500,
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::UNAUTHORIZED, self::FORBIDDEN => '접근 권한이 없습니다.',
            self::NOT_FOUND => '페이지를 찾을 수 없습니다.',
            self::METHOD_NOT_ALLOWED => '허용되지 않은 메소드입니다.',
            self::SESSION_EXPIRED => '세션이 만료되었습니다. 다시 로그인해 주세요.',
            default => '서버 오류가 발생했습니다.',
        };
    }

    public function page(): string
    {
        return match ($this) {
            // Todo: 에러페이지 만들어야함
            self::FORBIDDEN => 'errors/403',
            self::NOT_FOUND => 'errors/404',
            self::METHOD_NOT_ALLOWED => 'errors/405',
            self::SESSION_EXPIRED => 'errors/419',
            default => 'errors/500',
        };
    }
}
