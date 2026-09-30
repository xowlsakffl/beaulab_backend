<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Dto\Hospital;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;

final readonly class HospitalChatBotQnaForHospitalDto
{
    public static function fromModel(HospitalChatBotQna $qna): array
    {
        return [
            'id' => (int) $qna->id,
            'folder_id' => $qna->folder_id !== null ? (int) $qna->folder_id : null,
            'parent_folder_id' => self::folderParentId($qna) !== null ? (int) $qna->folder->parent_id : null,
            // SDK가 폴더 트리를 만들 수 있도록 이름도 함께 준다.
            'folder_name' => self::folderName($qna) !== null ? (string) $qna->folder->name : null,
            'category' => (string) $qna->category,
            'question' => (string) $qna->question,
            'answer' => (string) $qna->answer,
            'sort_order' => (int) $qna->sort_order,
        ];
    }

    /** 관계를 지연 로드하지 않는다. 미리 싣지 않았으면 비운다. */
    private static function folderName(HospitalChatBotQna $qna): ?string
    {
        $folder = $qna->relationLoaded('folder') ? $qna->getRelation('folder') : null;

        return $folder?->name !== null ? (string) $folder->name : null;
    }

    private static function folderParentId(HospitalChatBotQna $qna): ?int
    {
        $folder = $qna->relationLoaded('folder') ? $qna->getRelation('folder') : null;

        return $folder?->parent_id !== null ? (int) $folder->parent_id : null;
    }

    /**
     * 상위 폴더 이름.
     *
     * SDK가 다른 Q&A에서 상위 이름을 유추하면, 상위에 직접 달린 Q&A가 없을 때
     * 계층이 사라진다. 서버가 직접 알려 준다.
     */
    private static function parentFolderName(HospitalChatBotQna $qna): ?string
    {
        $folder = $qna->relationLoaded('folder') ? $qna->getRelation('folder') : null;
        if ($folder === null || ! $folder->relationLoaded('parent')) {
            return null;
        }

        $parent = $folder->getRelation('parent');

        return $parent?->name !== null ? (string) $parent->name : null;
    }
}
