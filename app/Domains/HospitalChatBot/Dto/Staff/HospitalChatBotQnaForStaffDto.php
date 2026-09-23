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
            'category' => (string) $qna->category,
            'question' => (string) $qna->question,
            'answer' => (string) $qna->answer,
            'status' => (string) $qna->status,
            'sort_order' => (int) $qna->sort_order,
            'created_by_staff_id' => $qna->created_by_staff_id ? (int) $qna->created_by_staff_id : null,
            'updated_by_staff_id' => $qna->updated_by_staff_id ? (int) $qna->updated_by_staff_id : null,
            'created_at' => $qna->created_at?->toISOString(),
            'updated_at' => $qna->updated_at?->toISOString(),
        ];
    }
}
