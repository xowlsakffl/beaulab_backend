<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

/**
 * 상담 세션의 상태와 전이 규칙을 한곳에 모은다.
 * PRD 11장의 상태 정의·전이표와 1:1로 대응한다.
 */
final class HospitalChatBotConsultationStatus
{
    public const WAITING = 'WAITING';

    public const CONNECTED = 'CONNECTED';

    public const ENDED = 'ENDED';

    public const CANCELED = 'CANCELED';

    /** 종료·취소 사유 코드 */
    public const REASON_AGENT_COMPLETED = 'AGENT_COMPLETED';

    public const REASON_USER_CANCELED = 'USER_CANCELED';

    public const REASON_WAITING_TIMEOUT = 'WAITING_TIMEOUT';

    /** 병원 사용자가 상담 중에 직접 종료한 경우(PRD D-09 결정 변경). */
    public const REASON_USER_ENDED = 'USER_ENDED';

    /** 상태 전이를 수행한 주체 */
    public const ACTOR_HOSPITAL_USER = 'HOSPITAL_USER';

    public const ACTOR_AGENT = 'AGENT';

    public const ACTOR_SYSTEM = 'SYSTEM';

    /** @return list<string> */
    public static function active(): array
    {
        return [self::WAITING, self::CONNECTED];
    }

    /** @return list<string> */
    public static function terminal(): array
    {
        return [self::ENDED, self::CANCELED];
    }

    public static function isActive(string $status): bool
    {
        return in_array($status, self::active(), true);
    }

    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::terminal(), true);
    }

    /**
     * 사람이 읽는 이름. 내보낸 CSV는 화면 없이 혼자 읽히므로 코드가 아니라 말이어야 한다.
     *
     * 모르는 코드는 그대로 돌려준다. 칸이 비는 것보다 낫다.
     */
    public static function label(string $status): string
    {
        return match ($status) {
            self::WAITING => '대기',
            self::CONNECTED => '진행',
            self::ENDED => '종료',
            self::CANCELED => '취소',
            default => $status,
        };
    }

    public static function closeReasonLabel(?string $reason): string
    {
        return match ($reason) {
            null, '' => '',
            self::REASON_AGENT_COMPLETED => '상담원 종료',
            self::REASON_USER_CANCELED => '사용자 취소',
            self::REASON_USER_ENDED => '사용자 종료',
            self::REASON_WAITING_TIMEOUT => '대기 시간 초과',
            default => $reason,
        };
    }
}
