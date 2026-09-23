<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Dto\Hospital;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use Carbon\CarbonInterface;

final readonly class HospitalChatBotConsultationForHospitalDto
{
    /** @return array<string, mixed>|null */
    public static function fromModel(?HospitalChatBotConsultation $consultation): ?array
    {
        if ($consultation === null) {
            return null;
        }

        return [
            'id' => (int) $consultation->id,
            'status' => (string) $consultation->status,
            'requested_at' => self::time($consultation->requested_at),
            'connected_at' => self::time($consultation->connected_at),
            'ended_at' => self::time($consultation->ended_at),
            'canceled_at' => self::time($consultation->canceled_at),
            'close_reason' => $consultation->close_reason !== null ? (string) $consultation->close_reason : null,
            // 병원 사용자에게는 담당 상담원의 표시명만 노출한다. 계정 ID는 내보내지 않는다.
            'assigned_staff_name' => $consultation->assigned_staff_name_snapshot !== null
                ? (string) $consultation->assigned_staff_name_snapshot
                : null,
            'last_message_sequence' => (int) $consultation->last_message_sequence,
        ];
    }

    private static function time(mixed $value): ?string
    {
        return $value instanceof CarbonInterface
            ? $value->setTimezone(HospitalChatBotAvailabilityResolver::TIMEZONE)->toIso8601String()
            : null;
    }
}
