<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;

final class HospitalChatBotQnaCreateForStaffQuery
{
    public function create(array $payload): HospitalChatBotQna
    {
        return HospitalChatBotQna::query()->create($payload);
    }
}
