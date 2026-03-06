<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Common\Queries\Category\Staff\CategoryCreateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class CategoryCreateForStaffAction
{
    public function __construct(
        private readonly CategoryCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Category::class);

        $domain = (string) $payload['domain'];
        $name = trim((string) $payload['name']);

        if (! in_array($domain, Category::domains(), true)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 카테고리 도메인입니다.');
        }

        if ($name === '') {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '카테고리명은 필수입니다.');
        }

        $parent = null;
        if (! empty($payload['parent_id'])) {
            $parent = Category::query()
                ->domain($domain)
                ->find((int) $payload['parent_id']);

            if (! $parent) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '상위 카테고리를 찾을 수 없습니다.');
            }

            if ((int) $parent->depth >= 3) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '소분류(3단계) 아래에는 카테고리를 추가할 수 없습니다.');
            }
        }

        $depth = $parent ? ((int) $parent->depth + 1) : 1;
        $fullPath = $parent
            ? trim((string) ($parent->full_path ?: $parent->name)) . ' > ' . $name
            : $name;

        $exists = Category::query()
            ->domain($domain)
            ->where('parent_id', $parent?->id)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '같은 상위 카테고리 아래 동일한 이름이 이미 존재합니다.');
        }

        $normalizedCode = array_key_exists('code', $payload) && $payload['code'] !== null
            ? trim((string) $payload['code'])
            : null;

        $icon = $payload['icon'] ?? null;
        if (! $icon instanceof UploadedFile) {
            $icon = null;
        }

        $category = DB::transaction(function () use ($domain, $parent, $depth, $name, $normalizedCode, $fullPath, $payload, $icon) {
            $created = $this->query->create([
                'domain' => $domain,
                'parent_id' => $parent?->id,
                'depth' => $depth,
                'name' => $name,
                'code' => $normalizedCode !== '' ? $normalizedCode : null,
                'full_path' => $fullPath,
                'sort_order' => (int) ($payload['sort_order'] ?? 0),
                'status' => (string) ($payload['status'] ?? Category::STATUS_ACTIVE),
                'is_menu_visible' => (bool) ($payload['is_menu_visible'] ?? true),
            ]);

            if ($icon) {
                $this->mediaAttachAction->attachOne($created, $icon, 'icon', 'category', 'icon', true);
            }

            return $created->fresh();
        });

        Log::info('카테고리 생성', [
            'category_id' => $category->id,
            'domain' => $category->domain,
            'name' => $category->name,
            'depth' => $category->depth,
        ]);

        return [
            'category' => $this->toArray($category->load('iconMedia')),
        ];
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
