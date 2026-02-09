<?php

namespace App\Common\Exceptions;

enum ErrorCode: string
{
    // Common
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case INVALID_REQUEST = 'INVALID_REQUEST';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    case TOKEN_ERROR = 'TOKEN_ERROR';
    case DB_ERROR = 'DB_ERROR';
    case USER_NOT_FOUND = 'USER_NOT_FOUND';
    case RATE_LIMITED = 'RATE_LIMITED';

    public function status(): int
    {
        return match ($this) {
            self::INVALID_REQUEST => 422,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::METHOD_NOT_ALLOWED => 405,
            self::TOKEN_ERROR => 419,
            self::RATE_LIMITED => 429,
            self::DB_ERROR => 500,
            self::USER_NOT_FOUND => 404,
            default => 500,
        };
    }

    public function messageApp(): string
    {
        return match ($this) {
            self::INVALID_REQUEST => '요청 값이 올바르지 않습니다.',
            self::UNAUTHORIZED => '인증이 필요합니다.',
            self::FORBIDDEN => '권한이 없습니다.',
            self::NOT_FOUND => '요청한 정보를 찾을 수 없습니다.',
            self::METHOD_NOT_ALLOWED => '허용되지 않는 HTTP 메서드입니다.',
            self::TOKEN_ERROR => '토큰이 유효하지 않습니다.',
            self::RATE_LIMITED => '요청이 너무 많습니다. 잠시 후 다시 시도해주세요.',
            self::DB_ERROR => '서버 오류가 발생했습니다.',
            self::USER_NOT_FOUND => '사용자를 찾을 수 없습니다.',
            default => '서버 오류가 발생했습니다.',
        };
    }
}
