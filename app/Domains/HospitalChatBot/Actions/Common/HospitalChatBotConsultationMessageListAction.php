<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Common;

use App\Domains\HospitalChatBot\Dto\HospitalChatBotConsultationMessageDto;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotConsultationMessageQuery;

final class HospitalChatBotConsultationMessageListAction
{
    /** 한 번에 가져올 수 있는 최대 건수. 재접속 후 빠진 구간을 메우기에 충분한 값이다. */
    public const MAX_LIMIT = 200;

    public function __construct(private readonly HospitalChatBotConsultationMessageQuery $query) {}

    /** @return array{items: list<array<string, mixed>>, last_sequence: int, has_older: bool} */
    public function execute(int $consultationId, ?int $afterSequence, int $limit): array
    {
        $messages = $this->query->list($consultationId, $afterSequence, min($limit, self::MAX_LIMIT));
        $items = HospitalChatBotConsultationMessageDto::fromCollection($messages);

        return [
            'items' => $items,
            // 클라이언트가 다음 조회의 기준으로 쓴다. 비어 있으면 요청한 지점을 그대로 돌려준다.
            'last_sequence' => $items === [] ? (int) ($afterSequence ?? 0) : (int) end($items)['sequence'],
            // 꼬리부터 돌려주므로 앞에 더 남았을 수 있다. 클라이언트가 "이전 대화 더 보기"를 띄울 근거다.
            'has_older' => $items !== []
                && $this->query->hasOlderThan($consultationId, (int) $items[0]['sequence']),
        ];
    }
}
