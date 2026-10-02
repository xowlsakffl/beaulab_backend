<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotQnaTreeQuery;

final class HospitalChatBotQnaGetForStaffAction
{
    public function __construct(private readonly HospitalChatBotQnaTreeQuery $tree) {}

    public function execute(HospitalChatBotQna $qna): array
    {
        $node = $this->tree->ordered($this->tree->all())->firstWhere('id', $qna->id);
        abort_if($node === null, 404);

        return HospitalChatBotQnaForStaffDto::fromModel($node);
    }
}
