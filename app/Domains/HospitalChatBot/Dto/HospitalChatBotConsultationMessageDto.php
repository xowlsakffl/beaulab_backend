<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Dto;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final readonly class HospitalChatBotConsultationMessageDto
{
    /** @return array<string, mixed> */
    public static function fromModel(HospitalChatBotConsultationMessage $message): array
    {
        return [
            'id' => (int) $message->id,
            'consultation_id' => (int) $message->consultation_id,
            'sequence' => (int) $message->sequence,
            'sender_type' => (string) $message->sender_type,
            // 발신자 계정 ID는 내보내지 않는다. 화면에는 표시명만 있으면 된다.
            'sender_name' => is_string($message->sender_name_snapshot) && $message->sender_name_snapshot !== ''
                ? $message->sender_name_snapshot
                : null,
            'client_message_id' => is_string($message->client_message_id) && $message->client_message_id !== ''
                ? $message->client_message_id
                : null,
            'message_type' => (string) $message->message_type,
            'content' => (string) $message->content,
            'created_at' => $message->created_at instanceof CarbonInterface
                ? $message->created_at->setTimezone(HospitalChatBotAvailabilityResolver::TIMEZONE)->toIso8601String()
                : null,
        ];
    }

    /**
     * @param  Collection<int, HospitalChatBotConsultationMessage>  $messages
     * @return list<array<string, mixed>>
     */
    public static function fromCollection(Collection $messages): array
    {
        return $messages->map(static fn (HospitalChatBotConsultationMessage $message): array => self::fromModel($message))->values()->all();
    }
}
