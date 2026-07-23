<?php

namespace App\Domains\Common\Category\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Dto\Staff\CategoryForStaffDto;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Queries\Staff\CategoryCreateForStaffQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * CategoryCreateForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class CategoryCreateForStaffAction
{
    public function __construct(
        private readonly CategoryCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly CategoryUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Category::class);

        $domain = (string) $payload['domain'];
        $name = trim((string) $payload['name']);

        $parent = null;
        if (! empty($payload['parent_id'])) {
            $parent = $this->query->findParent($domain, (int) $payload['parent_id']);

            if (! $parent) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '상위 카테고리를 찾을 수 없습니다.');
            }

            if ((int) $parent->depth >= 3) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '소분류(3단계) 아래에는 카테고리를 추가할 수 없습니다.');
            }
        }

        $depth = $parent ? ((int) $parent->depth + 1) : 1;
        $groupCode = $this->resolveGroupCode($payload, $parent);
        $fullPath = $parent
            ? trim((string) ($parent->full_path ?: $parent->name)).' > '.$name
            : $name;

        $exists = $this->query->existsSiblingName($domain, $parent?->id, $name);

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

        $category = DB::transaction(function () use ($domain, $parent, $depth, $groupCode, $name, $normalizedCode, $fullPath, $payload, $icon) {
            $created = $this->query->create([
                'domain' => $domain,
                'parent_id' => $parent?->id,
                'depth' => $depth,
                'group_code' => $groupCode,
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

            $created = $created->fresh(['parent', 'iconMedia']);
            $this->historyRecordAction->recordCreated($created);

            return $created;
        });

        Log::info('카테고리 생성', [
            'category_id' => $category->id,
            'domain' => $category->domain,
            'name' => $category->name,
            'depth' => $category->depth,
        ]);

        return [
            'category' => CategoryForStaffDto::fromModel($category->load('iconMedia'))->toArray(),
        ];
    }

    private function resolveGroupCode(array $payload, ?Category $parent): ?string
    {
        $requested = array_key_exists('group_code', $payload)
            ? (trim((string) ($payload['group_code'] ?? '')) ?: null)
            : null;

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
