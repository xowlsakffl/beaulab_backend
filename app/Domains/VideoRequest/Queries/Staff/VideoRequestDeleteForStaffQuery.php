<?php

namespace App\Domains\VideoRequest\Queries\Staff;

use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestDeleteForStaffQuery
{
    public function softDelete(VideoRequest $videoRequest): void
    {
        $videoRequest->delete();
    }
}
