<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;

final class HospitalChatBotQnaDeleteForStaffQuery
{
    public function delete(HospitalChatBotQna $qna): void
    {
        $qna->delete();
    }
}
