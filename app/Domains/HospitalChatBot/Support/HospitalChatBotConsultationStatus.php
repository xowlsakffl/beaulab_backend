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
}
