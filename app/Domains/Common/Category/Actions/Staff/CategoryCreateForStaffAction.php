<?php

namespace App\Domains\Common\Category\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Dto\Staff\CategoryForStaffDto;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Queries\CategoryTreeWriteQuery;
use App\Domains\Common\Category\Queries\Staff\CategoryCreateForStaffQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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
        private readonly CategoryTreeWriteQuery $tree,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Category::class);
        if (array_key_exists('status', $payload) || array_key_exists('is_menu_visible', $payload)) {
            Gate::authorize('updateStatus', Category::class);
        }

        $domain = (string) $payload['domain'];
        $name = trim((string) $payload['name']);

        try {
            $category = DB::transaction(function () use ($domain, $name, $payload) {
                \App\Domains\Common\Category\Models\CategoryTopic::orderBy('id')->lockForUpdate()->get();
                $parent = null;
                if (! empty($payload['parent_id'])) {
                    $parent = $this->tree->lockPath((int) $payload['parent_id']);

                    if ($parent->domain !== $domain) {
                        throw new CustomException(ErrorCode::INVALID_REQUEST, '상위 카테고리를 찾을 수 없습니다.');
                    }

                    if ((int) $parent->depth >= 3) {
                        throw new CustomException(ErrorCode::INVALID_REQUEST, '소분류(3단계) 아래에는 카테고리를 추가할 수 없습니다.');
                    }
                }

                $depth = $parent ? ((int) $parent->depth + 1) : 1;
                if ($domain === Category::DOMAIN_HOSPITAL_DEPARTMENT && ($parent || strlen((string) ($payload['code'] ?? '')) > 30)) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '분과는 단일 분류이며 코드는 30자 이하여야 합니다.');
                }
                $groupCode = $this->resolveGroupCode($payload, $parent);
                if ($parent && (in_array($domain, [Category::DOMAIN_HOSPITAL_DEPARTMENT, Category::DOMAIN_HOSPITAL_SUBJECT, Category::DOMAIN_DOCTOR_SUBJECT, Category::DOMAIN_TALK, Category::DOMAIN_HOSPITAL_EVALUATION], true) || $groupCode === Category::GROUP_PROMOTION)) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '이 업무는 한 단계 분류로 관리합니다.');
                }
                if (in_array($domain, Category::medicalDomains(), true) && (! in_array($groupCode, Category::groupCodes(), true) || ($groupCode === Category::GROUP_PROMOTION && $domain !== Category::DOMAIN_HOSPITAL_MEDICAL))) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '업무에 맞는 분류 그룹을 선택해 주세요.');
                }
                $fullPath = $parent
                    ? trim((string) ($parent->full_path ?: $parent->name)).' > '.$name
                    : $name;

                $exists = $this->query->existsSiblingName($domain, $parent?->id, $name, $groupCode);
                if (mb_strlen($fullPath) > 255) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '상위 분류를 포함한 전체 이름은 255자 이하여야 합니다.');
                }

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

                if ($parent) {
                    $created->topics()->sync($parent->topics()->pluck('category_topics.id')->all());
                }

                $created = $created->fresh(['parent', 'iconMedia']);
                $this->historyRecordAction->recordCreated($created);

                return $created;
            });
        } catch (UniqueConstraintViolationException) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '같은 상위 카테고리의 이름 또는 카테고리 코드가 이미 사용 중입니다.');
        }

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
