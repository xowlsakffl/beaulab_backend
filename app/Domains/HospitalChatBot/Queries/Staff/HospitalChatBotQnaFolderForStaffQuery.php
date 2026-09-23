<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQnaFolder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

final class HospitalChatBotQnaFolderForStaffQuery
{
    /** @return Collection<int, HospitalChatBotQnaFolder> */
    public function all(): Collection
    {
        return HospitalChatBotQnaFolder::query()
            ->withCount('qnas')
            ->orderBy('depth')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function find(int $folderId): HospitalChatBotQnaFolder
    {
        $folder = HospitalChatBotQnaFolder::query()->find($folderId);

        if ($folder === null) {
            throw new CustomException(ErrorCode::NOT_FOUND, '폴더를 찾을 수 없습니다.');
        }

        return $folder;
    }

    /**
     * 상위 폴더로 쓸 수 있는지 확인하고 새 폴더의 깊이를 정한다.
     *
     * 2단 고정이므로 소분류 아래에는 더 만들 수 없다.
     */
    public function resolveDepth(?int $parentId): int
    {
        if ($parentId === null) {
            return 1;
        }

        $parent = $this->find($parentId);

        if ((int) $parent->depth >= HospitalChatBotQnaFolder::MAX_DEPTH) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '폴더는 2단까지만 만들 수 있습니다.',
            );
        }

        return (int) $parent->depth + 1;
    }

    public function create(array $payload, ?int $staffId): HospitalChatBotQnaFolder
    {
        try {
            return $this->insert($payload, $staffId);
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicateName($payload['name']);
        }
    }

    private function insert(array $payload, ?int $staffId): HospitalChatBotQnaFolder
    {
        return HospitalChatBotQnaFolder::query()->create([
            'parent_id' => $payload['parent_id'],
            'depth' => $this->resolveDepth($payload['parent_id']),
            'name' => $payload['name'],
            'sort_order' => $payload['sort_order'],
            'created_by_staff_id' => $staffId,
            'updated_by_staff_id' => $staffId,
        ]);
    }

    /**
     * 이름과 순서만 바꾼다.
     *
     * 폴더 이동은 하위 깊이까지 다시 계산해야 하고 2단 제약과 얽힌다.
     * 지금 화면에서 필요하지 않아 넣지 않았다.
     */
    public function update(HospitalChatBotQnaFolder $folder, array $payload, ?int $staffId): HospitalChatBotQnaFolder
    {
        try {
            $folder->forceFill([
                'name' => $payload['name'],
                'sort_order' => $payload['sort_order'],
                'updated_by_staff_id' => $staffId,
            ])->save();
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicateName($payload['name']);
        }

        return $folder->refresh();
    }

    /**
     * 비어 있을 때만 지운다.
     *
     * 하위 폴더나 Q&A가 남아 있는데 지우면 무엇이 함께 사라지는지
     * 누르는 사람이 알 수 없다.
     */
    public function delete(HospitalChatBotQnaFolder $folder): void
    {
        $childCount = HospitalChatBotQnaFolder::query()->where('parent_id', $folder->id)->count();
        if ($childCount > 0) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                "하위 폴더 {$childCount}개를 먼저 정리해 주세요.",
            );
        }

        // 삭제된 Q&A까지 세야 한다. 되살릴 때 갈 곳이 없어지면 안 된다.
        $qnaCount = HospitalChatBotQna::withTrashed()->where('folder_id', $folder->id)->count();
        if ($qnaCount > 0) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                "이 폴더에 Q&A {$qnaCount}건이 있습니다. 옮기거나 삭제한 뒤 폴더를 지워 주세요.",
            );
        }

        $folder->delete();
    }

    private function duplicateName(string $name): CustomException
    {
        return new CustomException(
            ErrorCode::INVALID_REQUEST,
            "같은 위치에 '{$name}' 폴더가 이미 있습니다.",
        );
    }
}
