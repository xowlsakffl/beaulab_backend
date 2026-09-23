<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Dto\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQnaFolder;
use Illuminate\Support\Collection;

final readonly class HospitalChatBotQnaFolderForStaffDto
{
    /**
     * 평평한 목록을 2단 트리로 만든다.
     *
     * @param  Collection<int, HospitalChatBotQnaFolder>  $folders
     * @return list<array<string, mixed>>
     */
    public static function tree(Collection $folders): array
    {
        $byParent = $folders->groupBy(static fn (HospitalChatBotQnaFolder $folder): string => (string) ($folder->parent_id ?? 'root'));

        return collect($byParent->get('root', collect()))
            ->map(static function (HospitalChatBotQnaFolder $folder) use ($byParent): array {
                $children = collect($byParent->get((string) $folder->id, collect()))
                    ->map(static fn (HospitalChatBotQnaFolder $child): array => self::node($child, []))
                    ->values()
                    ->all();

                return self::node($folder, $children);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private static function node(HospitalChatBotQnaFolder $folder, array $children): array
    {
        return [
            'id' => (int) $folder->id,
            'parent_id' => $folder->parent_id !== null ? (int) $folder->parent_id : null,
            'depth' => (int) $folder->depth,
            'name' => (string) $folder->name,
            'sort_order' => (int) $folder->sort_order,
            // 이 폴더에 직접 달린 Q&A 수. 하위 폴더 것은 포함하지 않는다.
            'qna_count' => (int) ($folder->qnas_count ?? 0),
            'children' => $children,
        ];
    }
}
