<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Domains\HospitalChatBot\Dto\Hospital\HospitalChatBotQnaForHospitalDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\Hospital\HospitalChatBotQnaListForHospitalQuery;

final class HospitalChatBotQnaListForHospitalAction
{
    public function __construct(private readonly HospitalChatBotQnaListForHospitalQuery $query) {}

    public function execute(): array
    {
        return $this->query->get()
            ->map(static fn (HospitalChatBotQna $qna): array => HospitalChatBotQnaForHospitalDto::fromModel($qna))
            ->all();
    }
}
