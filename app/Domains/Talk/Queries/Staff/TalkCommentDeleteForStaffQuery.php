<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\TalkComment;

final class TalkCommentDeleteForStaffQuery
{
    public function softDelete(TalkComment $comment): void
    {
        $comment->children()->delete();
        $comment->delete();
    }
}
