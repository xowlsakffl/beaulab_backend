<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries;

use App\Domains\HospitalChatBot\Models\HospitalChatBotEvent;
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
