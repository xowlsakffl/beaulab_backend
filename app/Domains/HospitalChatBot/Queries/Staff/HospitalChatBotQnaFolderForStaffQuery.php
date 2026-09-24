<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQnaFolder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class HospitalChatBotQnaFolderForStaffQuery
{
    /** @return Collection<int, HospitalChatBotQnaFolder> */
    public function all(): Collection
    {
        return HospitalChatBotQnaFolder::query()
            // 삭제 차단은 소프트 삭제된 Q&A까지 센다. 화면에 다른 수를 보여 주면
            // 0건인데 지워지지 않는 폴더가 생긴다.
            ->withCount(['qnas as qnas_count' => fn ($query) => $query->withTrashed()])
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

        // category는 폴더 이름의 사본이다. 이름을 바꾸면 따라가지 않으면
        // 목록 정렬과 기존 필터가 옛 이름을 보게 된다.
        HospitalChatBotQna::withTrashed()
            ->where('folder_id', $folder->id)
            ->update(['category' => $payload['name']]);

        return $folder->refresh();
    }

    /**
     * 폴더를 원하는 자리로 옮긴다.
     *
     * 순서 바꾸기와 상위 바꾸기를 한 경로로 처리한다. 드래그 한 번이 둘 중 무엇인지는
     * 놓는 자리가 정하지, 누르는 사람이 고르는 게 아니기 때문이다.
     *
     * $position은 대상 형제 목록에서 자기 자신을 뺀 0-based 자리다.
     */
    public function move(HospitalChatBotQnaFolder $folder, ?int $parentId, int $position): void
    {
        if ($parentId === (int) $folder->id) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '폴더를 자기 자신 안으로 옮길 수 없습니다.');
        }

        $depth = $this->resolveDepth($parentId);

        // 하위 폴더를 가진 폴더는 소분류가 될 수 없다. 그러면 손자가 3단이 된다.
        if ($depth >= HospitalChatBotQnaFolder::MAX_DEPTH && $this->hasChildren($folder)) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '하위 폴더가 있는 폴더는 대분류로만 둘 수 있습니다.',
            );
        }

        try {
            DB::transaction(function () use ($folder, $parentId, $position, $depth): void {
                $previousParentId = $folder->parent_id !== null ? (int) $folder->parent_id : null;

                $siblings = $this->siblingIds($parentId, exceptId: (int) $folder->id);
                $index = max(0, min($position, count($siblings)));
                array_splice($siblings, $index, 0, [(int) $folder->id]);

                $folder->forceFill([
                    'parent_id' => $parentId,
                    'depth' => $depth,
                ])->save();

                $this->applyOrder($siblings);

                // 떠나온 자리에도 빈 번호가 남는다. 다음 이동이 그 틈을 밟지 않게 다시 채운다.
                if ($previousParentId !== $parentId) {
                    $this->applyOrder($this->siblingIds($previousParentId, exceptId: (int) $folder->id));
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicateName((string) $folder->name);
        }
    }

    private function hasChildren(HospitalChatBotQnaFolder $folder): bool
    {
        return HospitalChatBotQnaFolder::query()->where('parent_id', $folder->id)->exists();
    }

    /** @return list<int> */
    private function siblingIds(?int $parentId, int $exceptId): array
    {
        return HospitalChatBotQnaFolder::query()
            ->where(fn ($query) => $parentId === null
                ? $query->whereNull('parent_id')
                : $query->where('parent_id', $parentId))
            ->whereKeyNot($exceptId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * 순서를 0부터 다시 매긴다.
     *
     * 사이에 끼워 넣을 때마다 번호를 벌려 두는 방식은 언젠가 자리가 없어진다.
     * 폴더 수가 적으니 매번 다시 매기는 편이 단순하고 틀릴 데가 없다.
     *
     * @param  list<int>  $orderedIds
     */
    private function applyOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $order => $id) {
            HospitalChatBotQnaFolder::query()->whereKey($id)->update(['sort_order' => $order]);
        }
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
