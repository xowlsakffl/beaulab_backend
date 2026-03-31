<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\Talk;

final class TalkDeleteForStaffQuery
{
    public function softDelete(Talk $talk): void
    {
        $talk->comments()->delete();
        $talk->delete();
    }
}
