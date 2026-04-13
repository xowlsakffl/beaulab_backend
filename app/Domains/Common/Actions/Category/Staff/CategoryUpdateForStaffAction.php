<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Common\Queries\Category\Staff\CategoryUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * CategoryUpdateForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class CategoryUpdateForStaffAction
{
    public function __construct(
        private readonly CategoryUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
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

            $this->replaceIcon($updatedCategory, $payload);

            return $updatedCategory->fresh();
        });

        Log::info('카테고리 수정', [
            'category_id' => $updated->id,
            'domain' => $updated->domain,
            'name' => $updated->name,
        ]);

        return [
            'category' => $this->toArray($updated->load('iconMedia')),
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
            'icon' => $this->formatMedia($category->relationLoaded('iconMedia') ? $category->iconMedia : null),
            'created_at' => optional($category->created_at)?->toISOString(),
            'updated_at' => optional($category->updated_at)?->toISOString(),
        ];
    }

    private function formatMedia(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'collection' => (string) $media->collection,
            'disk' => (string) $media->disk,
            'path' => (string) $media->path,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'sort_order' => (int) $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => optional($media->created_at)?->toISOString(),
            'updated_at' => optional($media->updated_at)?->toISOString(),
        ];
    }
}
