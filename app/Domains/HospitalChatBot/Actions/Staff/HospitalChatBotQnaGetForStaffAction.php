<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;

final class HospitalChatBotQnaGetForStaffAction
{
    public function execute(HospitalChatBotQna $qna): array
    {
        return HospitalChatBotQnaForStaffDto::fromModel($qna->loadMissing('folder:id,parent_id,name'));
    }
}
