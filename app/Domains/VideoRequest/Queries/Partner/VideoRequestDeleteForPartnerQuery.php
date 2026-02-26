<?php

namespace App\Domains\VideoRequest\Queries\Partner;

use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestDeleteForPartnerQuery
{
    public function softDelete(VideoRequest $videoRequest): void
    {
        $videoRequest->delete();
    }
}
