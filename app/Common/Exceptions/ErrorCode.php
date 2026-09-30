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
    case CSRF_MISMATCH = 'CSRF_MISMATCH';
    case DB_ERROR = 'DB_ERROR';
    case USER_NOT_FOUND = 'USER_NOT_FOUND';
    case RATE_LIMITED = 'RATE_LIMITED';
    case PAYLOAD_TOO_LARGE = 'PAYLOAD_TOO_LARGE';

    // HospitalChatBot 상담
    case CONSULTATION_UNAVAILABLE = 'CONSULTATION_UNAVAILABLE';
    case CONSULTATION_ALREADY_CLAIMED = 'CONSULTATION_ALREADY_CLAIMED';
    case CONSULTATION_NOT_ACTIVE = 'CONSULTATION_NOT_ACTIVE';

    public function status(): int
    {
        return match ($this) {
            self::INVALID_REQUEST => 422,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::METHOD_NOT_ALLOWED => 405,
            self::PAYLOAD_TOO_LARGE => 413,
            self::TOKEN_ERROR => 419,
            self::CSRF_MISMATCH => 419,
            self::RATE_LIMITED => 429,
            self::DB_ERROR => 500,
            self::USER_NOT_FOUND => 404,
            self::CONSULTATION_UNAVAILABLE => 422,
            self::CONSULTATION_ALREADY_CLAIMED, self::CONSULTATION_NOT_ACTIVE => 409,
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
            self::PAYLOAD_TOO_LARGE => '요청 용량이 초과되었습니다.',
            self::TOKEN_ERROR => '토큰이 유효하지 않습니다.',
            self::CSRF_MISMATCH => '요청 인증 정보가 만료되었습니다. 다시 시도해 주세요.',
            self::RATE_LIMITED => '요청이 너무 많습니다. 잠시 후 다시 시도해주세요.',
            self::DB_ERROR => '데이터베이스 오류가 발생했습니다.',
            self::USER_NOT_FOUND => '사용자를 찾을 수 없습니다.',
            self::CONSULTATION_UNAVAILABLE => '지금은 상담원 연결이 어렵습니다.',
            self::CONSULTATION_ALREADY_CLAIMED => '다른 상담원이 이미 연결한 상담입니다.',
            self::CONSULTATION_NOT_ACTIVE => '이미 종료되었거나 취소된 상담입니다.',
            default => '서버 오류가 발생했습니다.',
        };
    }
}
