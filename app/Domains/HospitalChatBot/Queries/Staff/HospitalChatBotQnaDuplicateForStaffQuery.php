<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;

final class HospitalChatBotQnaDuplicateForStaffQuery
{
    public function exists(string $questionKey, ?int $ignoreId = null): bool
    {
        return HospitalChatBotQna::query()
            ->where('question_key', $questionKey)
            ->when($ignoreId !== null, static fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
