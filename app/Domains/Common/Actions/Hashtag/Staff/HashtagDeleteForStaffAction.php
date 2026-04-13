<?php

namespace App\Domains\Common\Actions\Hashtag\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Models\Hashtag\Hashtag;
use Illuminate\Support\Facades\Gate;

/**
 * HashtagDeleteForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HashtagDeleteForStaffAction
{
    public function execute(Hashtag $hashtag): array
    {
        Gate::authorize('delete', $hashtag);

        throw new CustomException(ErrorCode::INVALID_REQUEST, '해시태그 삭제는 지원하지 않습니다.');
    }
}
