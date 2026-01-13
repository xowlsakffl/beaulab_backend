<?php

namespace App\Shared\Exceptions;

enum ErrorCode: string
{
    // Common
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case INVALID_REQUEST = 'INVALID_REQUEST';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case DB_ERROR = 'DB_ERROR';
    case USER_NOT_FOUND = 'USER_NOT_FOUND';

    public function status(): int
    {
        return match ($this) {
            self::INVALID_REQUEST => 422,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::DB_ERROR => 500,
            self::USER_NOT_FOUND => 404,
            default => 500,
        };
    }

    public function messageApp(): string
    {
        // 앱(End-user)용: 안전하고 짧게
        return match ($this) {
            self::INVALID_REQUEST => '요청 값이 올바르지 않습니다.',
            self::UNAUTHORIZED => '인증이 필요합니다.',
            self::FORBIDDEN => '권한이 없습니다.',
            self::NOT_FOUND => '요청한 정보를 찾을 수 없습니다.',
            self::DB_ERROR => '서버 오류가 발생했습니다.',
            self::USER_NOT_FOUND => '사용자를 찾을 수 없습니다.',
            default => '서버 오류가 발생했습니다.',
        };
    }

    public function messageAdmin(): string
    {
        // 어드민(운영자)용: 좀 더 힌트 가능(그래도 민감정보는 주의)
        return match ($this) {
            self::INVALID_REQUEST => '요청 값 검증에 실패했습니다.',
            self::UNAUTHORIZED => '로그인이 필요합니다.',
            self::FORBIDDEN => '접근 권한이 없습니다.',
            self::NOT_FOUND => '리소스를 찾을 수 없습니다.',
            self::DB_ERROR => 'DB 오류가 발생했습니다.',
            self::USER_NOT_FOUND => '사용자를 찾을 수 없습니다.',
            default => '예기치 못한 서버 오류가 발생했습니다.',
        };
    }
}
