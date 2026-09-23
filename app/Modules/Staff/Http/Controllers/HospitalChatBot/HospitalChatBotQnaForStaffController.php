<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotQnaCreateForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotQnaDeleteForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotQnaGetForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotQnaListForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotQnaUpdateForStaffAction;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotQnaCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotQnaListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotQnaUpdateForStaffRequest;

final class HospitalChatBotQnaForStaffController extends Controller
{
    public function index(HospitalChatBotQnaListForStaffRequest $request, HospitalChatBotQnaListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta']);
    }

    public function show(HospitalChatBotQna $qna, HospitalChatBotQnaGetForStaffAction $action)
    {
        return ApiResponse::success($action->execute($qna));
    }

    public function store(HospitalChatBotQnaCreateForStaffRequest $request, HospitalChatBotQnaCreateForStaffAction $action)
    {
        return ApiResponse::success($action->execute($request->validated()), status: 201);
    }

    public function update(
        HospitalChatBotQna $qna,
        HospitalChatBotQnaUpdateForStaffRequest $request,
        HospitalChatBotQnaUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($qna, $request->validated()));
    }

    public function destroy(HospitalChatBotQna $qna, HospitalChatBotQnaDeleteForStaffAction $action)
    {
        return ApiResponse::success($action->execute($qna));
    }
}
