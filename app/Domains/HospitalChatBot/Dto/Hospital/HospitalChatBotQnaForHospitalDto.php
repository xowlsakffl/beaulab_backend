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
            'category' => (string) $qna->category,
            'question' => (string) $qna->question,
            'answer' => (string) $qna->answer,
            'sort_order' => (int) $qna->sort_order,
        ];
    }
}
