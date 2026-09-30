<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Dto\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;

final readonly class HospitalChatBotQnaForStaffDto
{
    public static function fromModel(HospitalChatBotQna $qna): array
    {
        return [
            'id' => (int) $qna->id,
            'folder_id' => $qna->folder_id !== null ? (int) $qna->folder_id : null,
            // 분류 이름은 폴더에서 가져온다. category 컬럼은 이관 전 데이터의 흔적이다.
            'folder_name' => self::folderName($qna) !== null ? (string) $qna->folder->name : null,
            'category' => (string) $qna->category,
            'question' => (string) $qna->question,
            'answer' => (string) $qna->answer,
            'status' => (string) $qna->status,
            'sort_order' => (int) $qna->sort_order,
            'created_by_staff_id' => $qna->created_by_staff_id !== null ? (int) $qna->created_by_staff_id : null,
            'updated_by_staff_id' => $qna->updated_by_staff_id !== null ? (int) $qna->updated_by_staff_id : null,
            'created_at' => $qna->created_at?->toISOString(),
            'updated_at' => $qna->updated_at?->toISOString(),
        ];
    }

    /** 관계를 지연 로드하지 않는다. 미리 싣지 않았으면 비운다. */
    private static function folderName(HospitalChatBotQna $qna): ?string
    {
        $folder = $qna->relationLoaded('folder') ? $qna->getRelation('folder') : null;

        return $folder?->name !== null ? (string) $folder->name : null;
    }
}
