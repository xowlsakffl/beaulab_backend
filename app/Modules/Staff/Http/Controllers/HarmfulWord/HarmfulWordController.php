<?php

namespace App\Modules\Staff\Http\Controllers\HarmfulWord;

use App\Common\Authorization\AccessPermissions;
use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\HarmfulWord\Actions\Staff\HarmfulWordManageAction;
use App\Domains\Common\HarmfulWord\Models\HarmfulWord;
use App\Domains\Common\HarmfulWord\Support\HarmfulWordMatcher;
use App\Domains\Common\HarmfulWord\Support\HarmfulWordRules;
use App\Modules\Staff\Http\Requests\HarmfulWord\HarmfulWordRequest;

final class HarmfulWordController extends Controller
{
    public function index(HarmfulWordRequest $request)
    {
        $data = $request->validated();
        $query = HarmfulWord::query()->with(['scopes', 'exceptions']);
        $q = trim($data['q'] ?? '');
        if ($q !== '') {
            $query->where('normalized_word', 'like', '%'.addcslashes(HarmfulWordRules::normalize($q), '%_\\').'%');
        }
        if ($scopes = $data['scopes'] ?? []) {
            $query->whereHas('scopes', fn ($query) => $query->whereIn('scope', $scopes));
        }
        if ($statuses = $data['statuses'] ?? []) {
            $query->whereIn('status', $statuses);
        }
        $page = $query->orderByDesc('id')->paginate(20);

        return ApiResponse::success($page->getCollection()->map(fn (HarmfulWord $word) => $word->snapshot())->all(), [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
            'total' => $page->total(), 'per_page' => $page->perPage(),
            'can_manage' => $request->user()->can(AccessPermissions::BEAULAB_HARMFUL_WORD_MANAGE),
        ]);
    }

    public function check(HarmfulWordRequest $request, HarmfulWordMatcher $matcher)
    {
        $rules = $matcher->rulesForScope($request->validated('scope'));
        $matches = $matcher->findMatches($request->validated('text'), $rules);

        return ApiResponse::success(['allowed' => ! $matches, 'matches' => $matches, 'active_rule_count' => count($rules)]);
    }

    public function validateWords(HarmfulWordRequest $request, HarmfulWordRules $rules)
    {
        return ApiResponse::success($rules->analyze($request->validated('text')));
    }

    public function store(HarmfulWordRequest $request, HarmfulWordManageAction $action)
    {
        return ApiResponse::success($action->create($request->validated(), $request->user()));
    }

    public function update(HarmfulWordRequest $request, HarmfulWordManageAction $action)
    {
        $action->mutate($request->validated(), $request->user());

        return ApiResponse::success();
    }
}
