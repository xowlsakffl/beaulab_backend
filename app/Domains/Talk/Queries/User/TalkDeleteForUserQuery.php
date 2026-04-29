<?php

namespace App\Domains\Talk\Queries\User;

use App\Domains\Talk\Models\Talk;

final class TalkDeleteForUserQuery
{
    public function getOwnedForUpdate(int $talkId, int $authorId): ?Talk
    {
        return Talk::query()
            ->whereKey($talkId)
            ->where('author_id', $authorId)
            ->lockForUpdate()
            ->first();
    }

    public function markDeleted(Talk $talk, string $status, string $postStatus): Talk
    {
        $talk->forceFill([
            'status' => $status,
            'post_status' => $postStatus,
        ]);

        if ($talk->isDirty()) {
            $talk->save();
        }

        return $talk->fresh();
    }
}
