<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaDeleteForStaffQuery;

final class HospitalChatBotQnaDeleteForStaffAction
{
    public function __construct(private readonly HospitalChatBotQnaDeleteForStaffQuery $query) {}

    public function execute(HospitalChatBotQna $qna): array
    {
        $this->query->delete($qna);

        return ['deleted' => true, 'id' => (int) $qna->id];
    }
}
