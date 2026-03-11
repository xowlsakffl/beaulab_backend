<?php

namespace App\Domains\HospitalTalk\Queries\Staff;

use App\Domains\HospitalTalk\Models\HospitalTalkComment;

final class HospitalTalkCommentDeleteForStaffQuery
{
    public function softDelete(HospitalTalkComment $comment): void
    {
        $comment->children()->delete();
        $comment->delete();
    }
}
