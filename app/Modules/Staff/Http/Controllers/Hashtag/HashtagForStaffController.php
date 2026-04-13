<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\Hashtag;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Actions\Hashtag\Staff\HashtagCreateForStaffAction;
use App\Domains\Common\Actions\Hashtag\Staff\HashtagDeleteForStaffAction;
use App\Domains\Common\Actions\Hashtag\Staff\HashtagGetForStaffAction;
use App\Domains\Common\Actions\Hashtag\Staff\HashtagListForStaffAction;
use App\Domains\Common\Actions\Hashtag\Staff\HashtagUpdateForStaffAction;
use App\Domains\Common\Models\Hashtag\Hashtag;
use App\Modules\Staff\Http\Requests\Hashtag\HashtagCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Hashtag\HashtagGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Hashtag\HashtagListForStaffRequest;
use App\Modules\Staff\Http\Requests\Hashtag\HashtagUpdateForStaffRequest;

/**
 * HashtagForStaffController 역할 정의.
 * 스태프 모듈의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class HashtagForStaffController extends Controller
{
    public function getHashtagsForStaff(
        HashtagListForStaffRequest $request,
        HashtagListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getHashtagForStaff(
        Hashtag $hashtag,
        HashtagGetForStaffRequest $request,
        HashtagGetForStaffAction $action,
    ) {
        $result = $action->execute($hashtag);

        return ApiResponse::success($result['hashtag'] ?? $result);
    }

    public function storeHashtagForStaff(
        HashtagCreateForStaffRequest $request,
        HashtagCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['hashtag'] ?? $result);
    }

    public function updateHashtagForStaff(
        Hashtag $hashtag,
        HashtagUpdateForStaffRequest $request,
        HashtagUpdateForStaffAction $action,
    ) {
        $result = $action->execute($hashtag, $request->validated());

        return ApiResponse::success($result['hashtag'] ?? $result);
    }

    public function deleteHashtagForStaff(
        Hashtag $hashtag,
        HashtagDeleteForStaffAction $action,
    ) {
        $result = $action->execute($hashtag);

        return ApiResponse::success($result);
    }
}
