<?php

namespace App\Domains\Common\Category\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryTopic;
use App\Domains\Common\Category\Queries\CategoryTreeWriteQuery;
use App\Domains\Common\Category\Queries\Staff\CategoryDeleteForStaffQuery;
use App\Domains\Common\Category\Support\CategoryUsageAvailability;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdSlotAvailabilityForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class CategoryManagementAction
{
    public function show(string $domain): array
    {
        $this->authorize();
        $categories = Category::query()->domain($domain)->with('topics')->orderBy('id')->get();
        $resolved = app(CategoryUsageAvailability::class)->resolve($categories);
        $counts = DB::table('category_assignments')->whereIn('category_id', $categories->modelKeys())
            ->selectRaw('category_id, COUNT(*) as total')->groupBy('category_id')->pluck('total', 'category_id');
        if ($domain === Category::DOMAIN_HOSPITAL_DEPARTMENT) {
            $departmentCounts = DB::table('hospitals')->selectRaw('department, COUNT(*) as total')->groupBy('department')->pluck('total', 'department');
            $counts = $categories->mapWithKeys(fn ($row) => [$row->id => (int) ($departmentCounts[$row->code] ?? 0)]);
        }
        $rows = $categories->map(fn ($category) => [
            'id' => $category->id, 'parent_id' => $category->parent_id, 'depth' => $category->depth,
            'name' => $category->name, 'code' => $category->code, 'full_path' => $category->full_path,
            'group_code' => $category->group_code, 'sort_order' => $category->sort_order, 'status' => $category->status,
            'is_menu_visible' => $category->is_menu_visible, 'topic_ids' => $category->topics->modelKeys(),
            'assignment_count' => (int) ($counts[$category->id] ?? 0), ...$resolved[$category->id],
        ])->all();

        return [
            'version' => hash('sha256', json_encode($rows)), 'rows' => $rows,
            'topics' => CategoryTopic::orderBy('sort_order')->orderBy('id')->get()->toArray(),
            'can_manage' => Gate::allows(AccessPermissions::BEAULAB_CATEGORY_MANAGE),
            'can_status' => Gate::allows(AccessPermissions::BEAULAB_CATEGORY_STATUS_UPDATE),
        ];
    }

    public function save(array $input): array
    {
        $this->authorize();

        return DB::transaction(function () use ($input) {
            CategoryTopic::orderBy('id')->lockForUpdate()->get();
            app(HospitalEventAdSlotAvailabilityForStaffQuery::class)->lockBookings();
            Category::query()->domain($input['domain'])->orderBy('id')->lockForUpdate()->get();
            $snapshot = $this->show($input['domain']);
            if (! hash_equals($snapshot['version'], $input['version'])) {
                $this->fail('다른 관리자가 분류를 변경했습니다. 목록을 다시 확인해 주세요.');
            }
            $op = $input['operation'];
            $statusOnly = $op === 'update' && ! array_intersect(['name', 'parent_id'], array_keys($input));
            Gate::authorize($statusOnly ? AccessPermissions::BEAULAB_CATEGORY_STATUS_UPDATE : AccessPermissions::BEAULAB_CATEGORY_MANAGE);
            if (array_key_exists('status', $input)) {
                Gate::authorize(AccessPermissions::BEAULAB_CATEGORY_STATUS_UPDATE);
            }
            if ($op === 'create') {
                $parent = ! empty($input['parent_id']) ? $this->category($input['domain'], $input['parent_id']) : null;
                $group = $parent?->group_code ?? ($input['group_code'] ?? null);
                $flat = in_array($input['domain'], [Category::DOMAIN_HOSPITAL_DEPARTMENT, Category::DOMAIN_HOSPITAL_SUBJECT,
                    Category::DOMAIN_DOCTOR_SUBJECT, Category::DOMAIN_TALK, Category::DOMAIN_HOSPITAL_EVALUATION], true);
                if ($flat && $parent) {
                    $this->fail('이 업무는 한 단계 분류로 관리합니다.');
                }
                if (in_array($input['domain'], Category::medicalDomains(), true) && ! in_array($group, Category::groupCodes(), true)) {
                    $this->fail('성형·쁘띠·기획전 중 분류를 선택해 주세요.');
                }
                if ($group === 'PROMOTION' && $input['domain'] !== Category::DOMAIN_HOSPITAL_MEDICAL) {
                    $this->fail('기획전은 이벤트에서 관리합니다.');
                }
                app(CategoryCreateForStaffAction::class)->execute([
                    'domain' => $input['domain'], 'parent_id' => $parent?->id, 'group_code' => $group,
                    'name' => $input['name'], 'code' => 'C_'.Str::upper((string) Str::ulid()),
                    'sort_order' => Category::query()->domain($input['domain'])->where('parent_id', $parent?->id)->max('sort_order') + 1,
                ]);
            } elseif ($op === 'reorder') {
                $ids = array_map('intval', $input['ids']);
                $categories = Category::query()->domain($input['domain'])->whereIn('id', $ids)->get();
                if ($categories->count() !== count($ids) || $categories->pluck('parent_id')->unique()->count() !== 1
                    || $categories->pluck('group_code')->unique()->count() !== 1) {
                    $this->fail('같은 상위 분류 안에서만 순서를 변경할 수 있습니다.');
                }
                $expected = Category::query()->domain($input['domain'])->where('parent_id', $categories->first()->parent_id)
                    ->where('group_code', $categories->first()->group_code)->pluck('id')->all();
                $actual = $ids;
                sort($expected);
                sort($actual);
                if ($expected !== $actual) {
                    $this->fail('현재 목록 전체 순서를 보내 주세요.');
                }
                foreach ($ids as $order => $id) {
                    $categories->firstWhere('id', $id)->update(['sort_order' => $order + 1]);
                }
                $this->history($categories->first(), '순서 변경', ['ids' => $ids]);
            } else {
                $category = $this->category($input['domain'], $input['id']);
                if ($op === 'update') {
                    if ($statusOnly) {
                        $before = $category->only(['status']);
                        $category->update(array_intersect_key($input, array_flip(['status'])));
                        $this->history($category, '분류 등록 사용 설정', ['before' => $before, 'after' => $category->only(['status'])]);
                    } else {
                        app(CategoryUpdateForStaffAction::class)->execute($category, array_intersect_key($input, array_flip(['name', 'status'])));
                    }
                } elseif ($op === 'move') {
                    $this->move($category, $input['parent_id'] ?? null, $input['name'] ?? null);
                } elseif ($op === 'delete') {
                    $query = app(CategoryDeleteForStaffQuery::class);
                    if ($query->hasChildren($category) || $query->hasAssignments($category) || $category->topics()->exists()) {
                        $this->fail('하위 분류 또는 연결된 데이터·예약·앱 메뉴가 있어 삭제할 수 없습니다.');
                    }
                    $this->history($category, '분류 삭제', ['name' => $category->name, 'code' => $category->code]);
                    $category->topics()->detach();
                    $category->usages()->delete();
                    app(CategoryDeleteForStaffAction::class)->execute($category);
                }
            }
            CategoryUsageAvailability::forgetRequestCache();
            DB::afterCommit(fn () => \App\Domains\HospitalEventAd\Support\HospitalEventAdCalendarCache::flush());

            return $this->show($input['domain']);
        }, 3);
    }

    private function move(Category $category, ?int $parentId, ?string $name = null): void
    {
        if ($category->group_code === Category::GROUP_PROMOTION || in_array($category->domain, [Category::DOMAIN_HOSPITAL_DEPARTMENT, Category::DOMAIN_HOSPITAL_SUBJECT, Category::DOMAIN_DOCTOR_SUBJECT, Category::DOMAIN_TALK, Category::DOMAIN_HOSPITAL_EVALUATION], true)) {
            $this->fail('한 단계 분류는 상위를 변경할 수 없습니다.');
        }
        $parent = $parentId ? $this->category($category->domain, $parentId) : null;
        if ($parent && ($parent->group_code !== $category->group_code || $parent->id === $category->id)) {
            $this->fail('같은 그룹의 다른 분류를 선택해 주세요.');
        }
        $node = $parent;
        while ($node) {
            if ($node->id === $category->id) {
                $this->fail('자신의 하위 분류로 이동할 수 없습니다.');
            }
            $node = $node->parent;
        }
        $subtree = [$category];
        for ($i = 0; $i < count($subtree); $i++) {
            foreach ($subtree[$i]->children as $child) {
                $subtree[] = $child;
            }
        }
        $delta = ($parent ? $parent->depth + 1 : 1) - $category->depth;
        foreach ($subtree as $row) {
            if ($row->depth + $delta > 3) {
                $this->fail('최대 3단계까지 이동할 수 있습니다.');
            }
            if (app(CategoryDeleteForStaffQuery::class)->hasAssignments($row)) {
                $this->fail('콘텐츠·가격·예약이 연결된 분류는 상위를 바로 바꿀 수 없습니다. 연결 이전을 먼저 확인해 주세요.');
            }
        }
        $name = $name === null ? $category->name : trim($name);
        if ($name === '' || mb_strlen($name) > 120 || str_contains($name, '>')) {
            $this->fail('카테고리명은 > 기호 없이 1~120자로 입력해 주세요.');
        }
        if (Category::where('domain', $category->domain)->where('group_code', $category->group_code)->where('parent_id', $parent?->id)->where('name', $name)->whereKeyNot($category->id)->exists()) {
            $this->fail('이동할 위치에 같은 이름이 이미 있습니다.');
        }
        $newPath = $parent ? $parent->full_path.' > '.$name : $name;
        foreach ($subtree as $row) {
            if (mb_strlen($row->full_path) - mb_strlen($category->full_path) + mb_strlen($newPath) > 255) {
                $this->fail('이동 후 전체 이름이 255자를 넘습니다.');
            }
        }
        $history = app(CategoryUpdateHistoryRecordAction::class);
        $before = $history->capture($category);
        $category->update(['name' => $name, 'parent_id' => $parent?->id, 'full_path' => $newPath]);
        foreach ($subtree as $row) {
            $row->update(['depth' => $row->depth + $delta]);
        }
        app(CategoryTreeWriteQuery::class)->syncDescendants($category);
        $history->recordUpdated($category->fresh(['parent', 'iconMedia']), $before);
    }

    private function category(string $domain, int $id): Category
    {
        return Category::query()->domain($domain)->findOrFail($id);
    }

    private function history(Category $category, string $label, array $metadata): void
    {
        app(OperationHistoryCreateAction::class)->execute(target: $category, action: OperationHistory::ACTION_UPDATED,
            actor: auth()->user(), metadata: ['source' => 'staff.category.management', 'label' => $label, ...$metadata]);
    }

    private function authorize(): void
    {
        if (! Gate::allows(AccessPermissions::BEAULAB_CATEGORY_MANAGE) && ! Gate::allows(AccessPermissions::BEAULAB_CATEGORY_STATUS_UPDATE)) {
            abort(403);
        }
    }

    private function fail(string $message): never
    {
        throw new CustomException(ErrorCode::INVALID_REQUEST, $message);
    }
}
