<?php

namespace App\Domains\Common\Category\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Dto\Staff\CategoryForStaffDto;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Queries\Staff\CategoryUpdateForStaffQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * CategoryUpdateForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class CategoryUpdateForStaffAction
{
    public function __construct(
        private readonly CategoryUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly CategoryUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(Category $category, array $payload): array
    {
        Gate::authorize('update', $category);

        $parent = $this->query->parent($category);
        $name = array_key_exists('name', $payload)
            ? trim((string) $payload['name'])
            : (string) $category->name;

        $exists = $this->query->existsSiblingName($category, $name);

        if ($exists) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '같은 상위 카테고리 아래 동일한 이름이 이미 존재합니다.');
        }

        $parentPath = $parent ? trim((string) ($parent->full_path ?: $parent->name)) : null;
        $newFullPath = $parentPath ? "{$parentPath} > {$name}" : $name;
        $oldFullPath = (string) ($category->full_path ?: $category->name);
        $oldGroupCode = $category->group_code !== null ? (string) $category->group_code : null;
        $groupCode = $this->resolveGroupCode($payload, $parent, $oldGroupCode);

        $normalizedCode = array_key_exists('code', $payload)
            ? (trim((string) ($payload['code'] ?? '')) ?: null)
            : $category->code;

        $updated = DB::transaction(function () use ($category, $payload, $name, $newFullPath, $oldFullPath, $oldGroupCode, $groupCode, $normalizedCode) {
            $before = $this->historyRecordAction->capture($category);
            $updatedCategory = $this->query->update($category, [
                'name' => $name,
                'code' => $normalizedCode,
                'full_path' => $newFullPath,
                'group_code' => $groupCode,
                'sort_order' => array_key_exists('sort_order', $payload) ? (int) $payload['sort_order'] : $category->sort_order,
                'status' => array_key_exists('status', $payload) ? (string) $payload['status'] : $category->status,
                'is_menu_visible' => array_key_exists('is_menu_visible', $payload) ? (bool) $payload['is_menu_visible'] : $category->is_menu_visible,
            ]);

            if ($oldFullPath !== $newFullPath) {
                $this->query->syncDescendantPaths((string) $updatedCategory->domain, $oldFullPath, $newFullPath);
            }

            if ($oldGroupCode !== $groupCode) {
                $this->query->syncDescendantGroupCode((string) $updatedCategory->domain, $newFullPath, $groupCode);
            }

            $this->replaceIcon($updatedCategory, $payload);

            $updatedCategory = $updatedCategory->fresh(['parent', 'iconMedia']);
            $this->historyRecordAction->recordUpdated($updatedCategory, $before);

            return $updatedCategory;
        });

        return [
            'category' => CategoryForStaffDto::fromModel($updated->load('iconMedia'))->toArray(),
        ];
    }

    private function replaceIcon(Category $category, array $payload): void
    {
        $icon = $payload['icon'] ?? null;
        if (! $icon instanceof UploadedFile) {
            return;
        }

        $this->mediaAttachAction->deleteCollectionMedia($category, 'icon');
        $this->mediaAttachAction->attachOne($category, $icon, 'icon', 'category', 'icon', true);
    }

    private function resolveGroupCode(array $payload, ?Category $parent, ?string $currentGroupCode): ?string
    {
        $requested = array_key_exists('group_code', $payload)
            ? (trim((string) ($payload['group_code'] ?? '')) ?: null)
            : $currentGroupCode;

        if (! $parent) {
            return $requested;
        }

        $parentGroupCode = $parent->group_code !== null ? (string) $parent->group_code : null;
        if ($requested !== null && $requested !== $parentGroupCode) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '하위 카테고리는 상위 카테고리와 같은 그룹만 사용할 수 있습니다.');
        }

        return $parentGroupCode;
    }
}
