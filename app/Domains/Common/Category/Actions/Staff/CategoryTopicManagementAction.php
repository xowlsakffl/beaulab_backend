<?php

namespace App\Domains\Common\Category\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryTopic;
use App\Domains\Common\Category\Support\CategoryHierarchy;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CategoryTopicManagementAction
{
    public function show(): array
    {
        abort_unless(Gate::allows(AccessPermissions::BEAULAB_CATEGORY_MANAGE) || Gate::allows(AccessPermissions::BEAULAB_CATEGORY_STATUS_UPDATE), 403);
        $topics = CategoryTopic::with(['categories:id'])->orderBy('sort_order')->orderBy('id')->get();
        $categories = Category::whereIn('domain', Category::domains())->where('domain', '!=', Category::DOMAIN_HOSPITAL_DEPARTMENT)
            ->where(fn ($q) => $q->whereNull('group_code')->orWhere('group_code', '!=', 'PROMOTION'))
            ->orderBy('domain')->orderBy('depth')->orderBy('sort_order')->orderBy('id')->get(['id', 'domain', 'parent_id', 'depth', 'name', 'full_path', 'group_code', 'status']);
        $rows = $topics->map(fn ($topic) => [...$topic->only(['id', 'code', 'name', 'group_code', 'sort_order', 'is_menu_visible']),
            'category_ids' => $topic->categories->modelKeys(),
            'included_category_ids' => CategoryHierarchy::expandSelection($categories, $topic->categories->modelKeys()),
        ])->all();

        return ['version' => hash('sha256', json_encode([$rows, $categories])), 'rows' => $rows,
            'categories' => $categories->toArray(), 'can_manage' => Gate::allows(AccessPermissions::BEAULAB_CATEGORY_MANAGE)];
    }

    public function save(array $input): array
    {
        Gate::authorize(AccessPermissions::BEAULAB_CATEGORY_MANAGE);

        return DB::transaction(function () use ($input) {
            CategoryTopic::orderBy('id')->lockForUpdate()->get();
            Category::orderBy('id')->lockForUpdate()->get();
            if (! hash_equals($this->show()['version'], $input['version'])) {
                throw ValidationException::withMessages(['version' => '분류가 변경되었습니다. 목록을 다시 확인해 주세요.']);
            }
            if ($input['operation'] === 'reorder') {
                $ids = array_map('intval', $input['ids']);
                $expected = CategoryTopic::orderBy('id')->pluck('id')->all();
                $actual = $ids;
                sort($actual);
                if ($expected !== $actual) {
                    throw ValidationException::withMessages(['ids' => '현재 목록 전체 순서를 보내 주세요.']);
                }
                foreach ($ids as $order => $id) {
                    CategoryTopic::whereKey($id)->update(['sort_order' => $order + 1]);
                }
                $this->history(CategoryTopic::findOrFail($ids[0]), '앱 메뉴 순서 변경', null, ['ids' => $ids]);

                return $this->show();
            }
            $topic = ! empty($input['id']) ? CategoryTopic::findOrFail($input['id']) : new CategoryTopic;
            $before = $topic->exists ? $topic->toArray() : null;
            $beforeIds = $topic->exists ? $topic->categories()->pluck('categories.id')->all() : [];
            if ($input['operation'] === 'delete') {
                if ($topic->categories()->exists() || DB::table('category_topic_contents')->where('topic_id', $topic->id)->exists()) {
                    throw ValidationException::withMessages(['id' => '연결된 분류 또는 콘텐츠가 있어 삭제할 수 없습니다.']);
                }
                $this->history($topic, '앱 메뉴 삭제', $before, []);
                $topic->delete();
            } else {
                $ids = array_values(array_unique($input['category_ids'] ?? $beforeIds));
                $valid = collect($this->show()['categories'])->pluck('id')->all();
                if (array_diff($ids, $valid)) {
                    throw ValidationException::withMessages(['category_ids' => '연결할 업무 분류를 다시 선택해 주세요.']);
                }
                if (! $topic->exists) {
                    $topic->code = 'TOPIC_'.Str::upper((string) Str::ulid());
                    $topic->sort_order = (int) CategoryTopic::max('sort_order') + 1;
                }
                $topic->fill(array_intersect_key($input, array_flip(['name', 'group_code', 'is_menu_visible'])))->save();
                if (array_key_exists('category_ids', $input)) {
                    $topic->categories()->sync($ids);
                }
                $this->history($topic, '앱 메뉴 저장', ['topic' => $before, 'category_ids' => $beforeIds], ['topic' => $topic->toArray(), 'category_ids' => $ids]);
            }

            return $this->show();
        }, 3);
    }

    private function history(CategoryTopic $topic, string $label, mixed $before, array $after): void
    {
        app(OperationHistoryCreateAction::class)->execute(target: $topic, action: OperationHistory::ACTION_UPDATED,
            actor: auth()->user(), metadata: ['label' => $label, 'before' => $before, 'after' => $after]);
    }
}
