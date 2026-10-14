<?php

namespace App\Modules\User\Http\Controllers\Category;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryTopic;
use App\Domains\Common\Category\Queries\CategoryDiscoveryQuery;
use App\Domains\Common\Category\Support\CategoryHierarchy;
use App\Domains\Common\Category\Support\CategoryUsageAvailability;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CategoryDiscoveryController extends Controller
{
    public function categories(Request $request)
    {
        $input = $request->validate(['domain' => ['required', Rule::in(Category::domains())], 'mode' => ['sometimes', Rule::in(['selection', 'menu'])]]);
        if (($input['mode'] ?? 'selection') === 'menu') {
            $selected = Category::where('domain', $input['domain'])
                ->whereHas('topics', fn ($q) => $q->where('is_menu_visible', true))->pluck('id')->all();
            $ids = CategoryHierarchy::selectedAndDescendants($input['domain'], $selected);
        } else {
            $ids = app(CategoryUsageAvailability::class)->activeIdsForDomain($input['domain']);
        }

        return ApiResponse::success(['items' => Category::whereIn('id', $ids)->orderBy('sort_order')->orderBy('id')->get(['id', 'parent_id', 'depth', 'group_code', 'name', 'code', 'full_path'])]);
    }

    public function topics()
    {
        return ApiResponse::success(['items' => CategoryTopic::where('is_menu_visible', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'code', 'name', 'group_code'])]);
    }

    public function mapping(string $code, CategoryDiscoveryQuery $query)
    {
        $topic = $this->topic($code);
        $categories = Category::whereIn('id', $query->categoryIds($topic))->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'domain', 'parent_id', 'name', 'code']);

        return ApiResponse::success(['topic' => $topic->only(['id', 'code', 'name', 'group_code']),
            'categories' => $categories->map(fn ($category) => $category->only(['id', 'domain', 'parent_id', 'name', 'code']))->groupBy('domain')]);
    }

    public function contents(Request $request, string $code, CategoryDiscoveryQuery $query)
    {
        $input = $request->validate(['domain' => ['required', Rule::in(array_keys(CategoryDiscoveryQuery::DOMAINS))], 'page' => ['sometimes', 'integer', 'min:1', 'max:100000'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']]);
        $topic = $this->topic($code);
        $title = CategoryDiscoveryQuery::DOMAINS[$input['domain']][2];
        $rows = $query->contents($topic, $input['domain'])->orderByDesc('id')->paginate($input['per_page'] ?? 20, ['id', $title]);

        return ApiResponse::success(['items' => $rows->getCollection()->map(fn ($row) => ['id' => $row->id, 'title' => mb_substr(strip_tags((string) $row->{$title}), 0, 120)]),
            'meta' => ['current_page' => $rows->currentPage(), 'per_page' => $rows->perPage(), 'total' => $rows->total(), 'last_page' => $rows->lastPage()]]);
    }

    private function topic(string $code): CategoryTopic
    {
        return CategoryTopic::where('code', $code)->firstOrFail();
    }
}
