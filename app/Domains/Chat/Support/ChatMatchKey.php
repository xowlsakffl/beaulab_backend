<?php

namespace App\Domains\Chat\Support;

/**
 * 1:1 채팅방 중복 생성을 막기 위한 match_key 생성기.
 * 사용자 ID 순서와 무관하게 같은 두 유저는 항상 같은 key를 만든다.
 */
final class ChatMatchKey
{
    public static function forUsers(int $firstUserId, int $secondUserId): string
    {
        $ids = [$firstUserId, $secondUserId];
        sort($ids, SORT_NUMERIC);

        return "{$ids[0]}:{$ids[1]}";
    }
}
