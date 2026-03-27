<?php

namespace App\Domains\Common\Actions\Hashtag\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Models\Hashtag\Hashtag;
use Illuminate\Support\Facades\Gate;

final class HashtagDeleteForStaffAction
{
    public function execute(Hashtag $hashtag): array
    {
        Gate::authorize('delete', $hashtag);

        throw new CustomException(ErrorCode::INVALID_REQUEST, '해시태그 삭제는 지원하지 않습니다.');
    }
}
