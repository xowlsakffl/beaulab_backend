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
     * 주어진 Q&A 중 분류 노드(자식이 있는 것)의 ID.
     *
     * SDK는 트리를 내려가는 클릭도 질문 선택으로 알린다. 분류 클릭은 세지 않는다(대시보드 PRD DD-08).
     * 삭제된 자식까지 본다. 자식이 전부 지워졌어도 분류였던 노드가 질문이 되지는 않는다.
     *
     * @param  list<int>  $qnaIds
     * @return list<int>
     */
    public function folderIds(array $qnaIds): array
    {
        if ($qnaIds === []) {
            return [];
        }

        return HospitalChatBotQna::withTrashed()
            ->whereIn('parent_id', $qnaIds)
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
