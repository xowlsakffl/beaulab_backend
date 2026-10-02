<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Hospital;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Illuminate\Support\Collection;

final class HospitalChatBotQnaListForHospitalQuery
{
    /**
     * @return Collection<int, HospitalChatBotQna>
     */
    public function get(): Collection
    {
        $tree = app(\App\Domains\HospitalChatBot\Queries\HospitalChatBotQnaTreeQuery::class);

        return $tree->ordered($tree->all(), visibleOnly: true);
    }
}
