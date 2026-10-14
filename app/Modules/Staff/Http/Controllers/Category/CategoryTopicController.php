<?php

namespace App\Modules\Staff\Http\Controllers\Category;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Category\Actions\Staff\CategoryTopicManagementAction;
use App\Domains\Common\Category\Models\CategoryTopic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CategoryTopicController extends Controller
{
    public function index(CategoryTopicManagementAction $action)
    {
        return ApiResponse::success($action->show());
    }

    public function save(Request $request, CategoryTopicManagementAction $action)
    {
        $input = $request->validate([
            'operation' => ['required', Rule::in(['create', 'update', 'delete', 'reorder'])],
            'version' => ['required', 'string', 'size:64'],
            'id' => ['prohibited_if:operation,create,reorder', 'required_if:operation,update,delete', 'integer', 'min:1'],
            'name' => ['required_if:operation,create', 'string', 'min:1', 'max:120'],
            'group_code' => ['sometimes', 'nullable', Rule::in(['SURGERY', 'TREATMENT'])],
            'is_menu_visible' => ['sometimes', 'boolean'],
            'category_ids' => ['sometimes', 'array', 'max:3000'],
            'category_ids.*' => ['integer', 'distinct', 'min:1'],
            'ids' => ['required_if:operation,reorder', 'array', 'min:1', 'max:3000'],
            'ids.*' => ['integer', 'distinct', 'min:1'],
        ]);

        return ApiResponse::success($action->save($input));
    }

    public function selector()
    {
        // 콘텐츠 등록에서도 사용하며 설정 관리 권한을 요구하지 않는다. staff 인증은 라우트 그룹에서 적용한다.
        return ApiResponse::success(['items' => CategoryTopic::orderBy('sort_order')->orderBy('id')->get(['id', 'code', 'name', 'group_code'])]);
    }
}
