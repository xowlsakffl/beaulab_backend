<?php

namespace App\Domains\Chat\Support;

final class ChatMatchKey
{
    public static function forUsers(int $firstUserId, int $secondUserId): string
    {
        $ids = [$firstUserId, $secondUserId];
        sort($ids, SORT_NUMERIC);

        return "{$ids[0]}:{$ids[1]}";
    }
}
