<?php

namespace App\Domains\Common\Category\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Category\Queries\Staff\CategoryDeleteForStaffQuery;
use App\Domains\Common\Category\Support\CategoryChangeGuard;
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
        $categories = Category::query()->domain($domain)->with(['usages' => fn ($q) => $q->orderBy('id')])->orderBy('id')->get();
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
            'is_menu_visible' => $category->is_menu_visible,
            'assignment_count' => (int) ($counts[$category->id] ?? 0),
            ...$resolved[$category->id],
        ])->all();

        return [
            'version' => hash('sha256', $categories->toJson()), 'rows' => $rows,
            'usages' => collect(CategoryUsageAvailability::OPTIONS)->except('HOSPITAL_DOCTOR_SUBJECT')->map(fn ($value, $key) => ['value' => $key, ...$value])->values()->all(),
            'can_manage' => Gate::allows(AccessPermissions::BEAULAB_CATEGORY_MANAGE),
            'can_status' => Gate::allows(AccessPermissions::BEAULAB_CATEGORY_STATUS_UPDATE),
        ];
    }

    public function save(array $input): array
    {
        $this->authorize();

        return DB::transaction(function () use ($input) {
            app(HospitalEventAdSlotAvailabilityForStaffQuery::class)->lockBookings();
            Category::query()->domain($input['domain'])->orderBy('id')->lockForUpdate()->get();
            $snapshot = $this->show($input['domain']);
            if (! hash_equals($snapshot['version'], $input['version'])) {
                $this->fail('다른 관리자가 분류를 변경했습니다. 목록을 새로 연 뒤 다시 시도해 주세요.');
            }
            $op = $input['operation'];
            Gate::authorize($op === 'usage' || ($op === 'update' && isset($input['status']))
                ? AccessPermissions::BEAULAB_CATEGORY_STATUS_UPDATE : AccessPermissions::BEAULAB_CATEGORY_MANAGE);
            if ($op === 'create') {
                $parent = ! empty($input['parent_id']) ? $this->category($input['domain'], $input['parent_id']) : null;
                $group = $parent?->group_code ?? ($input['group_code'] ?? null);
                if ($input['domain'] === Category::DOMAIN_HOSPITAL_DEPARTMENT && $parent) {
                    $this->fail('분과는 하위 분류를 만들 수 없습니다.');
                }
                if ($input['domain'] === Category::DOMAIN_HOSPITAL_MEDICAL && ! in_array($group, Category::groupCodes(), true)) {
                    $this->fail('성형·쁘띠·기획전 중 분류를 선택해 주세요.');
                }
                foreach ($input['usages'] ?? [] as $usage) {
                    if ($input['domain'] !== Category::DOMAIN_HOSPITAL_MEDICAL || ! in_array($group, CategoryUsageAvailability::OPTIONS[$usage]['groups'] ?? [], true)) {
                        $this->fail('이 분류에서 사용할 수 없는 사용처입니다.');
                    }
                }
                $created = app(CategoryCreateForStaffAction::class)->execute([
                    'domain' => $input['domain'], 'parent_id' => $parent?->id, 'group_code' => $group,
                    'name' => $input['name'], 'code' => 'CAT_'.Str::upper((string) Str::ulid()),
                    'sort_order' => Category::query()->domain($input['domain'])->where('parent_id', $parent?->id)->max('sort_order') + 1,
                ]);
                $category = Category::findOrFail($created['category']['id']);
                foreach (CategoryUsageAvailability::OPTIONS as $usage => $option) {
                    if ($category->domain !== Category::DOMAIN_HOSPITAL_MEDICAL || ! in_array($group, $option['groups'], true)) {
                        continue;
                    }
                    $enabled = in_array($usage, $input['usages'] ?? [], true);
                    if ($enabled) {
                        Gate::authorize(AccessPermissions::BEAULAB_CATEGORY_STATUS_UPDATE);
                        $parentRow = $parent ? collect($snapshot['rows'])->firstWhere('id', $parent->id) : null;
                        if ($parent && ! $parentRow['usages'][$usage]['enabled']) {
                            $this->fail('상위 분류에서 사용하지 않는 사용처는 선택할 수 없습니다.');
                        }
                    }
                    CategoryUsage::create(['category_id' => $category->id, 'usage' => $usage,
                        'status' => $enabled ? 'ACTIVE' : 'INACTIVE', 'is_menu_visible' => $enabled, 'sort_order' => $category->sort_order]);
                }
            } elseif ($op === 'reorder') {
                $ids = array_map('intval', $input['ids']);
                $categories = Category::query()->domain($input['domain'])->whereIn('id', $ids)->get();
                if ($categories->count() !== count($ids) || $categories->pluck('parent_id')->unique()->count() !== 1
                    || $categories->pluck('group_code')->unique()->count() !== 1) {
                    $this->fail('같은 상위 분류 안에서만 순서를 변경할 수 있습니다.');
                }
                $siblings = Category::query()->domain($input['domain'])->where('parent_id', $categories->first()->parent_id)
                    ->where('group_code', $categories->first()->group_code)->pluck('id')->all();
                $expected = $siblings;
                $actual = $ids;
                sort($expected);
                sort($actual);
                if ($expected !== $actual) {
                    $this->fail('현재 목록 전체 순서를 보내 주세요.');
                }
                foreach ($ids as $order => $id) {
                    $category = $categories->firstWhere('id', $id);
                    if (! empty($input['usage'])) {
                        $this->assertUsage($category, $input['usage']);
                        $resolved = collect($snapshot['rows'])->firstWhere('id', $id)['usages'][$input['usage']];
                        CategoryUsage::updateOrCreate(['category_id' => $id, 'usage' => $input['usage']], [
                            'sort_order' => $order + 1, 'status' => $resolved['own_enabled'] ? 'ACTIVE' : 'INACTIVE', 'is_menu_visible' => $resolved['own_visible'],
                        ]);
                    } else {
                        $category->update(['sort_order' => $order + 1]);
                    }
                }
                $this->history($categories->first(), '순서 변경', ['usage' => $input['usage'] ?? null, 'ids' => $ids]);
            } else {
                $category = $this->category($input['domain'], $input['id']);
                if ($category->domain === Category::DOMAIN_HOSPITAL_MEDICAL && in_array($op, ['update', 'delete'], true) && empty($input['confirm_global'])) {
                    $this->fail('공통 원본 변경은 연결된 업무와 데이터에 미치는 영향을 확인한 뒤 적용해 주세요.');
                }
                if ($op === 'update') {
                    app(CategoryUpdateForStaffAction::class)->execute($category, array_intersect_key($input, array_flip(['name', 'status'])));
                } elseif ($op === 'label') {
                    $this->assertUsage($category, $input['usage']);
                    $resolved = collect($snapshot['rows'])->firstWhere('id', $category->id)['usages'][$input['usage']];
                    $label = trim((string) ($input['display_name'] ?? '')) ?: null;
                    CategoryUsage::updateOrCreate(['category_id' => $category->id, 'usage' => $input['usage']], [
                        'display_name' => $label,
                        'status' => $resolved['own_enabled'] ? 'ACTIVE' : 'INACTIVE',
                        'is_menu_visible' => $resolved['own_visible'], 'sort_order' => $resolved['sort_order'],
                    ]);
                    $this->history($category, '업무별 표시명 변경', ['usage' => $input['usage'], 'before' => $resolved['display_name'], 'after' => $label]);
                } elseif ($op === 'usage') {
                    $this->assertUsage($category, $input['usage']);
                    if (! $input['enabled'] || ! $input['visible']) {
                        if (str_contains($input['usage'], 'EVENT')) {
                            app(CategoryChangeGuard::class)->assertNoPaidAds($category);
                        }
                    }
                    $resolved = collect($snapshot['rows'])->firstWhere('id', $category->id)['usages'][$input['usage']];
                    if ($input['enabled'] && (! $resolved['parent_enabled'] || $category->status !== 'ACTIVE')) {
                        $this->fail('상위 분류와 현재 분류를 먼저 사용 상태로 변경해 주세요.');
                    }
                    CategoryUsage::updateOrCreate(['category_id' => $category->id, 'usage' => $input['usage']], [
                        'status' => $input['enabled'] ? 'ACTIVE' : 'INACTIVE', 'is_menu_visible' => $input['visible'], 'sort_order' => $resolved['sort_order'],
                        'display_name' => array_key_exists('display_name', $input) ? (trim((string) $input['display_name']) ?: null) : $resolved['display_name'],
                    ]);
                    $this->history($category, '업무별 설정', ['usage' => $input['usage'], 'before' => $resolved, 'enabled' => $input['enabled'], 'visible' => $input['visible'], 'display_name' => $input['display_name'] ?? null]);
                } elseif ($op === 'delete') {
                    $query = app(CategoryDeleteForStaffQuery::class);
                    if ($query->hasChildren($category)) {
                        $this->fail('하위 분류가 있어 삭제할 수 없습니다.');
                    }
                    if ($query->hasAssignments($category)) {
                        $this->fail('연결된 데이터 또는 가격·노출 설정이 있어 삭제할 수 없습니다. 사용 중지로 변경해 주세요.');
                    }
                    $this->history($category, '분류 삭제', ['name' => $category->name, 'code' => $category->code]);
                    $category->usages()->delete();
                    app(CategoryDeleteForStaffAction::class)->execute($category);
                }
            }

            CategoryUsageAvailability::forgetRequestCache();
            DB::afterCommit(fn () => \App\Domains\HospitalEventAd\Support\HospitalEventAdCalendarCache::flush());

            return $this->show($input['domain']);
        }, 3);
    }

    private function category(string $domain, int $id): Category
    {
        return Category::query()->domain($domain)->findOrFail($id);
    }

    private function assertUsage(Category $category, string $usage): void
    {
        if ($category->domain !== Category::DOMAIN_HOSPITAL_MEDICAL || ! in_array($category->group_code, CategoryUsageAvailability::OPTIONS[$usage]['groups'] ?? [], true)) {
            $this->fail('이 분류에서 사용할 수 없는 사용처입니다.');
        }
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
