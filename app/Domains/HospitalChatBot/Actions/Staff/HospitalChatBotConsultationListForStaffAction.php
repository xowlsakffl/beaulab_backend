<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotConsultationForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;

final class HospitalChatBotConsultationListForStaffAction
{
    public function __construct(private readonly HospitalChatBotConsultationForStaffQuery $query) {}

    /** @param  array<string, mixed>  $filters */
    public function execute(array $filters): array
    {
        return PaginatedResponse::fromPaginator(
            $this->query->paginate($filters),
            static fn (HospitalChatBotConsultation $consultation): array => HospitalChatBotConsultationForStaffDto::fromModel($consultation),
        );
    }
}
