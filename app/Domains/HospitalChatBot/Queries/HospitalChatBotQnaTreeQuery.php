<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class HospitalChatBotQnaTreeQuery
{
    public const MAX_DEPTH = 3;

    public function all(bool $lock = false): Collection
    {
        return HospitalChatBotQna::query()->orderBy('id')->when($lock, fn ($query) => $query->lockForUpdate())->get();
    }

    /** 깊이 우선 순서와 전체 경로를 함께 계산해 검색·페이지 이동 후에도 위치를 알 수 있게 한다. */
    public function ordered(Collection $nodes, bool $visibleOnly = false, string $direction = 'asc'): Collection
    {
        $children = $nodes->sortBy([['sort_order', $direction], ['id', 'asc']])->groupBy(fn ($node) => $node->parent_id ?? 0);
        $result = collect();
        $walk = function (int $parent, array $path, array $ids) use (&$walk, $children, $result, $visibleOnly): void {
            if (count($ids) >= self::MAX_DEPTH) {
                return;
            }
            foreach ($children->get($parent, collect()) as $node) {
                if ($visibleOnly && $node->status !== HospitalChatBotQna::STATUS_ACTIVE) {
                    continue;
                }
                if (in_array($node->id, $ids, true)) {
                    continue;
                }
                $node->setAttribute('tree_path', implode(' > ', $path));
                $node->setAttribute('ancestor_ids', $ids);
                $result->push($node);
                $walk((int) $node->id, [...$path, $node->question], [...$ids, (int) $node->id]);
            }
        };
        $walk(0, [], []);

        return $result;
    }

    public function depth(Collection $nodes, ?int $parentId, ?int $id = null): int
    {
        if ($parentId === null) {
            return 1;
        }
        $byId = $nodes->keyBy('id');
        $depth = 1;
        $seen = $id === null ? [] : [$id];
        while ($parentId !== null) {
            if (in_array($parentId, $seen, true)) {
                throw ValidationException::withMessages(['parent_id' => '자기 자신이나 하위 항목을 상위 항목으로 선택할 수 없습니다.']);
            }
            $parent = $byId->get($parentId);
            if ($parent === null) {
                throw ValidationException::withMessages(['parent_id' => '상위 항목을 찾을 수 없습니다.']);
            }
            $seen[] = $parentId;
            $parentId = $parent->parent_id;
            $depth++;
            if ($depth > self::MAX_DEPTH) {
                throw ValidationException::withMessages(['parent_id' => 'Q&A는 3단계까지 등록할 수 있습니다.']);
            }
        }

        return $depth;
    }

    public function descendants(Collection $nodes, int $id): Collection
    {
        $result = collect();
        $pending = [$id];
        while ($pending !== []) {
            $parent = array_shift($pending);
            foreach ($nodes->where('parent_id', $parent) as $node) {
                if ($node->id === $id || $result->has($node->id)) {
                    continue;
                }
                $result->put($node->id, $node);
                $pending[] = $node->id;
            }
        }

        return $result;
    }

    public static function questionKey(string $question, ?int $parentId): string
    {
        return HospitalChatBotQna::questionKey($question, $parentId);
    }
}
