<?php

namespace App\Modules\Staff\Http\Controllers\Nickname;

use App\Common\Authorization\AccessPermissions;
use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Nickname\Actions\Staff\NicknameWordManageAction;
use App\Domains\Nickname\Models\NicknameWord;
use App\Domains\Nickname\Support\NicknameGenerator;
use App\Domains\Nickname\Support\NicknameWordRules;
use App\Modules\Staff\Http\Requests\Nickname\NicknameWordRequest;

final class NicknameWordController extends Controller
{
    public function index(NicknameWordRequest $request)
    {
        $data = $request->validated();
        $query = NicknameWord::query()->where('type', $data['type']);
        if ($q = trim($data['q'] ?? '')) {
            $query->where('word', 'like', '%'.addcslashes($q, '%_\\').'%');
        }
        if ($status = $data['status'] ?? null) {
            $query->where('status', $status);
        }
        $page = $query->orderByDesc('id')->paginate(20);
        $stats = NicknameWord::query()->where('type', $data['type'])->selectRaw('COUNT(*) as total, SUM(status = ?) as active', ['ACTIVE'])->first();

        return ApiResponse::success($page->items(), [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
            'total' => $page->total(), 'per_page' => $page->perPage(),
            'word_count' => (int) $stats->total, 'active_count' => (int) $stats->active,
            'can_manage' => $request->user()->can(AccessPermissions::BEAULAB_NICKNAME_MANAGE),
        ]);
    }

    public function preview(NicknameWordRequest $request, NicknameGenerator $generator)
    {
        return ApiResponse::success(['samples' => $generator->preview()]);
    }

    public function validateWords(NicknameWordRequest $request, NicknameWordRules $rules)
    {
        return ApiResponse::success($rules->analyze($request->validated('type'), $request->validated('text')));
    }

    public function store(NicknameWordRequest $request, NicknameWordManageAction $action)
    {
        return ApiResponse::success($action->create($request->validated(), $request->user()));
    }

    public function update(NicknameWordRequest $request, NicknameWordManageAction $action)
    {
        $action->mutate($request->validated(), $request->user());

        return ApiResponse::success();
    }
}
