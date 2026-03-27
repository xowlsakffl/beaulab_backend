<?php

namespace App\Domains\Common\Queries\Hashtag\Staff;

use App\Domains\Common\Models\Hashtag\Hashtag;

final class HashtagCreateForStaffQuery
{
    public function create(array $data): Hashtag
    {
        return Hashtag::create($data);
    }
}
