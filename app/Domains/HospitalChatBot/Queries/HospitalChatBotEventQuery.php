<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries;

use App\Domains\HospitalChatBot\Models\HospitalChatBotEvent;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Carbon\CarbonInterface;

/**
 * 이용 이벤트 저장과 정리.
 */
final class HospitalChatBotEventQuery
{
    /**
     * 한 요청에서 온 이벤트를 한 번에 넣는다. 한 건씩 넣으면 20건이 20번 왕복이다.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return int 저장한 건수
     */
    public function record(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        HospitalChatBotEvent::query()->insert($rows);

        return count($rows);
    }

    /**
     * 주어진 Q&A 중 병의원 화면에서 분류로 보이는 노드(살아 있는 활성 자식이 있는 것)의 ID.
     *
     * SDK는 트리를 내려가는 클릭도 질문 선택으로 알린다. 분류 클릭은 세지 않는다(대시보드 PRD DD-08).
     * 병의원 트리는 삭제·비활성 자식을 숨기므로, 자식이 전부 지워졌거나 꺼진 노드는 답변을 보이는
     * 질문이다. 그 클릭은 세야 한다. 화면이 보는 것과 같은 기준으로 판단한다.
     *
     * @param  list<int>  $qnaIds
     * @return list<int>
     */
    public function folderIds(array $qnaIds): array
    {
        if ($qnaIds === []) {
            return [];
        }

        return HospitalChatBotQna::query()
            ->whereIn('parent_id', $qnaIds)
            ->where('status', HospitalChatBotQna::STATUS_ACTIVE)
            ->distinct()
            ->pluck('parent_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * 보관 기간이 지난 이벤트를 지운다.
     *
     * 한 번에 다 지우면 큰 테이블에서 잠금이 길어진다. 묶음 단위로 끊고, 한 묶음이
     * 꽉 찼으면 더 남은 것으로 보고 반복한다.
     *
     * @return int 지운 건수
     */
    public function pruneBefore(CarbonInterface $before, int $chunk): int
    {
        $deleted = 0;

        do {
            $count = HospitalChatBotEvent::query()
                ->where('occurred_at', '<', $before)
                ->limit($chunk)
                ->delete();
            $deleted += $count;
        } while ($count === $chunk);

        return $deleted;
    }
}
