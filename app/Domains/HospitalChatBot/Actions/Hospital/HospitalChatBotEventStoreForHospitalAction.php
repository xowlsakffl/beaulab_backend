<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Domains\HospitalChatBot\Queries\HospitalChatBotEventQuery;
use Carbon\CarbonImmutable;

final class HospitalChatBotEventStoreForHospitalAction
{
    public function __construct(private readonly HospitalChatBotEventQuery $query) {}

    /**
     * @param  list<array{event_type:string, qna_id:?int, source_page_path:?string}>  $events
     * @return array{accepted:int}
     */
    public function execute(array $events): array
    {
        // 병의원과 계정은 세션이 정한다. 본문에 실려 온 값은 받지 않는다.
        $account = HospitalChatBotConsultationActiveForHospitalAction::account();
        // 시각은 서버가 찍는다. 클라이언트 시계 오차가 집계에 들어오면 안 된다.
        $now = CarbonImmutable::now();

        // 분류 노드를 펼친 클릭은 질문을 본 게 아니다. 버린다(대시보드 PRD DD-08).
        $folderIds = $this->query->folderIds(array_values(array_filter(array_column($events, 'qna_id'))));
        $events = array_values(array_filter(
            $events,
            static fn (array $event): bool => $event['qna_id'] === null || ! in_array($event['qna_id'], $folderIds, true),
        ));

        $rows = array_map(static fn (array $event): array => [
            'event_type' => $event['event_type'],
            'hospital_id' => (int) $account->hospital_id,
            'account_hospital_id' => (int) $account->id,
            'qna_id' => $event['qna_id'],
            'source_page_path' => $event['source_page_path'],
            'occurred_at' => $now,
        ], $events);

        return ['accepted' => $this->query->record($rows)];
    }
}
