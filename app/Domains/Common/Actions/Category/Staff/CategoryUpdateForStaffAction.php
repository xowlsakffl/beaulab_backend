<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Queries\Category\Staff\CategoryUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class CategoryUpdateForStaffAction
{
    public function __construct(
        private readonly CategoryUpdateForStaffQuery $query,
    ) {}

    public function execute(Category $category, array $payload): array
    {
        Gate::authorize('update', $category);

        if (array_key_exists('domain', $payload) && (string) $payload['domain'] !== (string) $category->domain) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '도메인 변경은 지원하지 않습니다.');
        }

        $parent = $category->parent()->first();
        $name = array_key_exists('name', $payload)
            ? trim((string) $payload['name'])
            : (string) $category->name;

        if ($name === '') {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '카테고리명은 비워둘 수 없습니다.');
        }

        $exists = Category::query()
            ->domain((string) $category->domain)
            ->where('parent_id', $category->parent_id)
            ->where('name', $name)
            ->whereKeyNot($category->id)
            ->exists();

        if ($exists) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '같은 상위 카테고리 아래 동일한 이름이 이미 존재합니다.');
        }

        $parentPath = $parent ? trim((string) ($parent->full_path ?: $parent->name)) : null;
        $newFullPath = $parentPath ? "{$parentPath} > {$name}" : $name;
        $oldFullPath = (string) ($category->full_path ?: $category->name);

        $normalizedCode = array_key_exists('code', $payload)
            ? (trim((string) ($payload['code'] ?? '')) ?: null)
            : $category->code;

        $updated = DB::transaction(function () use ($category, $payload, $name, $newFullPath, $oldFullPath, $normalizedCode) {
            $updatedCategory = $this->query->update($category, [
                'name' => $name,
                'code' => $normalizedCode,
                'full_path' => $newFullPath,
                'sort_order' => array_key_exists('sort_order', $payload) ? (int) $payload['sort_order'] : $category->sort_order,
                'status' => array_key_exists('status', $payload) ? (string) $payload['status'] : $category->status,
                'is_menu_visible' => array_key_exists('is_menu_visible', $payload) ? (bool) $payload['is_menu_visible'] : $category->is_menu_visible,
            ]);

            if ($oldFullPath !== $newFullPath) {
                $this->syncDescendantPaths((string) $updatedCategory->domain, $oldFullPath, $newFullPath);
            }

            return $updatedCategory->fresh();
        });

        Log::info('카테고리 수정', [
            'category_id' => $updated->id,
            'domain' => $updated->domain,
            'name' => $updated->name,
        ]);

        return [
            'category' => $this->toArray($updated),
        ];
    }

    private function syncDescendantPaths(string $domain, string $oldPrefix, string $newPrefix): void
    {
        $descendants = Category::query()
            ->domain($domain)
            ->where('full_path', 'like', $oldPrefix . ' > %')
            ->orderBy('depth')
            ->get();

        foreach ($descendants as $descendant) {
            $currentPath = (string) ($descendant->full_path ?? '');

            $nextPath = preg_replace(
                '/^' . preg_quote($oldPrefix, '/') . '/',
                $newPrefix,
                $currentPath,
                1
            );

            if ($nextPath === null || $nextPath === $currentPath) {
                continue;
            }

            $descendant->forceFill([
                'full_path' => $nextPath,
            ])->save();
        }
    }

    private function toArray(Category $category): array
    {
        return [
            'id' => (int) $category->id,
            'domain' => (string) $category->domain,
            'parent_id' => $category->parent_id !== null ? (int) $category->parent_id : null,
            'depth' => (int) $category->depth,
            'name' => (string) $category->name,
            'code' => $category->code,
            'full_path' => $category->full_path,
            'sort_order' => (int) $category->sort_order,
            'status' => (string) $category->status,
            'is_menu_visible' => (bool) $category->is_menu_visible,
            'created_at' => optional($category->created_at)?->toISOString(),
            'updated_at' => optional($category->updated_at)?->toISOString(),
        ];
    }
}

