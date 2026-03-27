<?php

namespace App\Domains\Common\Queries\Hashtag\Staff;

use App\Domains\Common\Models\Hashtag\Hashtag;

final class HashtagDeleteForStaffQuery
{
    public function delete(Hashtag $hashtag): void
    {
        $hashtag->delete();
    }
}
