<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;

final class HospitalChatBotQnaUpdateForStaffQuery
{
    public function update(HospitalChatBotQna $qna, array $payload): HospitalChatBotQna
    {
        $qna->fill($payload);
        if ($qna->isDirty()) {
            $qna->save();
        }

        return $qna->fresh();
    }
}
