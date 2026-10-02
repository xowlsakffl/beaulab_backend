<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

/**
 * 실시간 채널 이름을 한곳에서 만든다.
 * 프론트도 같은 규칙으로 구독하므로 문자열을 흩어 두지 않는다.
 */
final class HospitalChatBotChannel
{
    /** 상담 한 건의 메시지와 상태 변화. 해당 병의원과 담당 상담원만 구독한다. */
    public static function consultation(int $consultationId): string
    {
        return "hospital-chatbot.consultation.{$consultationId}";
    }

    /** 상담원 대기 목록. 새 요청과 상태 변화가 흐른다. 상담원만 구독한다. */
    public static function queue(): string
    {
        return 'hospital-chatbot.queue';
    }
}
