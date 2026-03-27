<?php

namespace App\Domains\Common\Queries\Hashtag\Staff;

use App\Domains\Common\Models\Hashtag\Hashtag;

final class HashtagUpdateForStaffQuery
{
    public function update(Hashtag $hashtag, array $data): Hashtag
    {
        $hashtag->fill($data);

        if ($hashtag->isDirty()) {
            $hashtag->save();
        }

        return $hashtag->fresh();
    }
}
